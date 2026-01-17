<?php
namespace Armo\DineFlow\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class I18nFront {

	public static function t( string $key ): string {
		$lang = Settings::get( 'front_language' );
		$dict = self::dict();
		$fallback = $dict['es'][ $key ] ?? $key;
		return ( $dict[ $lang ][ $key ] ?? $fallback );
	}

	private static function dict(): array {
		return [
			'es' => [
				'mode_title' => '¿Cómo quieres ordenar?',
				'mode_you_are_in' => 'Estás en modo %s.',
				'mode_label_dinein' => 'Dine In',
				'mode_label_takeaway' => 'Takeaway',
				'mode_label_delivery' => 'Delivery',
				'dine_in' => 'Dine In (En el restaurante)',
				'takeaway' => 'Takeaway (Para recoger)',
				'delivery' => 'Delivery (A domicilio)',
				'waiter_panel' => 'Panel Meseros',
				'kitchen_panel' => 'Panel Cocina (KDS)',
				'no_orders' => 'Sin órdenes en cocina.',
				'waiter_current' => 'Mesero actual',
				'waiter_base' => 'Aquí mostraremos mesas activas, alertas y acciones (asumir, enviar a cocina, cerrar sesión).',
				'kitchen_base' => 'Órdenes enviadas por meseros. Cambia estado cuando esté listo.',
				'join_base' => 'En v1.2 conectaremos sesión + join code + múltiples clientes.',
				'waiter_my_tables' => 'Mis mesas activas',
				'waiter_none' => 'No tienes mesas asignadas aún.',
				'waiter_unassigned' => 'Mesas sin asignar',
				'waiter_assume' => 'Asumir',
				'waiter_no_unassigned' => 'No hay mesas pendientes.',
				'waiter_login' => 'Inicia sesión para asumir mesas.',
				'waiter_assume_fail' => 'No se pudo asumir la mesa.',
				'waiter_assume_not_logged' => 'Debes iniciar sesión.',
				'waiter_assume_cannot' => 'No se pudo (¿ya la tomó otro mesero?).',
				'waiter_open' => 'Abrir',
				'waiter_close' => 'Cerrar',
				'waiter_live_hint' => 'Actualización en vivo',
				'waiter_live_poll' => 'Actualizando cada %ss',
				'waiter_join_code' => 'Código para unirse',
				'waiter_no_items' => 'Aún no hay productos en esta orden.',
				'waiter_unit' => 'Unitario',
				'waiter_line_total' => 'Total línea',
				'waiter_addons' => 'Opciones',
				'waiter_modal_total' => 'Total',
				'join_welcome' => '¡Bienvenido! Ya puedes armar tu orden.',
				'join_code' => 'Código para invitar',
				'join_start' => 'Empezar a ordenar',
				'tables_seats_unit' => 'sillas',
			
			'join_welcome_title' => '¡Bienvenido a tu mesa!',
			'join_welcome_desc' => 'Ya estás en modo Dine In. Puedes empezar tu orden y compartir el código con tu grupo.',
			'join_share_label' => 'Código para unirse',
			'join_share_help' => 'Comparte este código de 4 dígitos para que otros teléfonos se unan a la misma orden.',
			'join_back_home' => 'Ir al Home',
			'join_code_prompt' => 'Ingresa el código de 4 dígitos para unirte a esta orden',
			'join_enter' => 'Entrar',
			'join_help' => 'Pide el código a la persona que inició la orden (o a tu mesero).',
			'join_code_invalid' => 'Código inválido. Verifica e inténtalo de nuevo.',
		],
			'en' => [
				'mode_title' => 'How would you like to order?',
				'mode_you_are_in' => 'You are in %s mode.',
				'mode_label_dinein' => 'Dine In',
				'mode_label_takeaway' => 'Takeaway',
				'mode_label_delivery' => 'Delivery',
				'dine_in' => 'Dine In (In restaurant)',
				'takeaway' => 'Takeaway (Pickup)',
				'delivery' => 'Delivery',
				'waiter_panel' => 'Waiter Panel',
				'kitchen_panel' => 'Kitchen Panel (KDS)',
				'no_orders' => 'No orders in kitchen.',
				'waiter_current' => 'Current waiter',
				'waiter_base' => 'We will show active tables, alerts and actions (claim, send to kitchen, close session).',
				'kitchen_base' => 'Orders sent by waiters. Update status when ready.',
				'join_base' => 'In v1.2 we will connect session + join code + multi-guest ordering.',
				'waiter_my_tables' => 'My active tables',
				'waiter_none' => 'You do not have assigned tables yet.',
				'waiter_unassigned' => 'Unassigned tables',
				'waiter_assume' => 'Assume',
				'waiter_no_unassigned' => 'No pending tables.',
				'waiter_login' => 'Log in to assume tables.',
				'waiter_assume_fail' => 'Could not assume table.',
				'waiter_assume_not_logged' => 'You must be logged in.',
				'waiter_assume_cannot' => 'Could not assume (maybe another waiter took it).',
				'waiter_open' => 'Open',
				'waiter_close' => 'Close',
				'waiter_live_hint' => 'Live updates',
				'waiter_live_poll' => 'Updating every %ss',
				'waiter_join_code' => 'Join code',
				'waiter_no_items' => 'No items yet in this order.',
				'waiter_unit' => 'Unit',
				'waiter_line_total' => 'Line total',
				'waiter_addons' => 'Options',
				'waiter_modal_total' => 'Total',
				'join_welcome' => 'Welcome! You can start your order now.',
				'join_code' => 'Invite code',
				'join_start' => 'Start ordering',
				'tables_seats_unit' => 'seats',
			
			'join_welcome_title' => 'Welcome to your table!',
			'join_welcome_desc' => 'You are now in Dine In mode. Start your order and share the code with your group.',
			'join_share_label' => 'Join code',
			'join_share_help' => 'Share this 4-digit code so other phones can join the same order.',
			'join_back_home' => 'Go to Home',
			'join_code_prompt' => 'Enter the 4-digit code to join this order',
			'join_enter' => 'Join',
			'join_help' => 'Ask the person who started the order (or your waiter) for the code.',
			'join_code_invalid' => 'Invalid code. Please try again.',
		],
		];
	}
}
