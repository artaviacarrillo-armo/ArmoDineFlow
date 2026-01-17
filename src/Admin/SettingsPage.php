<?php
namespace Armo\DineFlow\Admin;

use Armo\DineFlow\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SettingsPage {

	public static function render(): void {
		$opts = Settings::get_all();

		?>
		<div class="wrap">
			<h1>Armo DineFlow Settings</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'armo_dineflow' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Logo del restaurante</th>
						<td>
							<div style="display:flex;gap:12px;align-items:center;">
								<div id="armo-df-logo-preview">
									<?php
									$logo_id = (int) ( $opts['restaurant_logo_id'] ?? 0 );
									if ( $logo_id ) {
										echo wp_get_attachment_image( $logo_id, [ 120, 120 ], false, [ 'style' => 'max-width:120px;height:auto;border:1px solid #ddd;padding:8px;background:#fff;border-radius:12px;' ] );
									} else {
										echo '<div style="width:120px;height:120px;border:1px dashed #bbb;border-radius:12px;display:flex;align-items:center;justify-content:center;">Sin logo</div>';
									}
									?>
								</div>
								<div>
									<input type="hidden" id="armo-df-logo-id" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[restaurant_logo_id]" value="<?php echo esc_attr( $logo_id ); ?>" />
									<button type="button" class="button" id="armo-df-logo-pick">Elegir logo</button>
									<button type="button" class="button" id="armo-df-logo-remove">Quitar</button>
									<p class="description">Este logo se mostrará en el front (Mode/Join/Waiter/KDS).</p>
								</div>
							</div>
						</td>
					</tr>

					<tr>
						<th scope="row">Idioma del Front</th>
						<td>
							<select name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[front_language]">
								<option value="es" <?php selected( (string) $opts['front_language'], 'es' ); ?>>Español</option>
								<option value="en" <?php selected( (string) $opts['front_language'], 'en' ); ?>>English</option>
							</select>
							<p class="description">Afecta todo el texto del front del plugin (independiente del idioma del sitio).</p>
						</td>
					</tr>

					<tr>
						<th scope="row">UI Background</th>
						<td><input type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[ui_bg_color]" value="<?php echo esc_attr( $opts['ui_bg_color'] ); ?>" class="regular-text armo-df-color" /></td>
					</tr>
					<tr>
						<th scope="row">UI Text Color</th>
						<td><input type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[ui_text_color]" value="<?php echo esc_attr( $opts['ui_text_color'] ); ?>" class="regular-text armo-df-color" /></td>
					</tr>
					<tr>
						<th scope="row">UI Card Background</th>
						<td><input type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[ui_card_color]" value="<?php echo esc_attr( $opts['ui_card_color'] ); ?>" class="regular-text armo-df-color" /></td>
					</tr>

					<tr>
						<th scope="row">Takeaway URL</th>
						<td><input type="url" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[takeaway_url]" value="<?php echo esc_attr( $opts['takeaway_url'] ); ?>" class="regular-text" placeholder="https://tusitio.com/menu/" /><p class="description">URL de tu menú/tienda en tu sitio (WooCommerce) para pedidos para recoger. Ej: /menu/ o /shop/.</p></td>
					</tr>
					<tr>
						<th scope="row">QR Size (px)</th>
						<td><input type="number" min="200" max="1200" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[qr_px]" value="<?php echo esc_attr( (int) $opts['qr_px'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row">QR Margin</th>
						<td><input type="number" min="0" max="10" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[qr_margin]" value="<?php echo esc_attr( (int) $opts['qr_margin'] ); ?>" /></td>
					</tr>
				
	<tr>
		<th scope="row">Delivery (Plataformas)</th>
		<td>
			<p class="description">Activa una o más plataformas. Si hay más de una activa, el usuario podrá elegir.</p>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr>
						<th style="width:70px">Activa</th>
						<th style="width:160px">Plataforma</th>
						<th>URL</th>
						<th style="width:160px">Mostrar</th>
						<th style="width:260px">Logo (opcional)</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( (array) $opts['delivery_providers'] as $key => $p ) : ?>
					<tr>
						<td><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[delivery_providers][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $p['enabled'] ) ); ?> /></td>
						<td><input type="text" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[delivery_providers][<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( $p['name'] ); ?>" class="regular-text" style="max-width:150px" /></td>
						<td><input type="url" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[delivery_providers][<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $p['url'] ); ?>" class="regular-text" style="max-width:420px" placeholder="https://..." /></td>
						<td>
							<select name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[delivery_providers][<?php echo esc_attr( $key ); ?>][display]">
								<option value="both" <?php selected( $p['display'], 'both' ); ?>>Logo + Nombre</option>
								<option value="logo" <?php selected( $p['display'], 'logo' ); ?>>Solo Logo</option>
								<option value="name" <?php selected( $p['display'], 'name' ); ?>>Solo Nombre</option>
							</select>
						</td>
						<td><input type="url" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[delivery_providers][<?php echo esc_attr( $key ); ?>][logo]" value="<?php echo esc_attr( $p['logo'] ); ?>" class="regular-text" style="max-width:240px" placeholder="https://logo..." /></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</td>
	</tr>

	<tr style="display:none">
		<th scope="row">Delivery URL (legacy)</th>
		<td><input type="url" name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[delivery_url]" value="<?php echo esc_attr( $opts['delivery_url'] ); ?>" class="regular-text" /></td>
	</tr>

</table>


				<?php submit_button(); ?>
			</form>
		</div>

		<script>
		(function(){
			var frame;
			function byId(id){ return document.getElementById(id); }
			var pick = byId('armo-df-logo-pick');
			var remove = byId('armo-df-logo-remove');
			var input = byId('armo-df-logo-id');
			var preview = byId('armo-df-logo-preview');

			if(pick){
				pick.addEventListener('click', function(e){
					e.preventDefault();
					if(frame){ frame.open(); return; }
					frame = wp.media({ title: 'Elegir logo', button: { text: 'Usar logo' }, multiple: false });
					frame.on('select', function(){
						var att = frame.state().get('selection').first().toJSON();
						input.value = att.id;
						preview.innerHTML = '<img style="max-width:120px;height:auto;border:1px solid #ddd;padding:8px;background:#fff;border-radius:12px;" src="'+att.url+'" />';
					});
					frame.open();
				});
			}
			if(remove){
				remove.addEventListener('click', function(e){
					e.preventDefault();
					input.value = '0';
					preview.innerHTML = '<div style="width:120px;height:120px;border:1px dashed #bbb;border-radius:12px;display:flex;align-items:center;justify-content:center;">Sin logo</div>';
				});
			}
		})();
		</script>
		<?php
	}
}
