<?php
namespace Armo\DineFlow\Service;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Session {

	/**
	 * Back-compat alias.
	 * Some services may call get_session(); canonical method is get_by_id().
	 */
	public static function get_session( int $session_id ) {
		return self::get_by_id( $session_id );
	}


	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'armo_df_sessions';
	}

	public static function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			table_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			join_code VARCHAR(10) NOT NULL DEFAULT '',
			code_free_until DATETIME NULL,
			assigned_waiter BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY table_id (table_id),
			KEY status (status),
			KEY assigned_waiter (assigned_waiter)
		) {$charset};";

		dbDelta( $sql );
	}

	public static function now_mysql(): string {
		return gmdate( 'Y-m-d H:i:s' );
	}

	public static function random_code(): string {
		return str_pad( (string) wp_rand( 0, 9999 ), 4, '0', STR_PAD_LEFT );
	}

	public static function get_open_by_table( int $table_id ): ?array {
		global $wpdb;
		$table = self::table_name();
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE table_id=%d AND status='open' ORDER BY id DESC LIMIT 1", $table_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public static function create_for_table( int $table_id ): array {
		global $wpdb;
		$table = self::table_name();

		$code = self::random_code();
		$now  = self::now_mysql();
		$free_until = gmdate( 'Y-m-d H:i:s', time() + 120 ); // 2 minutes

		$wpdb->insert(
			$table,
			[
				'table_id'        => $table_id,
				'status'          => 'open',
				'join_code'       => $code,
				'code_free_until' => $free_until,
				'assigned_waiter' => null,
				'created_at'      => $now,
				'updated_at'      => $now,
			],
			[ '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
		);

		$id = (int) $wpdb->insert_id;
		return self::get_by_id( $id ) ?? [
			'id' => $id,
			'table_id' => $table_id,
			'status' => 'open',
			'join_code' => $code,
			'code_free_until' => $free_until,
			'assigned_waiter' => null,
		];
	}

	public static function get_by_id( int $id ): ?array {
		global $wpdb;
		$table = self::table_name();
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public static function list_open_unassigned(): array {
		global $wpdb;
		$table = self::table_name();
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE status='open' AND (assigned_waiter IS NULL OR assigned_waiter=0) ORDER BY id DESC LIMIT 50", ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public static function list_open_by_waiter( int $user_id ): array {
		global $wpdb;
		$table = self::table_name();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status='open' AND assigned_waiter=%d ORDER BY id DESC LIMIT 50", $user_id ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public static function assume( int $session_id, int $user_id ): bool {
		global $wpdb;
		$table = self::table_name();
		$now   = self::now_mysql();

		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET assigned_waiter=%d, updated_at=%s
				 WHERE id=%d AND status='open' AND (assigned_waiter IS NULL OR assigned_waiter=0)",
				$user_id, $now, $session_id
			)
		);

		return (bool) $updated;
	}

	public static function is_code_required( array $session ): bool {
		$free_until = $session['code_free_until'] ?? '';
		if ( empty( $free_until ) ) {
			return true;
		}
		$ts = strtotime( $free_until . ' UTC' );
		return ( time() > $ts );
	}

	public static function verify_code( array $session, string $code ): bool {
		$code = preg_replace( '/\D+/', '', $code );
		return ( $code !== '' && $code === (string) ( $session['join_code'] ?? '' ) );
	}

	/**
	 * Get table metadata from saved tables option.
	 *
	 * @param int $table_id
	 * @return array{name?:string,seats?:int,location?:string}
	 */
	public static function get_table_meta( int $table_id ): array {
		$tables = get_option( 'armo_df_tables', [] );
		if ( ! is_array( $tables ) ) {
			return [];
		}
		foreach ( $tables as $t ) {
			$tid = isset( $t['id'] ) ? (int) $t['id'] : 0;
			if ( $tid === $table_id ) {
				return [
					'name'     => isset( $t['name'] ) ? (string) $t['name'] : '',
					'seats'    => isset( $t['seats'] ) ? (int) $t['seats'] : 0,
					'location' => isset( $t['location'] ) ? (string) $t['location'] : '',
				];
			}
		}
		return [];
	}

}
