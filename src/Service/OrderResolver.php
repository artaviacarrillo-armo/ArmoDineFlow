<?php
/**
 * =============================================================================
 * ARMO DINEFLOW — ORDER RESOLVER (v1.2.0 core)
 * =============================================================================
 *
 * Objetivo:
 * - Resolver una sesión (mesa) a JSON ESTABLE para:
 *   - Waiter Panel
 *   - KDS
 *   - Checkout futuro (si aplica)
 *
 * Estrategia:
 * - "Snapshot + Reprice opcional"
 *   - Si existe price_snapshot, se usa como precio principal.
 *   - Si no existe, se consulta WooCommerce (wc_get_product).
 *
 * YITH:
 * - NO leemos YITH internamente.
 * - Solo interpretamos lo que ya guardaste en wp_armo_df_session_items.yith_addons
 *   (JSON/texto) y lo normalizamos a una estructura estable.
 */

declare(strict_types=1);

namespace Armo\DineFlow\Service;

if ( ! defined('ABSPATH') ) { exit; }

final class OrderResolver {

	/**
	 * Punto principal de consumo:
	 * @param int   $session_id
	 * @param array $args { statuses?: string[] }
	 * @return array JSON estable
	 */
	public static function resolve_session(int $session_id, array $args = []): array {
		global $wpdb;

		$items_table = $wpdb->prefix . 'armo_df_session_items';

		$statuses = $args['statuses'] ?? null;
		$where_status = '';
		$params = [ $session_id ];

		if (is_array($statuses) && !empty($statuses)) {
			$placeholders = implode(',', array_fill(0, count($statuses), '%s'));
			$where_status = " AND item_status IN ($placeholders) ";
			foreach ($statuses as $st) {
				$params[] = (string) $st;
			}
		}

		$sql = "
			SELECT
				id,
				session_id,
				product_id,
				qty,
				yith_addons,
				customer_note,
				waiter_note,
				price_snapshot,
				added_by_user_id,
				item_status
			FROM $items_table
			WHERE session_id = %d
			$where_status
			ORDER BY id ASC
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

		$items = [];
		$totals = [
			'currency'        => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
			'subtotal'        => 0.0,
			'addons_total'    => 0.0, // si luego agregas precios a addons
			'tax'             => 0.0, // reservado
			'total'           => 0.0,
			'items_count'     => 0,
			'lines_count'     => 0,
		];

		foreach ($rows as $r) {
			$line = self::normalize_item_row($r);

			$items[] = $line;

			$line_total = (float) $line['line_total'];
			$totals['subtotal'] += $line_total;
			$totals['items_count'] += (int) $line['qty'];
			$totals['lines_count'] += 1;
		}

		$totals['subtotal'] = self::round_money($totals['subtotal']);
		$totals['total']    = self::round_money($totals['subtotal'] + $totals['addons_total'] + $totals['tax']);

		return [
			'session_id' => $session_id,
			'items'      => $items,
			'totals'     => $totals,
		];
	}

	// =========================================================================
	// Normalización de filas (DB -> JSON)
	// =========================================================================

	private static function normalize_item_row(array $r): array {

		$item_id    = (int) ($r['id'] ?? 0);
		$product_id = (int) ($r['product_id'] ?? 0);
		$qty        = max(1, (int) ($r['qty'] ?? 1));

		// 1) Snapshot price (si existe)
		$snapshot = self::parse_price_snapshot($r['price_snapshot'] ?? null);

		// 2) Producto Woo
		$product = (function_exists('wc_get_product') && $product_id > 0) ? wc_get_product($product_id) : null;

		$name = $snapshot['name'] ?: ($product ? $product->get_name() : ('#' . $product_id));
		$sku  = $snapshot['sku']  ?: ($product ? (string) $product->get_sku() : '');

		// Precio unitario: snapshot > woo
		$unit_price = null;

		if ($snapshot['unit_price'] !== null) {
			$unit_price = (float) $snapshot['unit_price'];
		} elseif ($product) {
			// get_price() retorna string numérica
			$unit_price = (float) $product->get_price();
		} else {
			$unit_price = 0.0;
		}

		$yith = self::normalize_yith_addons($r['yith_addons'] ?? null);

		$line_total = self::round_money($unit_price * $qty);

		return [
			'item_id'      => $item_id,
			'product_id'   => $product_id,
			'sku'          => $sku,
			'name'         => $name,
			'qty'          => $qty,
			'unit_price'   => self::round_money($unit_price),
			'line_total'   => $line_total,
			'currency'     => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
			'status'       => (string) ($r['item_status'] ?? 'active'),

			// Notas
			'customer_note' => is_string($r['customer_note'] ?? null) ? (string) $r['customer_note'] : '',
			'waiter_note'   => is_string($r['waiter_note'] ?? null) ? (string) $r['waiter_note'] : '',

			// YITH (solo lectura DB)
			'yith' => $yith,

			// Snapshot bruto por si lo necesitas para auditoría/debug
			'_snapshot' => $snapshot['_raw'],
		];
	}

