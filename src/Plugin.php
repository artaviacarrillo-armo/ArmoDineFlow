<?php
/**
 * Armo DineFlow
 * Plugin bootstrap (src/Plugin.php)
 *
 * Importante:
 * - Todo "register/boot" debe ocurrir dentro de Plugin::init()
 * - NO llames register() suelto fuera de la clase (evita estados inconsistentes, errores y “cache dependency”)
 */

declare(strict_types=1);

namespace Armo\DineFlow;

use Armo\DineFlow\Core\Assets;
use Armo\DineFlow\Core\Capabilities;
use Armo\DineFlow\Core\Rewrites;
use Armo\DineFlow\Core\Settings;
use Armo\DineFlow\Admin\Menu;
use Armo\DineFlow\Admin\Actions;
use Armo\DineFlow\Front\Ajax;
use Armo\DineFlow\Front\WooCapture;
use Armo\DineFlow\Service\Session;
use Armo\DineFlow\Service\SessionItems;
use Armo\DineFlow\Ajax\GetSessionOrder;

if ( ! defined('ABSPATH') ) {
	exit;
}

final class Plugin {

	/**
	 * Punto único de inicialización del plugin.
	 * Llamar esto desde armo-dineflow.php (archivo principal del plugin).
	 */
	public static function init(): void {

		// ---------------------------------------------------------------------
		// 1) Core (DB, settings, capacidades, rewrites, assets, shortcodes)
		// ---------------------------------------------------------------------
		Settings::boot();

		// Tablas (idempotente / safe)
		Session::create_table();
		SessionItems::create_table();

		Capabilities::boot();
		Rewrites::boot();
		Assets::boot();

		// Shortcodes
		\Armo\DineFlow\Core\Shortcodes::boot();

		// ---------------------------------------------------------------------
		// 2) Front / AJAX (hooks públicos)
		// ---------------------------------------------------------------------
		Ajax::boot();
		WooCapture::boot();

		// ✅ Registro del endpoint AJAX “core”
		// (No lo pongas fuera de init)
		GetSessionOrder::register();

		// ---------------------------------------------------------------------
		// 3) Admin (solo wp-admin)
		// ---------------------------------------------------------------------
		if ( is_admin() ) {
			Menu::boot();
			Actions::boot();
		}
	}
}
