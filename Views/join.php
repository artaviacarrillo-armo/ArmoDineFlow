<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Armo\DineFlow\Core\Settings;
use Armo\DineFlow\Service\Session;

$ctx = $GLOBALS['armo_df_ctx'] ?? [];
$t   = $ctx['t'] ?? function( $k ){ return $k; };

$table_id = isset( $_GET['table'] ) ? absint( $_GET['table'] ) : 0;

if ( ! $table_id ) {
	echo '<section class="armo-df-card"><h1>' . esc_html( $t('join') ) . '</h1><p>' . esc_html( $t('join_invalid') ) . '</p></section>';
	return;
}

// Force mode = Dine In whenever QR is scanned.
setcookie( 'armo_df_mode', 'dinein', time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
setcookie( 'armo_df_table', (string) $table_id, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

// Create or reuse session for this table.
$session = Session::get_open_by_table( $table_id );
if ( ! $session ) {
	$session = Session::create_for_table( $table_id );
}

// Cookie that marks this device as "joined" to this session.
$joined_cookie  = 'armo_df_joined_' . (int) $session['id'];
$already_joined = ! empty( $_COOKIE[ $joined_cookie ] );

	// Code logic.
	// If arriving from a QR scan (table param present), we treat the device as physically
	// at the table and allow joining without requiring the 4-digit code.
	// The join code is shown to share the same order with other devices.
	$from_qr       = isset( $_GET['table'] ) && $_GET['table'] !== '';
	$code_required = $from_qr ? false : Session::is_code_required( $session );
$err = '';

// If code is not required (2-min window) OR this device already joined, auto-join and redirect to menu/home.
if ( ! $code_required || $already_joined ) {
	// Auto-join (first 2 min window) or already joined on this device.
	setcookie( 'armo_df_joined_table', (string) $table_id, time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	setcookie( 'armo_df_joined_session', (string) $session['id'], time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	setcookie( 'armo_df_join_code', (string) $session['join_code'], time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

	$home = (string) \Armo\DineFlow\Core\Settings::get( 'takeaway_url', home_url( '/' ) );
	$welcome_title = $t( 'join_welcome_title' );
	$welcome_desc  = $t( 'join_welcome_desc' );
	$share_label   = $t( 'join_share_label' );
	$start_label   = $t( 'join_start' );

	?>
	<div class="armo-df-card" style="max-width:680px;margin:0 auto;">
		<h1 class="armo-df-title"><?php echo esc_html( $welcome_title ); ?></h1>
		<p class="armo-df-muted"><?php echo esc_html( $welcome_desc ); ?></p>

		<div style="margin-top:16px;padding:14px;border-radius:14px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
			<div class="armo-df-label"><?php echo esc_html( $share_label ); ?></div>
			<div style="font-size:28px;letter-spacing:10px;font-weight:800;margin-top:8px;"><?php echo esc_html( (string) $session['join_code'] ); ?></div>
			<div class="armo-df-muted" style="margin-top:10px;"><?php echo esc_html( $t( 'join_share_help' ) ); ?></div>
		</div>

		<div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
			<a class="armo-df-btn primary" href="<?php echo esc_url( $home ); ?>"><?php echo esc_html( $start_label ); ?></a>
		</div>
	</div>
	<?php
	return;
}
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['armo_df_join_nonce'] ) ) {
	$nonce = sanitize_text_field( (string) $_POST['armo_df_join_nonce'] );
	if ( wp_verify_nonce( $nonce, 'armo_df_join' ) ) {
		$code = sanitize_text_field( (string) ( $_POST['join_code'] ?? '' ) );
		if ( Session::verify_code( $session, $code ) ) {
			setcookie( $joined_cookie, '1', time() + ( 2 * HOUR_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
			$already_joined = true;
		} else {
			$err = $t('join_code_invalid');
		}
	}
}

// If allowed, redirect to Home/Menu (entrypoint).
if ( ! $code_required || $already_joined ) {
	$target = Settings::get( 'takeaway_url' );
	if ( empty( $target ) ) {
		$target = home_url( '/' );
	}
	wp_safe_redirect( esc_url_raw( $target ) );
	exit;
}

?>
<section class="armo-df-card" style="max-width:520px;margin:0 auto;">
	<h1><?php echo esc_html( $t('join') ); ?></h1>
	<p style="opacity:.85;margin-top:-6px;"><?php echo esc_html( $t('join_table') ); ?>: <?php echo esc_html( (string) $table_id ); ?></p>

	<p style="margin-top:14px;"><?php echo esc_html( $t('join_code_prompt') ); ?></p>

	<?php if ( $err ) : ?>
		<div style="background:rgba(255,0,0,.12);border:1px solid rgba(255,0,0,.25);padding:10px 12px;border-radius:12px;margin:10px 0;">
			<?php echo esc_html( $err ); ?>
		</div>
	<?php endif; ?>

	<form method="post" style="display:flex;gap:10px;align-items:center;margin-top:10px;">
		<?php wp_nonce_field( 'armo_df_join', 'armo_df_join_nonce' ); ?>
		<input type="text" name="join_code" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="0000" style="font-size:18px;letter-spacing:6px;width:140px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);color:var(--armo-df-text);" />
		<button class="armo-df-btn" type="submit"><?php echo esc_html( $t('join_enter') ); ?></button>
	</form>

	<p style="opacity:.75;margin-top:16px;"><?php echo esc_html( $t('join_help') ); ?></p>
</section>
