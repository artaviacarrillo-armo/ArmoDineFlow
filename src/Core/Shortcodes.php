<?php
namespace Armo\DineFlow\Core;

class Shortcodes {

	public static function boot(): void {
		add_shortcode( 'armo_dineflow_mode', [ __CLASS__, 'mode' ] );
	}

	public static function mode(): string {
		$t = [I18nFront::class, 't'];
		$mode = isset( $_COOKIE['armo_df_mode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['armo_df_mode'] ) ) : '';
		if ( ! $mode ) {
			return '';
		}

		// Human labels (no parentheses) for the UI.
		$label = $mode;
		switch ( $mode ) {
			case 'dinein':
				$label = $t( 'mode_label_dinein' );
				break;
			case 'takeaway':
				$label = $t( 'mode_label_takeaway' );
				break;
			case 'delivery':
				$label = $t( 'mode_label_delivery' );
				break;
		}

		$human = sprintf( (string) $t( 'mode_you_are_in' ), $label );

		$table = isset( $_COOKIE['armo_df_table_id'] ) ? (int) $_COOKIE['armo_df_table_id'] : 0;
		$extra = '';
		if ( $table ) {
			$meta = \Armo\DineFlow\Service\Session::get_table_meta( $table );
			if ( ! empty( $meta['name'] ) ) {
				$extra = ' · ' . $meta['name'];
			}
		}

		return '<span class="armo-df-mode-pill">' . esc_html( $human . $extra ) . '</span>';
	}
}
