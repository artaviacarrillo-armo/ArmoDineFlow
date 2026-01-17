<?php
namespace Armo\DineFlow\Ajax;

/**
 * =============================================================================
 * ARMO DINEFLOW — WAITER ACTIONS (v1.2.0)
 * =============================================================================
 *
 * Propósito:
 * - Acciones críticas del mesero sobre la orden:
 *   1) Enviar items a cocina (pending_waiter -> sent_kitchen)
 *   2) Eliminar un item (DELETE físico)
 *   3) Cancelar orden completa (borra items de la sesión)
 *
 * Requisitos:
 * - Tabla items: wp_{prefix}_armo_df_session_items
 *
 * Seguridad:
 * - En v1.2.0 dejamos nonces opcionales (si ya los tienes, se activan).
 * - En producción final: current_user_can() + nonce + role waiter.
 */
class WaiterActions
{
    // =========================================================================
    // 0) REGISTRO
    // =========================================================================

    public static function register(): void
    {
        add_action('wp_ajax_armo_df_waiter_send_item_to_kitchen', [__CLASS__, 'send_item_to_kitchen']);
        add_action('wp_ajax_armo_df_waiter_delete_item', [__CLASS__, 'delete_item']);
        add_action('wp_ajax_armo_df_waiter_cancel_order', [__CLASS__, 'cancel_order']);
    }

    // =========================================================================
    // 1) SEND ITEM TO KITCHEN
    // =========================================================================

    /**
     * POST:
     * - item_id
     * (Opcional) nonce
     */
    public static function send_item_to_kitchen(): void
    {
        // self::verify_nonce_or_bail(); // Actívalo si ya tienes nonce estándar.

        $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        if ($item_id <= 0) {
            wp_send_json_error(['message' => 'invalid_item_id'], 400);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'armo_df_session_items';

        // Solo permite mover desde pending_waiter a sent_kitchen
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table}
                 SET item_status = 'sent_kitchen'
                 WHERE id = %d AND item_status = 'pending_waiter'",
                $item_id
            )
        );

        if ($updated === false) {
            wp_send_json_error(['message' => 'db_error'], 500);
        }

        if ((int)$updated === 0) {
            // No se actualizó: item no existe o no estaba en pending
            wp_send_json_error(['message' => 'not_updated'], 409);
        }

        wp_send_json_success(['message' => 'sent_to_kitchen']);
    }

    // =========================================================================
    // 2) DELETE ITEM (BORRADO FÍSICO)
    // =========================================================================

    /**
     * POST:
     * - item_id
     * (Opcional) nonce
     */
    public static function delete_item(): void
    {
        // self::verify_nonce_or_bail();

        $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
        if ($item_id <= 0) {
            wp_send_json_error(['message' => 'invalid_item_id'], 400);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'armo_df_session_items';

        $deleted = $wpdb->delete($table, ['id' => $item_id], ['%d']);

        if ($deleted === false) {
            wp_send_json_error(['message' => 'db_error'], 500);
        }

        if ((int)$deleted === 0) {
            wp_send_json_error(['message' => 'not_deleted'], 404);
        }

        wp_send_json_success(['message' => 'deleted']);
    }

    // =========================================================================
    // 3) CANCEL ORDER (BORRAR TODO DE LA SESIÓN)
    // =========================================================================

    /**
     * Cancela la orden completa asociada a la sesión:
     * - borra físicamente todos los items de la sesión
     *
     * POST:
     * - session_id
     * (Opcional) nonce
     */
    public static function cancel_order(): void
    {
        // self::verify_nonce_or_bail();

        $session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
        if ($session_id <= 0) {
            wp_send_json_error(['message' => 'invalid_session_id'], 400);
        }

        global $wpdb;
        $items_table = $wpdb->prefix . 'armo_df_session_items';

        // Borrado físico de todos los items
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$items_table} WHERE session_id = %d",
                $session_id
            )
        );

        if ($deleted === false) {
            wp_send_json_error(['message' => 'db_error'], 500);
        }

        // (Opcional futuro): marcar sesión como cancelada en tabla sessions si existe.

        wp_send_json_success([
            'message'    => 'order_cancelled',
            'session_id' => $session_id,
            'deleted'    => (int) $deleted,
        ]);
    }

    // =========================================================================
    // 4) NONCE (OPCIONAL)
    // =========================================================================

    private static function verify_nonce_or_bail(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'armo_df_nonce')) {
            wp_send_json_error(['message' => 'invalid_nonce'], 403);
        }
    }
}
