<?php
namespace Armo\DineFlow\Admin;

use Armo\DineFlow\Core\Capabilities;
use Armo\DineFlow\Core\Settings;
use Armo\DineFlow\Service\Qr;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Actions {

	public static function boot(): void {
		add_action( 'admin_post_armo_df_qr_download', [ __CLASS__, 'download_qr' ] );
	}

	public static function download_qr(): void {
		$table = isset( $_GET['table'] ) ? absint( $_GET['table'] ) : 0;
		check_admin_referer( 'armo_df_qr_' . $table );

		if ( ! current_user_can( Capabilities::CAP_MANAGE ) ) {
			wp_die( 'No autorizado' );
		}

		$tables = (array) get_option( 'armo_df_tables', [] );
		if ( empty( $tables[ $table ] ) ) {
			wp_die( 'Mesa no encontrada' );
		}

		$opts = Settings::get_all();
		$join = add_query_arg( [ 'table' => $table ], home_url( '/dineflow/join/' ) );
		$img  = Qr::image_url( $join, (int) $opts['qr_px'], (int) $opts['qr_margin'] );

		$resp = wp_remote_get( $img, [ 'timeout' => 20 ] );
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$type = (string) wp_remote_retrieve_header( $resp, 'content-type' );
		$body = (string) wp_remote_retrieve_body( $resp );

		if ( $code < 200 || $code >= 300 || false === stripos( $type, 'image/png' ) || empty( $body ) ) {
			wp_safe_redirect( $img );
			exit;
		}

		nocache_headers();
		header( 'Content-Type: image/png' );
		header( 'Content-Disposition: attachment; filename="table-' . $table . '-qr.png"' );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
