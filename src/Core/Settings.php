<?php
namespace Armo\DineFlow\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Settings {

	public const OPTION_KEY = 'armo_dineflow';

	public static function defaults(): array {
		return [
			'front_languageuage' => 'es',
			'restaurant_logo'=> 0,
			'ui_bg_color'    => '#0B1220',
			'ui_text_color'  => '#F8FAFC',
			'ui_card_color'  => '#111A2E',
			'takeaway_url'   => home_url( '/' ),
			'delivery_url'   => '', // legacy
			'qr_px'          => 600,
			'qr_margin'      => 2,
			'poll_seconds'   => 4,
			'delivery_providers' => self::default_providers(),
		];
	}

	public static function default_providers(): array {
		return [
			'doordash' => [ 'key'=>'doordash', 'name'=>'DoorDash', 'enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/doordash.com' ],
			'ubereats' => [ 'key'=>'ubereats', 'name'=>'Uber Eats', 'enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/ubereats.com' ],
			'grubhub'  => [ 'key'=>'grubhub',  'name'=>'Grubhub',  'enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/grubhub.com' ],
			'postmates'=> [ 'key'=>'postmates','name'=>'Postmates','enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/postmates.com' ],
			'instacart'=> [ 'key'=>'instacart','name'=>'Instacart','enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/instacart.com' ],
			'gopuff'   => [ 'key'=>'gopuff',   'name'=>'GoPuff',   'enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/gopuff.com' ],
			'seamless' => [ 'key'=>'seamless', 'name'=>'Seamless', 'enabled'=>0, 'url'=>'', 'display'=>'both', 'logo'=>'https://logo.clearbit.com/seamless.com' ],
		];
	}

	public static function get_all(): array {
		$opts = (array) get_option( self::OPTION_KEY, [] );
		return wp_parse_args( $opts, self::defaults() );
	}

	public static function get( string $key ) {
		$all = self::get_all();
		return $all[ $key ] ?? null;
	}

	public static function boot(): void {
		// Back-compat: some parts call Settings::boot() in Plugin bootstrap.
		self::register();
	}

	public static function register(): void {
		register_setting(
			'armo_dineflow',
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize' ],
				'default'           => self::defaults(),
			]
		);
	}

	public static function sanitize( $input ): array {
		$defaults = self::defaults();
		$in       = is_array( $input ) ? $input : [];
		$out      = $defaults;

		$out['front_language']      = in_array( (string) ( $in['front_language'] ?? 'es' ), [ 'es', 'en' ], true ) ? (string) $in['front_language'] : 'es';
		$out['restaurant_logo'] = absint( $in['restaurant_logo'] ?? 0 );

		foreach ( [ 'ui_bg_color', 'ui_text_color', 'ui_card_color' ] as $k ) {
			$val = sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
			$out[ $k ] = ( '' === $val ) ? $defaults[ $k ] : $val; // clear => defaults
		}

		$out['takeaway_url'] = esc_url_raw( (string) ( $in['takeaway_url'] ?? $defaults['takeaway_url'] ) );
		$out['delivery_url'] = esc_url_raw( (string) ( $in['delivery_url'] ?? '' ) ); // legacy

		$out['qr_px']        = max( 200, min( 1200, absint( $in['qr_px'] ?? $defaults['qr_px'] ) ) );
		$out['qr_margin']    = max( 0, min( 10, absint( $in['qr_margin'] ?? $defaults['qr_margin'] ) ) );
		$out['poll_seconds'] = max( 2, min( 30, absint( $in['poll_seconds'] ?? $defaults['poll_seconds'] ) ) );

		$prov_in  = is_array( $in['delivery_providers'] ?? null ) ? (array) $in['delivery_providers'] : [];
		$prov_def = self::default_providers();
		$prov_out = $prov_def;

		foreach ( $prov_def as $key => $p ) {
			$pi = (array) ( $prov_in[ $key ] ?? [] );
			$prov_out[ $key ]['enabled'] = ! empty( $pi['enabled'] ) ? 1 : 0;
			$prov_out[ $key ]['url']     = esc_url_raw( (string) ( $pi['url'] ?? '' ) );
			$display = (string) ( $pi['display'] ?? 'both' );
			$prov_out[ $key ]['display'] = in_array( $display, [ 'both', 'logo', 'name' ], true ) ? $display : 'both';
			$prov_out[ $key ]['logo']    = esc_url_raw( (string) ( $pi['logo'] ?? $p['logo'] ) );
			$prov_out[ $key ]['name']    = sanitize_text_field( (string) ( $pi['name'] ?? $p['name'] ) );
		}
		$out['delivery_providers'] = $prov_out;

		return $out;
	}
}
