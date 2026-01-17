<?php
namespace Armo\DineFlow\Service;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SessionItems {

	/**
	 * Best-effort extractor for unit price + add-ons.
	 */
	private static function extract_pricing_and_addons( int $product_id, int $qty, array $meta ): array {
		$qty = max( 1, (int) $qty );

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
		$currency_code   = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';

		// 1) Base price: try YITH tmdata first, else fall back to WooCommerce product price.
		$base = 0.0;
		if ( isset( $meta['tmdata']['cpf_product_price'] ) ) {
			$base = (float) $meta['tmdata']['cpf_product_price'];
		} elseif ( function_exists( 'wc_get_product' ) && $product_id ) {
			$p = wc_get_product( $product_id );
			if ( $p ) {
				$base = (float) $p->get_price();
			}
		}

		// 2) Add-ons: prefer tmcartepo entries (richer), else fall back to yith_wapo_options.
		$addons = [];
		$addons_total = 0.0;

		if ( isset( $meta['tmcartepo'] ) && is_array( $meta['tmcartepo'] ) ) {
			foreach ( $meta['tmcartepo'] as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$label = (string) ( $entry['key'] ?? $entry['name'] ?? $entry['post_name'] ?? '' );
				$value = (string) ( $entry['value'] ?? '' );
				$price = isset( $entry['price'] ) ? (float) $entry['price'] : 0.0;
				if ( $label === '' && $value === '' && $price == 0.0 ) {
					continue;
				}
				$addons_total += $price;
				$addons[] = [
					'label' => $label !== '' ? $label : 'Add-on',
					'value' => $value,
					'price' => $price,
				];
			}
		} elseif ( isset( $meta['cart_item_data']['yith_wapo_options'] ) && is_array( $meta['cart_item_data']['yith_wapo_options'] ) ) {
			// This is usually not very descriptive; still better than nothing.
			foreach ( $meta['cart_item_data']['yith_wapo_options'] as $k => $v ) {
				$label = is_string( $k ) ? $k : 'Option';
				if ( is_array( $v ) ) {
					$value = wp_json_encode( $v );
				} else {
					$value = (string) $v;
				}
				$addons[] = [
					'label' => $label,
					'value' => $value,
					'price' => 0.0,
				];
			}
		}

		$unit_total = max( 0.0, $base + $addons_total );
		$line_total = $unit_total * $qty;

		return [
			'currency_symbol' => (string) $currency_symbol,
			'currency_code'   => (string) $currency_code,
			'base_price'      => $base,
			'addons_total'    => $addons_total,
			'unit_total'      => $unit_total,
			'line_total'      => $line_total,
			'addons'          => $addons,
		];
	}


	public static function delete_by_session(int $session_id): void {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( $wpdb->prepare("DELETE FROM $table WHERE session_id=%d", $session_id) );
	}

	public static function delete_item(int $item_id): void {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( $wpdb->prepare("DELETE FROM $table WHERE id=%d", $item_id) );
	}

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'armo_df_session_items';
	}

	public static function create_table(): void {
		global $wpdb;
		$table = self::table_name();
		$charset = $wpdb->get_charset_collate();

		// Backward/forward compatible schema:
		// - meta (legacy) OR yith_addons (newer) can store addon payload
		// - price_snapshot stores the unit price at the time of add-to-session (optional)
		$sql = "CREATE TABLE $table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			qty INT NOT NULL DEFAULT 1,
			meta LONGTEXT NULL,
			yith_addons LONGTEXT NULL,
			price_snapshot DECIMAL(10,2) NULL,
			customer_note TEXT NULL,
			waiter_note TEXT NULL,
			added_by_user_id BIGINT UNSIGNED NULL,
			item_status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY session_id (session_id),
			KEY product_id (product_id)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	public static function add_item( int $session_id, int $product_id, int $qty = 1, array $meta = [] ): void {
		global $wpdb;
		$table = self::table_name();

		$wpdb->insert(
			$table,
			[
				'session_id'  => $session_id,
				'product_id'  => $product_id,
				'qty'         => max( 1, (int) $qty ),
				'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
				'created_at'  => gmdate( 'Y-m-d H:i:s' ),
			],
			[ '%d', '%d', '%d', '%s', '%s' ]
		);
	}

	public static function list_items( int $session_id ): array {
		global $wpdb;
		$table = self::table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session_id=%d ORDER BY id ASC LIMIT 200", $session_id ),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	public static function format_items_for_ui( array $rows ): array {
		$out = [];
		foreach ( $rows as $r ) {
			$product_id = isset( $r['product_id'] ) ? (int) $r['product_id'] : 0;
			$qty        = isset( $r['qty'] ) ? (int) $r['qty'] : 1;
			$title      = $product_id ? get_the_title( $product_id ) : '';

			$meta = [];
			if ( ! empty( $r['meta'] ) ) {
				$decoded = json_decode( (string) $r['meta'], true );
				if ( is_array( $decoded ) ) {
					$meta = $decoded;
				}
			}

			$pricing = self::extract_pricing_and_addons( $product_id, $qty, $meta );

			// If DB has price_snapshot, use it as unit total (preserves historical pricing)
			if ( isset( $r['price_snapshot'] ) && $r['price_snapshot'] !== null && $r['price_snapshot'] !== '' ) {
				$ps = (float) $r['price_snapshot'];
				if ( $ps > 0 ) {
					$pricing['unit_total'] = $ps;
					$pricing['line_total'] = $ps * max( 1, $qty );
				}
			}

			$out[] = [
				'id'         => (int) ( $r['id'] ?? 0 ),
				'product_id' => $product_id,
				'title'      => (string) $title,
				'qty'        => $qty,
				'meta'       => $meta,
				'pricing'    => $pricing,
			];
		}
		return $out;
	}
}
