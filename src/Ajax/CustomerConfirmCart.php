<?php
namespace Armo\DineFlow\Ajax;

/**
 * =============================================================================
 * ARMO DINEFLOW — CUSTOMER CONFIRM CART (v1.2.0)
 * =============================================================================
 *
 * Propósito:
 * - Convertir el carrito Woo (draft) en items persistidos en DB como:
 *   item_status = pending_waiter
 * - Guardar snapshot estable:
 *   - yith_addons (normalizado)
 *   - price_snapshot (unit/line totals, currency)
 * - Vaciar el carrito para bloquear edición del cliente sobre lo confirmado.
 *
 * Qué NO hace:
 * - No envía a cocina.
 * - No crea pedido WooCommerce.
 * - No hace "reprice" dinámico.
 *
 * Requisitos:
 * - WooCommerce activo.
 * - Tabla: wp_{prefix}_armo_df_session_items
 *
 * Endpoint:
 * - action=armo_df_customer_confirm_cart
 * - POST: session_id, nonce (si ya tienes nonce en el proyecto)
 *
 * Respuesta JSON:
 * - success:true  => { session_id, inserted, skipped }
 * - success:false => { message, detail? }
 */
class CustomerConfirmCart
{
    // =========================================================================
    // 0) REGISTRO DEL ENDPOINT
    // =========================================================================

    public static function register(): void
    {
        add_action('wp_ajax_armo_df_customer_confirm_cart', [__CLASS__, 'handle']);
        add_action('wp_ajax_nopriv_armo_df_customer_confirm_cart', [__CLASS__, 'handle']);
    }

    // =========================================================================
    // 1) HANDLER PRINCIPAL
    // =========================================================================

    public static function handle(): void
    {
        // 1.1) Seguridad mínima (puedes endurecer cuando tengas nonce estándar)
        // Si ya tienes nonce en el plugin, activa esta validación:
        // self::verify_nonce_or_bail();

        // 1.2) Woo disponible
        if (!function_exists('WC') || !WC() || !WC()->cart) {
            wp_send_json_error([
                'message' => 'woocommerce_not_loaded'
            ], 500);
        }

        // 1.3) Validar session_id
        $session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
        if ($session_id <= 0) {
            wp_send_json_error([
                'message' => 'invalid_session'
            ], 400);
        }

        // 1.4) Leer cart actual
        $cart = WC()->cart->get_cart();
        if (empty($cart)) {
            wp_send_json_error([
                'message' => 'cart_empty'
            ], 400);
        }

        // 1.5) Insertar items en DB como pending_waiter
        $result = self::persist_cart_as_pending_items($session_id, $cart);

        // 1.6) Vaciar cart (bloquea edición del cliente sobre lo confirmado)
        WC()->cart->empty_cart();

        // 1.7) Responder
        wp_send_json_success([
            'session_id' => $session_id,
            'inserted'   => $result['inserted'],
            'skipped'    => $result['skipped'],
        ]);
    }

    // =========================================================================
    // 2) PERSISTENCIA — CART -> DB (pending_waiter)
    // =========================================================================

    /**
     * Inserta cada línea del carrito como un item en wp_armo_df_session_items.
     *
     * @param int $session_id
     * @param array $cart
     * @return array {inserted:int, skipped:int}
     */
    private static function persist_cart_as_pending_items(int $session_id, array $cart): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'armo_df_session_items';

        $inserted = 0;
        $skipped  = 0;

        foreach ($cart as $cart_item_key => $cart_item) {

            // 2.1) Resolver producto
            $product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
            $qty        = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

            if ($product_id <= 0 || $qty <= 0) {
                $skipped++;
                continue;
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                $skipped++;
                continue;
            }

            // 2.2) Capturar addons desde item_data (YITH compatible)
            $addons_snapshot = self::build_addons_snapshot_from_cart_item($cart_item);

            // 2.3) Capturar pricing snapshot
            $price_snapshot = self::build_pricing_snapshot_from_cart_item($cart_item, $product, $qty);

            // 2.4) Insert DB
            $ok = $wpdb->insert(
                $table,
                [
                    'session_id'     => $session_id,
                    'product_id'     => $product_id,
                    'qty'            => $qty,
                    'yith_addons'    => wp_json_encode($addons_snapshot),
                    'price_snapshot' => wp_json_encode($price_snapshot),
                    'item_status'    => 'pending_waiter',
                    'created_at'     => current_time('mysql'),
                ],
                [
                    '%d', // session_id
                    '%d', // product_id
                    '%d', // qty
                    '%s', // yith_addons
                    '%s', // price_snapshot
                    '%s', // item_status
                    '%s', // created_at
                ]
            );

            if ($ok) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        return [
            'inserted' => $inserted,
            'skipped'  => $skipped,
        ];
    }

