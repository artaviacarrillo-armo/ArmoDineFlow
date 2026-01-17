<?php
namespace Armo\DineFlow\Service;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Qr {

	/**
	 * Returns a remote QR image URL (PNG).
	 *
	 * We use QuickChart QR because it's stable and returns a direct PNG.
	 * (Google Chart QR has been inconsistent and can 404).
	 */
	public static function image_url( string $text, int $px = 600, int $margin = 2 ): string {
		$px     = max( 200, min( 1200, $px ) );
		$margin = max( 0, min( 10, $margin ) );

		$args = [
			'text'    => $text,
			'size'    => (string) $px,
			'margin'  => (string) $margin,
			'format'  => 'png',
			'ecLevel' => 'H',
		];

		// add_query_arg() will safely encode the text.
		return add_query_arg( $args, 'https://quickchart.io/qr' );
	}
}
