<?php
namespace Armo\DineFlow\Front;

use Armo\DineFlow\Service\Session;
use Armo\DineFlow\Service\SessionItems;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * v1.1.13: Minimal capture of WooCommerce add-to-cart into the current Dine In session.
 * This is NOT the final "replace cart with order" flow yet, but it lets us test real-time items.
 */
final class WooCapture {

	public static function boot(): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		add_action( 'woocommerce_add_to_cart', [ __CLASS__, 'on_add_to_cart' ], 10, 6 );
	}

	public static function on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ): void {
		$mode  = isset( $_COOKIE['armo_df_mode'] ) ? sanitize_text_field( (string) $_COOKIE['armo_df_mode'] ) : '';
		$table = isset( $_COOKIE['armo_df_table'] ) ? absint( $_COOKIE['armo_df_table'] ) : 0;

		if ( $mode !== 'dinein' || ! $table ) {
			return;
		}

		$session = Session::get_open_by_table( $table );
		if ( ! $session || empty( $session['id'] ) ) {
			return;
		}

		$sid = (int) $session['id'];
		$pid = (int) ( $variation_id ? $variation_id : $product_id );
		$qty = (int) $quantity;

		$meta = [
			'variation' => $variation,
			'cart_item_data' => $cart_item_data,
		];

		SessionItems::add_item( $sid, $pid, $qty, $meta );
	}
}