    // =========================================================================
    // 3) SNAPSHOT — ADDONS (desde woocommerce_get_item_data)
    // =========================================================================

    /**
     * Construye snapshot de addons usando el estándar Woo:
     * apply_filters('woocommerce_get_item_data', [], $cart_item)
     *
     * Esto captura lo que YITH (u otros) exponen en el cart item data.
     *
     * @param array $cart_item
     * @return array
     */
    private static function build_addons_snapshot_from_cart_item(array $cart_item): array
    {
        $item_data = apply_filters('woocommerce_get_item_data', [], $cart_item);

        // Normalizamos a formato estable para el OrderResolver
        $items = [];
        if (is_array($item_data)) {
            foreach ($item_data as $row) {
                // Woo suele entregar: ['name' => '...', 'value' => '...']
                $label = isset($row['name']) ? wp_strip_all_tags((string) $row['name']) : '';
                $value = isset($row['value']) ? wp_strip_all_tags((string) $row['value']) : '';

                if ($label === '' && $value === '') {
                    continue;
                }

                $items[] = [
                    'label'       => $label !== '' ? $label : 'Opción',
                    'value'       => $value,
                    'price_delta' => 0.0, // no siempre viene; el total ya lo trae Woo
                ];
            }
        }

        return [
            'source' => 'woocommerce_item_data',
            'items'  => $items,
        ];
    }

    // =========================================================================
    // 4) SNAPSHOT — PRICING (unit/line totals)
    // =========================================================================

    /**
     * Construye snapshot de precios:
     * - base_price: precio del producto en Woo (momento actual)
     * - addons_total: inferido si es posible, si no 0
     * - unit_price: line_total/qty (sin impuestos) según Woo
     * - line_total: line_total de Woo (sin impuestos)
     *
     * NOTA:
     * Woo cart_item incluye:
     * - line_total (sin impuestos)
     * - line_tax (impuestos)
     *
     * En restaurante normalmente trabajamos con line_total sin impuestos
     * y luego se puede extender.
     *
     * @param array $cart_item
     * @param \WC_Product $product
     * @param int $qty
     * @return array
     */
    private static function build_pricing_snapshot_from_cart_item(array $cart_item, $product, int $qty): array
    {
        $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';

        $line_total = isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : 0.0;
        $unit_price = ($qty > 0) ? ($line_total / $qty) : 0.0;

        $base_price = 0.0;
        if (is_object($product) && method_exists($product, 'get_price')) {
            $base_price = (float) $product->get_price();
        }

        // addons_total: aproximación razonable (snapshot). Puede ser 0 si no hay variación precio.
        // Si unit_price > base_price, asumimos diferencia como addons_total/unit.
        $addons_total = 0.0;
        if ($unit_price > 0 && $base_price > 0 && $unit_price > $base_price) {
            $addons_total = $unit_price - $base_price;
        }

        return [
            'currency'     => (string) $currency,
            'base_price'   => round($base_price, 2),
            'addons_total' => round($addons_total, 2),
            'unit_price'   => round($unit_price, 2),
            'line_total'   => round($line_total, 2),
        ];
    }

    // =========================================================================
    // 5) SEGURIDAD — NONCE (opcional, si ya tienes nonce estándar)
    // =========================================================================

    /**
     * Si tu plugin ya maneja un nonce estándar (recomendado),
     * puedes activar este método desde handle().
     */
    private static function verify_nonce_or_bail(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'armo_df_nonce')) {
            wp_send_json_error([
                'message' => 'invalid_nonce',
            ], 403);
        }
    }
}
