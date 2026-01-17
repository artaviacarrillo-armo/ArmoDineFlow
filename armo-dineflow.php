<?php
/**
 * Plugin Name:       Armo DineFlow
 * Description:       Dine In / Takeaway / Delivery flow + Waiter/KDS panels for restaurant operations.
 * Version:           1.2.0
 * Author:            Armo Laboratorio Creativo
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.6
 * Text Domain:       armo-dineflow
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Avoid "already defined" if something loads twice.
if ( ! defined( 'ARMO_DF_VERSION' ) ) {
	define( 'ARMO_DF_VERSION', '1.2.0' );
}
if ( ! defined( 'ARMO_DF_PATH' ) ) {
	define( 'ARMO_DF_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ARMO_DF_URL' ) ) {
	define( 'ARMO_DF_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Simple PSR-4 style autoloader (no Composer) for Armo\DineFlow\* classes.
 * Maps: Armo\DineFlow\Foo\Bar => /src/Foo/Bar.php
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'Armo\\DineFlow\\';

	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$rel  = substr( $class, strlen( $prefix ) );
	$rel  = str_replace( '\\', '/', $rel );
	$file = ARMO_DF_PATH . 'src/' . $rel . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Boot plugin once WordPress is ready.
add_action( 'plugins_loaded', function () {
	if ( class_exists( '\Armo\DineFlow\Plugin' ) ) {
		\Armo\DineFlow\Plugin::init();
	}
} );

// Activation/Deactivation hooks.
register_activation_hook( __FILE__, function () {
	if ( class_exists( '\Armo\DineFlow\Plugin' ) && method_exists( '\Armo\DineFlow\Plugin', 'activate' ) ) {
		\Armo\DineFlow\Plugin::activate();
	}
} );

register_deactivation_hook( __FILE__, function () {
	if ( class_exists( '\Armo\DineFlow\Plugin' ) && method_exists( '\Armo\DineFlow\Plugin', 'deactivate' ) ) {
		\Armo\DineFlow\Plugin::deactivate();
	}
} );
