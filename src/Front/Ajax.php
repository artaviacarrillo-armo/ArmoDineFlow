<?php
namespace Armo\DineFlow\Front;

use Armo\DineFlow\Service\Session;
use Armo\DineFlow\Service\SessionItems;
use Armo\DineFlow\Service\OrderResolver;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Ajax {

	/**
	 * Best-effort permission:
	 * - admins/managers always allowed
	 * - otherwise only the waiter assigned to the session
	 */
	private static function can_manage_session(int $session_id): bool {
		if (current_user_can('manage_options') || current_user_can('armo_df_manage')) {
			return true;
		}
		if (!is_user_logged_in()) {
			return false;
		}
		$session = Session::get($session_id);
		if (!$session) {
			return false;
		}
		$user_id = get_current_user_id();
		$assigned = 0;
		if (is_array($session)) {
			$assigned = (int)($session['assigned_waiter'] ?? $session['assigned_waiter_user_id'] ?? 0);
		} elseif (is_object($session)) {
			$assigned = (int)($session->assigned_waiter ?? $session->assigned_waiter_user_id ?? 0);
		}
		return $assigned && $assigned === $user_id;
	}

	public static function boot(): void {
		add_action( 'wp_ajax_armo_df_assume_session', [ __CLASS__, 'assume_session' ] );
		add_action( 'wp_ajax_armo_df_waiter_get_session', [ __CLASS__, 'waiter_get_session' ] );
		add_action( 'wp_ajax_armo_df_get_session_order', [ __CLASS__, 'get_session_order' ] );
		add_action( 'wp_ajax_armo_df_cancel_order', [ __CLASS__, 'cancel_order' ] );
		add_action( 'wp_ajax_armo_df_waiter_remove_item', [ __CLASS__, 'waiter_remove_item' ] );
	}

	public static function assume_session(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'not_logged_in' ], 401 );
		}

		check_ajax_referer( 'armo_df_waiter', 'nonce' );

		$session_id = absint( $_POST['session_id'] ?? 0 );
		if ( ! $session_id ) {
			wp_send_json_error( [ 'message' => 'missing_session' ], 400 );
		}

		$ok = Session::assume( $session_id, get_current_user_id() );
		if ( ! $ok ) {
			wp_send_json_error( [ 'message' => 'cannot_assume' ], 409 );
		}

		wp_send_json_success( [ 'message' => 'ok' ] );
	}

	public static function waiter_get_session(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'not_logged_in' ], 401 );
		}

		check_ajax_referer( 'armo_df_waiter', 'nonce' );

		try {
			$session_id = absint( $_POST['session_id'] ?? 0 );
			if ( ! $session_id ) {
				wp_send_json_error( [ 'message' => 'missing_session' ], 400 );
			}

			$s = Session::get_by_id( $session_id );
			if ( ! $s || (string) ( $s['status'] ?? '' ) !== 'open' ) {
				wp_send_json_error( [ 'message' => 'not_found' ], 404 );
			}

			// Permission: allow if unassigned (so waiter can preview) OR assigned to current user.
			$assigned = isset( $s['assigned_waiter'] ) ? (int) $s['assigned_waiter'] : 0;
			if ( $assigned && $assigned !== get_current_user_id() ) {
				wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
			}

			$meta   = Session::get_table_meta( (int) ( $s['table_id'] ?? 0 ) );
			$order  = OrderResolver::resolve_session_order( (int) $session_id );
			$items  = (array) ( $order['items'] ?? [] );
			$totals = (array) ( $order['totals'] ?? [ 'total' => 0, 'total_html' => '$0.00', 'total_text' => '$0.00' ] );

			wp_send_json_success( [
				'session' => [
					'id'             => (int) $s['id'],
					'table_id'        => (int) $s['table_id'],
					'join_code'       => (string) ( $s['join_code'] ?? '' ),
					'assigned_waiter' => $assigned,
				],
				'table'   => $meta,
				'items'   => $items,
				'totals'  => $totals,
			] );
		} catch ( \Throwable $e ) {
			error_log( '[Armo DineFlow] waiter_get_session fatal: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
			wp_send_json_error( [
				'message' => 'server_error',
				'detail'  => $e->getMessage(),
			], 500 );
		}
	}


	public static function get_session_order(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'not_logged_in' ], 401 );
		}
		check_ajax_referer( 'armo_df_waiter', 'nonce' );
		nocache_headers();
		$session_id = absint( $_POST['session_id'] ?? 0 );
		if ( $session_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'missing_session_id' ], 400 );
		}
		if ( ! self::can_manage_session( $session_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		try {
			$result = OrderResolver::resolve_session_order( $session_id );
			if ( empty( $result['ok'] ) ) {
				wp_send_json_error( $result, 404 );
			}
			wp_send_json_success( $result );
		} catch ( \Throwable $e ) {
			error_log( '[Armo DineFlow] get_session_order fatal: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
			wp_send_json_error( [
				'message' => 'server_error',
				'detail'  => $e->getMessage(),
			], 500 );
		}
	}

	public static function waiter_remove_item(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'not_logged_in' ], 401 );
		}
		check_ajax_referer( 'armo_df_waiter', 'nonce' );
		$session_id = absint( $_POST['session_id'] ?? 0 );
		$item_id = absint( $_POST['item_id'] ?? 0 );
		if ( ! $session_id || ! $item_id ) {
			wp_send_json_error( [ 'message' => 'missing_params' ], 400 );
		}
		if ( ! self::can_manage_session( $session_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}
		// Verify item belongs to session
		global $wpdb;
		$tbl = $wpdb->prefix . 'armo_df_session_items';
		$owner = (int) $wpdb->get_var( $wpdb->prepare( "SELECT session_id FROM $tbl WHERE id=%d", $item_id ) );
		if ( $owner !== (int) $session_id ) {
			wp_send_json_error( [ 'message' => 'item_not_in_session' ], 409 );
		}
		SessionItems::delete_item( $item_id );
		wp_send_json_success( [ 'message' => 'ok' ] );
	}

	public static function cancel_order(): void {
		check_ajax_referer( 'armo_df_waiter', 'nonce' );
		$session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
		if ($session_id <= 0) {
			wp_send_json_error(['message' => 'missing_session_id'], 400);
		}
		if ( ! self::can_manage_session( $session_id ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		// Remove session items
		SessionItems::delete_by_session($session_id);

		// Mark session as cancelled (if table exists)
		global $wpdb;
		$sessions_table = $wpdb->prefix . 'armo_df_sessions';
		$has_sessions = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $sessions_table));
		if ($has_sessions) {
			$wpdb->update($sessions_table, ['status' => 'cancelled'], ['id' => $session_id]);
		}

		// Free table if using wp_armo_df_tables table schema
		$tables_table = $wpdb->prefix . 'armo_df_tables';
		$has_tables = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tables_table));
		if ($has_tables) {
			$wpdb->update($tables_table, ['status' => 'free', 'active_session_id' => null], ['active_session_id' => $session_id]);
		}

		wp_send_json_success(['ok' => true, 'session_id' => $session_id]);
	}

}
