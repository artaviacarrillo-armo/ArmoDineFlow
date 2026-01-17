<?php
namespace Armo\DineFlow\Admin;

use Armo\DineFlow\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Menu {

	public static function boot(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register' ] );
	}

	public static function register(): void {
		add_menu_page(
			'Armo DineFlow',
			'Armo DineFlow', 'manage_options',
			'armo-dineflow',
			[ Dashboard::class, 'render' ],
			'dashicons-food',
			58
		);

		add_submenu_page(
			'armo-dineflow',
			'Settings',
			'Settings', 'manage_options',
			'armo-dineflow-settings',
			[ SettingsPage::class, 'render' ]
		);

		add_submenu_page(
			'armo-dineflow',
			'Tables (QR)',
			'Tables (QR)', 'manage_options',
			'armo-dineflow-tables',
			[ TablesPage::class, 'render' ]
		);
	}
}
