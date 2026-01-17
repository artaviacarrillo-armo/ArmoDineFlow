<?php
namespace Armo\DineFlow\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Capabilities {
	public const CAP_MANAGE = 'armo_df_manage';

	public static function boot(): void {
		// Ensure capabilities exist even if activation hook didn't run
		add_action( 'admin_init', [ __CLASS__, 'add_caps' ] );
	}

	public static function add_caps(): void {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( self::CAP_MANAGE ) ) {
			$role->add_cap( self::CAP_MANAGE );
		}
	}
}
