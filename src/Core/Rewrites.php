<?php
namespace Armo\DineFlow\Core;

use Armo\DineFlow\Front\Controller;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Rewrites {

	public static function boot(): void {
		add_action( 'init', [ __CLASS__, 'add_rules' ] );
		add_filter( 'query_vars', [ __CLASS__, 'query_vars' ] );
		add_action( 'template_redirect', [ __CLASS__, 'template_redirect' ] );
	}

	public static function add_rules(): void {
		add_rewrite_rule( '^dineflow/waiter/?$', 'index.php?armo_df_page=waiter', 'top' );
		add_rewrite_rule( '^dineflow/kitchen/?$', 'index.php?armo_df_page=kitchen', 'top' );
		add_rewrite_rule( '^dineflow/join/?$', 'index.php?armo_df_page=join', 'top' );
		add_rewrite_rule( '^dineflow/mode/?$', 'index.php?armo_df_page=mode', 'top' );
	}

	public static function query_vars( array $vars ): array {
		$vars[] = 'armo_df_page';
		return $vars;
	}

	public static function template_redirect(): void {
		$page = get_query_var( 'armo_df_page' );
		if ( ! $page ) { return; }

		// These endpoints are highly dynamic; prevent page/object caching (LiteSpeed, etc.).
		if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) { define( 'DONOTCACHEOBJECT', true ); }
		if ( function_exists( 'nocache_headers' ) ) { nocache_headers(); }
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		switch ( $page ) {
			case 'waiter':
				Controller::render('waiter');
				exit;
			case 'kitchen':
				Controller::render('kitchen');
				exit;
			case 'join':
				Controller::render('join');
				exit;
			case 'mode':
				Controller::render('mode');
				exit;
		}
	}
}
