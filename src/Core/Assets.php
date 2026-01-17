<?php
namespace Armo\DineFlow\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Assets {

	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'front_assets' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
	}

	public static function front_assets(): void {
		if ( get_query_var( 'armo_df_page' ) ) {
			wp_enqueue_style( 'armo-df-front', ARMO_DF_URL . 'assets/css/front.css', [], ARMO_DF_VERSION );
			$bg = Settings::get( 'ui_bg_color' );
			$tx = Settings::get( 'ui_text_color' );
			$cd = Settings::get( 'ui_card_color' );
			$css = ':root{--armo-df-bg:' . esc_attr( $bg ) . ';--armo-df-text:' . esc_attr( $tx ) . ';--armo-df-card:' . esc_attr( $cd ) . ';}';
			wp_add_inline_style( 'armo-df-front', $css );

// Optional: show Mode selector as modal on WooCommerce/menu pages when mode is not set.
if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_shop() || is_product() || is_product_category() || is_product_tag() ) ) {
	wp_enqueue_script( 'armo-df-front', ARMO_DF_URL . 'assets/js/front.js', [], ARMO_DF_VERSION, true );
	$payload = [
		'joinUrl'  => home_url( '/dineflow/join/' ),
		'takeaway' => (string) Settings::get( 'takeaway_url' ),
		'delivery' => home_url( '/dineflow/delivery/' ),
		'strings'  => [
			'title'    => \Armo\DineFlow\Core\I18nFront::t( 'mode_title' ),
			'dine_in'  => \Armo\DineFlow\Core\I18nFront::t( 'dine_in' ),
			'takeaway' => \Armo\DineFlow\Core\I18nFront::t( 'takeaway' ),
			'delivery' => \Armo\DineFlow\Core\I18nFront::t( 'delivery' ),
		],
	];
	wp_add_inline_script( 'armo-df-front', 'window.ArmoDineFlow=' . wp_json_encode( $payload ) . ';', 'before' );
	add_action( 'wp_footer', [ __CLASS__, 'render_mode_modal' ] );
		add_action( 'wp_footer', [ __CLASS__, 'render_join_modal' ] );
}

		}
	}

	public static function render_mode_modal(): void {
		?>
		<div id="armo-df-mode-modal" style="display:none">
			<div class="armo-df-modal-backdrop"></div>
			<div class="armo-df-modal">
				<div class="armo-df-modal-card">
					<h3 id="armo-df-modal-title"></h3>
					<div class="armo-df-modal-actions">
						<button type="button" class="armo-df-modal-btn" data-mode="dinein"></button>
						<button type="button" class="armo-df-modal-btn" data-mode="takeaway"></button>
						<button type="button" class="armo-df-modal-btn" data-mode="delivery"></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function admin_assets( string $hook ): void {
		if ( strpos( $hook, 'armo-dineflow' ) !== false ) {
			wp_enqueue_style( 'armo-df-admin', ARMO_DF_URL . 'assets/css/admin.css', [], ARMO_DF_VERSION );
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script( 'armo-df-admin', ARMO_DF_URL . 'assets/js/admin.js', [ 'wp-color-picker' ], ARMO_DF_VERSION, true );
		}
	}
}
