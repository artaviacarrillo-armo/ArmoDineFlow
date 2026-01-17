<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$ctx = $GLOBALS['armo_df_ctx'] ?? [];
$t   = $ctx['t'] ?? function( $k ){ return $k; };
$providers = $ctx['delivery_providers'] ?? [];
$active = array_values( array_filter( (array) $providers, function($p){
	return ! empty($p['enabled']) && ! empty($p['url']);
} ) );
?>
<section class="armo-df-card">
	<h1><?php echo esc_html( $t('delivery') ); ?></h1>

	<?php if ( empty( $active ) ) : ?>
		<p style="opacity:.85;"><?php echo esc_html( $t('delivery_none') ); ?></p>
	<?php elseif ( 1 === count( $active ) ) : ?>
		<?php wp_safe_redirect( esc_url_raw( $active[0]['url'] ) ); exit; ?>
	<?php else : ?>
		<p style="opacity:.85;"><?php echo esc_html( $t('delivery_choose') ); ?></p>
		<div class="armo-df-grid">
			<?php foreach ( $active as $p ) : ?>
				<a class="armo-df-provider" href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" rel="noopener">
					<?php
						$mode = $p['display'] ?? 'both';
						$logo = $p['logo'] ?? '';
						$name = $p['name'] ?? '';
						if ( ( 'logo' === $mode || 'both' === $mode ) && $logo ) {
							echo '<img src="' . esc_url( $logo ) . '" alt="" />';
						}
						if ( 'name' === $mode || 'both' === $mode ) {
							echo '<span>' . esc_html( $name ) . '</span>';
						}
					?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>

<style>
.armo-df-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
.armo-df-provider{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:var(--armo-df-text);text-decoration:none}
.armo-df-provider:hover{background:rgba(255,255,255,.11)}
.armo-df-provider img{width:28px;height:28px;object-fit:contain}
.armo-df-provider span{font-weight:800}
</style>