	// =========================================================================
	// Price Snapshot (opcional)
	// =========================================================================

	/**
	 * price_snapshot recomendado (string JSON):
	 * {
	 *   "name":"Tacos al pastor",
	 *   "sku":"TACO-001",
	 *   "unit_price": 11.0
	 * }
	 */
	private static function parse_price_snapshot($raw): array {
		$raw_str = is_string($raw) ? trim($raw) : '';
		$data = [];

		if ($raw_str !== '') {
			$decoded = json_decode($raw_str, true);
			if (is_array($decoded)) {
				$data = $decoded;
			}
		}

		$name = isset($data['name']) ? (string) $data['name'] : '';
		$sku  = isset($data['sku'])  ? (string) $data['sku']  : '';

		$unit_price = null;
		if (isset($data['unit_price']) && is_numeric($data['unit_price'])) {
			$unit_price = (float) $data['unit_price'];
		}

		return [
			'name'       => $name,
			'sku'        => $sku,
			'unit_price' => $unit_price,
			'_raw'       => $data,
		];
	}

	// =========================================================================
	// YITH Addons (DB JSON -> JSON estable)
	// =========================================================================

	/**
	 * Normaliza el JSON/texto guardado en yith_addons.
	 * Soporta:
	 * - JSON object/array
	 * - string no-JSON -> lo envuelve como note para no perderlo
	 */
	private static function normalize_yith_addons($raw): array {
		$raw_str = is_string($raw) ? trim($raw) : '';

		if ($raw_str === '' || strtolower($raw_str) === 'null') {
			return [
				'has_addons' => false,
				'groups'     => [],
				'_raw'       => null,
			];
		}

		$decoded = json_decode($raw_str, true);

		if (!is_array($decoded)) {
			// no es JSON válido, lo preservamos
			return [
				'has_addons' => true,
				'groups'     => [
					[
						'group_label' => 'Addons',
						'options'     => [
							[
								'label' => $raw_str,
								'value' => $raw_str,
							]
						],
					]
				],
				'_raw' => $raw_str,
			];
		}

		// Caso simple: si ya viene como estructura conocida, lo pasamos
		// y si viene como array plano, lo envolvemos.
		$groups = [];

		// 1) Si ya tiene 'groups'
		if (isset($decoded['groups']) && is_array($decoded['groups'])) {
			foreach ($decoded['groups'] as $g) {
				$groups[] = [
					'group_label' => isset($g['group_label']) ? (string) $g['group_label'] : 'Addons',
					'options'     => self::normalize_addon_options($g['options'] ?? []),
				];
			}
		} else {
			// 2) Si viene “plano”, lo convertimos a 1 grupo
			$groups[] = [
				'group_label' => 'Addons',
				'options'     => self::normalize_addon_options($decoded),
			];
		}

		return [
			'has_addons' => !empty($groups) && !empty($groups[0]['options']),
			'groups'     => $groups,
			'_raw'       => $decoded,
		];
	}

	private static function normalize_addon_options($raw): array {
		$out = [];

		if (!is_array($raw)) return $out;

		// Permite:
		// - array de opciones
		// - objeto tipo { "0":"Yes", "1":"No" }
		foreach ($raw as $k => $v) {

			// opción ya estructurada
			if (is_array($v) && (isset($v['label']) || isset($v['value']))) {
				$out[] = [
					'label' => isset($v['label']) ? (string) $v['label'] : (string) ($v['value'] ?? $k),
					'value' => isset($v['value']) ? (string) $v['value'] : (string) ($v['label'] ?? $k),
				];
				continue;
			}

			// key/value simple
			if (is_scalar($v)) {
				$out[] = [
					'label' => is_string($k) ? $k : 'Option',
					'value' => (string) $v,
				];
				continue;
			}

			// fallback
			$out[] = [
				'label' => 'Option',
				'value' => wp_json_encode($v),
			];
		}

		return $out;
	}

	// =========================================================================
	// Utilidades
	// =========================================================================

	private static function round_money(float $n): float {
		return round($n, 2);
	}
}
