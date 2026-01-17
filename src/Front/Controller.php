<?php
namespace Armo\DineFlow\Front;

use Armo\DineFlow\Core\Settings;
use Armo\DineFlow\Core\I18nFront;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Controller {

	/**
	 * Back-compat helpers (older Rewrites.php versions call these methods).
	 */
	public static function render_waiter(): void {
		self::render( 'waiter' );
	}

	public static function render_join(): void {
		self::render( 'join' );
	}

	public static function render( string $page ): void {
		// Prevent page caching plugins from caching these dynamic panels.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}

		status_header( 200 );
		nocache_headers();

		$view = ARMO_DF_PATH . 'views/' . sanitize_file_name( $page ) . '.php';
		if ( ! file_exists( $view ) ) {
			wp_die( 'View not found' );
		}

		$ctx = [
			'delivery_providers' => \Armo\DineFlow\Core\Settings::get( 'delivery_providers' ),
			'opts' => Settings::get_all(),
			't'    => function( string $key ): string { return I18nFront::t( $key ); },
			// Helper: fetch table metadata (name/seats/location) for a given table id.
			'get_table_meta' => function( $table_id ): array {
				return \Armo\DineFlow\Service\Session::get_table_meta( (int) $table_id );
			},
		];

		// Make context available to the view.
		$GLOBALS['armo_df_ctx'] = $ctx;

		include ARMO_DF_PATH . 'views/layout.php';
	}
}
