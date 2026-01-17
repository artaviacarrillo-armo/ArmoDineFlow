<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ctx  = $GLOBALS['armo_df_ctx'] ?? [];
$opts = $ctx['opts'] ?? [];
$t    = $ctx['t'] ?? function( $k ){ return $k; };

$page = get_query_var( 'armo_df_page' );
$view = ARMO_DF_PATH . 'views/' . sanitize_file_name( $page ) . '.php';

$logo_id = (int) ( $opts['restaurant_logo_id'] ?? 0 );
$logo_html = $logo_id ? wp_get_attachment_image( $logo_id, [ 120, 120 ], false, [ 'class' => 'armo-df-logo' ] ) : '';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<?php wp_head(); ?>
</head>
<body class="armo-df-body">
	<div class="armo-df-shell">
		<div class="armo-df-top">
			<div class="armo-df-top-inner">
				<div class="armo-df-brand">
					<?php if ( $logo_html ) : ?>
						<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<main class="armo-df-main">
			<?php include $view; ?>
		</main>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
