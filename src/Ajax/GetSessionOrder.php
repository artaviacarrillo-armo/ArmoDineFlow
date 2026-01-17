<?php
/**
 * Armo DineFlow — GetSessionOrder AJAX endpoint
 *
 * Acción AJAX:
 * - action=armo_df_get_session_order
 *
 * POST:
 * - session_id (int) requerido
 * - statuses (array|string) opcional
 * - nonce (string) opcional (si habilitas verify_nonce_or_bail)
 *
 * Respuesta:
 * - success:true  { session_id, items[], totals{} ... }
 * - success:false { message, detail?, file?, line? }
 */

declare(strict_types=1);

namespace Armo\DineFlow\Ajax;

if ( ! defined('ABSPATH') ) { exit; }

final class GetSessionOrder {

	// =========================================================================
	// 0) REGISTRO
	// =========================================================================

	public static function register(): void {
		add_action('wp_ajax_armo_df_get_session_order', [__CLASS__, 'handle']);
		add_action('wp_ajax_nopriv_armo_df_get_session_order', [__CLASS__, 'handle']);
	}

	// =========================================================================
	// 1) HANDLER
	// =========================================================================

	public static function handle(): void {

		// Si ya tienes nonce estándar, descomenta:
		// self::verify_nonce_or_bail();

		try {
			$session_id = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
			if ($session_id <= 0) {
				wp_send_json_error(['message' => 'invalid_session_id'], 400);
			}

			$statuses = self::normalize_statuses($_POST['statuses'] ?? null);

			$args = [];
			if (!empty($statuses)) {
				$args['statuses'] = $statuses;
			}

			// Resolver “núcleo” (JSON estable)
			$payload = \Armo\DineFlow\Service\OrderResolver::resolve_session($session_id, $args);

			wp_send_json_success($payload);

		} catch (\Throwable $e) {
			// Importante: devolvemos el error real para no adivinar (debug duro).
			wp_send_json_error([
				'message' => 'server_error',
				'detail'  => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			], 500);
		}
	}

	// =========================================================================
	// 2) HELPERS
	// =========================================================================

	/**
	 * statuses puede venir como:
	 * - array: ['pending_waiter','sent_kitchen']
	 * - string: 'pending_waiter'
	 * - null
	 *
	 * @param mixed $raw
	 * @return string[]
	 */
	private static function normalize_statuses($raw): array {
		if (is_array($raw)) {
			$out = array_map('sanitize_text_field', $raw);
			$out = array_values(array_filter($out, static function($v) {
				return is_string($v) && $v !== '';
			}));
			return $out;
		}

		if (is_string($raw) && $raw !== '') {
			return [sanitize_text_field($raw)];
		}

		return [];
	}

	// =========================================================================
	// 3) NONCE (OPCIONAL)
	// =========================================================================

	private static function verify_nonce_or_bail(): void {
		$nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
		if ($nonce === '' || ! wp_verify_nonce($nonce, 'armo_df_nonce')) {
			wp_send_json_error(['message' => 'invalid_nonce'], 403);
		}
	}
}
