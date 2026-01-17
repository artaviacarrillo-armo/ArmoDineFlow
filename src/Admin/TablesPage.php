<?php
namespace Armo\DineFlow\Admin;

use Armo\DineFlow\Core\Settings;
use Armo\DineFlow\Service\Qr;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class TablesPage {

	public static function render(): void {
		$opts   = Settings::get_all();
		$tables = (array) get_option( 'armo_df_tables', [] );

		if ( isset( $_POST['armo_df_tables_nonce'] ) && wp_verify_nonce( sanitize_text_field( (string) $_POST['armo_df_tables_nonce'] ), 'armo_df_tables' ) ) {
			$action = sanitize_text_field( (string) ( $_POST['action_type'] ?? '' ) );

			if ( 'add' === $action ) {
				$name     = sanitize_text_field( (string) ( $_POST['table_name'] ?? '' ) );
				$seats    = absint( $_POST['table_seats'] ?? 0 );
				$location = sanitize_text_field( (string) ( $_POST['table_location'] ?? '' ) );

				if ( $name ) {
					$id = time() + wp_rand( 1, 99 );
					$tables[ $id ] = [ 'name' => $name, 'seats' => $seats, 'location' => $location ];
					update_option( 'armo_df_tables', $tables );
				}
			}

			if ( 'delete' === $action ) {
				$id = absint( $_POST['table_id'] ?? 0 );
				if ( $id && isset( $tables[ $id ] ) ) {
					unset( $tables[ $id ] );
					update_option( 'armo_df_tables', $tables );
				}
			}

			if ( 'edit' === $action ) {
				$id = absint( $_POST['table_id'] ?? 0 );
				if ( $id && isset( $tables[ $id ] ) ) {
					$name     = sanitize_text_field( (string) ( $_POST['table_name'] ?? '' ) );
					$seats    = absint( $_POST['table_seats'] ?? 0 );
					$location = sanitize_text_field( (string) ( $_POST['table_location'] ?? '' ) );
					$tables[ $id ] = [ 'name' => $name ?: ( $tables[ $id ]['name'] ?? '' ), 'seats' => $seats, 'location' => $location ];
					update_option( 'armo_df_tables', $tables );
				}
			}
		}

		$editing = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		?>
		<div class="wrap armo-df-admin">
			<h1>Tables (QR)</h1>

			<div class="armo-df-card-admin" style="margin:12px 0;">
				<h2 style="margin-top:0">Agregar mesa</h2>
				<form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
					<?php wp_nonce_field( 'armo_df_tables', 'armo_df_tables_nonce' ); ?>
					<input type="hidden" name="action_type" value="add" />
					<div>
						<label><strong>Nombre</strong></label><br/>
						<input type="text" name="table_name" placeholder="Mesa 1" class="regular-text" style="max-width:260px;" />
					</div>
					<div>
						<label><strong>Sillas</strong></label><br/>
						<input type="number" min="0" name="table_seats" placeholder="4" style="width:90px;" />
					</div>
					<div>
						<label><strong>Ubicación</strong></label><br/>
						<input type="text" name="table_location" placeholder="Terraza / Piso 1" class="regular-text" style="max-width:260px;" />
					</div>
					<button class="button button-primary">Agregar mesa</button>
				</form>
			</div>

			<?php if ( empty( $tables ) ) : ?>
				<p>Aún no hay mesas.</p>
			<?php else : ?>
				<div class="armo-df-grid-admin">
				<?php foreach ( $tables as $id => $row ) :
					$name  = is_array( $row ) ? (string) ( $row['name'] ?? '' ) : (string) $row;
					$seats = is_array( $row ) ? (int) ( $row['seats'] ?? 0 ) : 0;
					$loc   = is_array( $row ) ? (string) ( $row['location'] ?? '' ) : '';

					$join = add_query_arg( [ 'table' => (int) $id ], home_url( '/dineflow/join/' ) );
					$img  = Qr::image_url( $join, (int) $opts['qr_px'], (int) $opts['qr_margin'] );
					$dl   = $img;
					$edit_url = add_query_arg( [ 'page' => 'armo-dineflow-tables', 'edit' => (int) $id ], admin_url( 'admin.php' ) );
				?>
					<div class="armo-df-table-card">
						<div class="armo-df-table-card__head">
							<div>
								<div class="armo-df-table-title"><?php echo esc_html( $name ); ?></div>
								<div class="armo-df-table-meta">
									<?php if ( $seats ) : ?><span><strong>Sillas:</strong> <?php echo esc_html( (string) $seats ); ?></span><?php endif; ?>
									<?php if ( $loc ) : ?><span><strong>Ubicación:</strong> <?php echo esc_html( $loc ); ?></span><?php endif; ?>
								</div>
							</div>
							<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
								<a class="button" href="<?php echo esc_url( $edit_url ); ?>">Editar</a>
								<form method="post" style="display:inline">
									<?php wp_nonce_field( 'armo_df_tables', 'armo_df_tables_nonce' ); ?>
									<input type="hidden" name="action_type" value="delete" />
									<input type="hidden" name="table_id" value="<?php echo esc_attr( (string) $id ); ?>" />
									<button class="button button-link-delete" type="submit" onclick="return confirm('¿Borrar mesa?');">Borrar</button>
								</form>
							</div>
						</div>

						<div class="armo-df-table-card__body">
							<div class="armo-df-qr-wrap">
								<a href="<?php echo esc_url( $img ); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url( $img ); ?>" alt="QR" />
								</a>
							</div>
							<div>
								<div class="armo-df-link-row">
									<input type="text" readonly value="<?php echo esc_attr( $join ); ?>" class="regular-text" style="width:100%" />
									<button type="button" class="button armo-df-copy" data-copy="<?php echo esc_attr( $join ); ?>">Copiar</button>
								</div>
								<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
									<a class="button" href="<?php echo esc_url( $img ); ?>" target="_blank" rel="noopener">Abrir QR</a>
									<a class="button button-primary" href="<?php echo esc_url( $dl ); ?>" download="table-<?php echo esc_attr( (string) $id ); ?>-qr.png" target="_blank" rel="noopener">Descargar QR (PNG)</a>
								</div>
								<p class="description" style="margin-top:8px">“Descargar” abre el PNG directo (no proxy) para evitar bloqueos del servidor.</p>
							</div>
						</div>

						<?php if ( $editing === (int) $id ) : ?>
							<hr/>
							<div style="padding:12px 0 0">
								<h3 style="margin:0 0 8px">Editar mesa</h3>
								<form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
									<?php wp_nonce_field( 'armo_df_tables', 'armo_df_tables_nonce' ); ?>
									<input type="hidden" name="action_type" value="edit" />
									<input type="hidden" name="table_id" value="<?php echo esc_attr( (string) $id ); ?>" />
									<div>
										<label><strong>Nombre</strong></label><br/>
										<input type="text" name="table_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" style="max-width:260px;" />
									</div>
									<div>
										<label><strong>Sillas</strong></label><br/>
										<input type="number" min="0" name="table_seats" value="<?php echo esc_attr( (string) $seats ); ?>" style="width:90px;" />
									</div>
									<div>
										<label><strong>Ubicación</strong></label><br/>
										<input type="text" name="table_location" value="<?php echo esc_attr( $loc ); ?>" class="regular-text" style="max-width:260px;" />
									</div>
									<button class="button button-primary">Guardar</button>
									<a class="button" href="<?php echo esc_url( remove_query_arg( 'edit' ) ); ?>">Cerrar</a>
								</form>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<p class="description" style="margin-top:14px">Si acabas de instalar el plugin, ve a <strong>Ajustes → Enlaces permanentes</strong> y guarda para asegurar los rewrites.</p>
		</div>
		<?php
	}
}
