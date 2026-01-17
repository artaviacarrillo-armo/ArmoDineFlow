<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ctx  = $GLOBALS['armo_df_ctx'] ?? [];
$opts = $ctx['opts'] ?? [];
$t    = $ctx['t'] ?? function( $k ){ return $k; };

$takeaway = ! empty( $opts['takeaway_url'] ) ? $opts['takeaway_url'] : home_url( '/' );
$delivery = ! empty( $opts['delivery_url'] ) ? $opts['delivery_url'] : home_url( '/' );
?>
<section class="armo-df-card armo-df-card-center">
	<h1><?php echo esc_html( $t('mode_title') ); ?></h1>

	<div class="armo-df-actions">
		<a class="armo-df-btn" href="<?php echo esc_url( home_url( '/dineflow/join/' ) ); ?>"><?php echo esc_html( $t('dine_in') ); ?></a>
		<a class="armo-df-btn" href="<?php echo esc_url( $takeaway ); ?>"><?php echo esc_html( $t('takeaway') ); ?></a>
		<a class="armo-df-btn" href="<?php echo esc_url( $delivery ); ?>"><?php echo esc_html( $t('delivery') ); ?></a>
	</div>
</section>
