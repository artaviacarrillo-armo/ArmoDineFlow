<?php
namespace Armo\DineFlow\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Dashboard {
	public static function render(): void {
		?>
		<div class="wrap">
			<h1>Armo DineFlow</h1>
			<p>v1.1.0 (reset estable). Aquí configuramos el front, QR y base de Waiter/KDS.</p>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=armo-dineflow-settings' ) ); ?>">Settings</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=armo-dineflow-tables' ) ); ?>">Tables (QR)</a></p>
			<hr/>
			<p><strong>Links front:</strong></p>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/dineflow/' ) ); ?>" target="_blank">/dineflow/</a></li>
				<li><a href="<?php echo esc_url( home_url( '/dineflow/waiter/' ) ); ?>" target="_blank">/dineflow/waiter/</a></li>
				<li><a href="<?php echo esc_url( home_url( '/dineflow/kitchen/' ) ); ?>" target="_blank">/dineflow/kitchen/</a></li>
			</ul>
		</div>
		<?php
	}
}
