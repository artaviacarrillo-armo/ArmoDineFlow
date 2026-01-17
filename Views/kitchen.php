<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ctx  = $GLOBALS['armo_df_ctx'] ?? [];
$t    = $ctx['t'] ?? function( $k ){ return $k; };
?>
<section class="armo-df-card">
	<h1><?php echo esc_html( $t('kitchen_panel') ); ?></h1>
	<p style="opacity:.85;"><?php echo esc_html( $t('kitchen_base') ); ?></p>
	<p style="opacity:.85;"><?php echo esc_html( $t('no_orders') ); ?></p>
</section>
