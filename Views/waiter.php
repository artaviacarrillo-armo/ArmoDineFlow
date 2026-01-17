<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Armo\DineFlow\Service\Session;

$ctx = $GLOBALS['armo_df_ctx'] ?? [];
$t   = $ctx['t'] ?? function( $k ){ return $k; };
$get_meta = $ctx['get_table_meta'] ?? function( $table_id ) { return []; };
$opts = $ctx['opts'] ?? [];
$poll = isset( $opts['poll_seconds'] ) ? max( 2, (int) $opts['poll_seconds'] ) : 4;

$user = wp_get_current_user();
$waiter_name = $user && $user->exists() ? $user->display_name : '';

$unassigned = Session::list_open_unassigned();
$mine       = is_user_logged_in() ? Session::list_open_by_waiter( get_current_user_id() ) : [];
?>
<section class="armo-df-card">
	<h1><?php echo esc_html( $t('waiter_panel') ); ?></h1>
	<p style="opacity:.85;margin-top:-6px;"><?php echo esc_html( $t('waiter_current') ); ?>: <strong><?php echo esc_html( $waiter_name ); ?></strong></p>

	<div class="armo-df-waiter-sections" style="margin-top:16px;">
		<div class="armo-df-card armo-df-card-sub">
			<h2><?php echo esc_html( $t('waiter_my_tables') ); ?></h2>

			<?php if ( empty( $mine ) ) : ?>
				<p style="opacity:.8;"><?php echo esc_html( $t('waiter_none') ); ?></p>
			<?php else : ?>
				<div class="armo-df-cards">
					<?php foreach ( $mine as $s ) :
						$tid  = (int) ( $s['table_id'] ?? 0 );
						$meta = is_callable( $get_meta ) ? (array) $get_meta( $tid ) : [];
						$name = isset( $meta['name'] ) && $meta['name'] !== '' ? (string) $meta['name'] : ( 'Mesa ' . $tid );
						$loc  = isset( $meta['location'] ) ? (string) $meta['location'] : '';
						$seats= isset( $meta['seats'] ) ? (int) $meta['seats'] : 0;
					?>
						<div class="armo-df-table-card" data-session="<?php echo esc_attr( (string) $s['id'] ); ?>">
							<div class="armo-df-table-card__top">
								<div>
									<div class="armo-df-table-title"><?php echo esc_html( $name ); ?></div>
									<div class="armo-df-table-meta">
										<?php if ( $loc ) : ?><span><?php echo esc_html( $loc ); ?></span><?php endif; ?>
										<?php if ( $seats ) : ?><span><?php echo esc_html( $seats ); ?> sillas</span><?php endif; ?>
										<span>#<?php echo esc_html( (string) $s['id'] ); ?></span>
									</div>
								</div>
								<button class="armo-df-btn armo-df-open-session" type="button" data-session="<?php echo esc_attr( (string) $s['id'] ); ?>">
									<?php echo esc_html( $t('waiter_open') ); ?>
								</button>
							</div>
							<div class="armo-df-table-card__body">
								<div class="armo-df-muted"><?php echo esc_html( $t('waiter_live_hint') ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="armo-df-card armo-df-card-sub">
			<h2><?php echo esc_html( $t('waiter_unassigned') ); ?></h2>

			<?php if ( empty( $unassigned ) ) : ?>
				<p style="opacity:.8;"><?php echo esc_html( $t('waiter_no_unassigned') ); ?></p>
			<?php else : ?>
				<div class="armo-df-cards">
					<?php foreach ( $unassigned as $s ) :
						$tid  = (int) ( $s['table_id'] ?? 0 );
						$meta = is_callable( $get_meta ) ? (array) $get_meta( $tid ) : [];
						$name = isset( $meta['name'] ) && $meta['name'] !== '' ? (string) $meta['name'] : ( 'Mesa ' . $tid );
						$loc  = isset( $meta['location'] ) ? (string) $meta['location'] : '';
						$seats= isset( $meta['seats'] ) ? (int) $meta['seats'] : 0;
					?>
						<div class="armo-df-table-card">
							<div class="armo-df-table-card__top">
								<div>
									<div class="armo-df-table-title"><?php echo esc_html( $name ); ?></div>
									<div class="armo-df-table-meta">
										<?php if ( $loc ) : ?><span><?php echo esc_html( $loc ); ?></span><?php endif; ?>
										<?php if ( $seats ) : ?><span><?php echo esc_html( $seats ); ?> sillas</span><?php endif; ?>
										<span>#<?php echo esc_html( (string) $s['id'] ); ?></span>
									</div>
								</div>
								<?php if ( is_user_logged_in() ) : ?>
									<div style="display:flex;gap:10px;align-items:center;">
										<button class="armo-df-btn armo-df-open-session" type="button" data-session="<?php echo esc_attr( (string) $s['id'] ); ?>"><?php echo esc_html( $t('waiter_open') ); ?></button>
										<button class="armo-df-btn armo-df-assume" type="button" data-session="<?php echo esc_attr( (string) $s['id'] ); ?>"><?php echo esc_html( $t('waiter_assume') ); ?></button>
									</div>
								<?php else : ?>
									<span style="opacity:.8;"><?php echo esc_html( $t('waiter_login') ); ?></span>
								<?php endif; ?>
							</div>
							<div class="armo-df-table-card__body">
								<div class="armo-df-muted"><?php echo esc_html( $t('waiter_pending') ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- Detail Drawer -->
<div class="armo-df-drawer" id="armoDfDrawer" aria-hidden="true">
	<div class="armo-df-drawer__panel">
		<div class="armo-df-drawer__header">
			<div>
				<div id="armoDfDrawerTitle" class="armo-df-table-title">—</div>
				<div id="armoDfDrawerMeta" class="armo-df-table-meta"></div>
			</div>
			<div class="armo-df-row" style="gap:10px;">
				<button class="armo-df-btn armo-df-btn--danger" type="button" id="armoDfCancelOrder"><?php echo esc_html( $t('waiter_cancel_order') ); ?></button>
				<button class="armo-df-btn" type="button" id="armoDfDrawerClose"><?php echo esc_html( $t('waiter_close') ); ?></button>
			</div>
		</div>

		<div class="armo-df-drawer__content">
			<div class="armo-df-row" style="justify-content:space-between;align-items:center;">
				<div class="armo-df-muted"><?php echo esc_html( $t('waiter_live_poll') ); ?>: <?php echo esc_html( (string) $poll ); ?>s</div>
				<div class="armo-df-muted"><?php echo esc_html( $t('waiter_join_code') ); ?>: <strong id="armoDfJoinCode">—</strong></div>
			</div>

			<div id="armoDfItems" class="armo-df-items"></div>
		</div>
	</div>
</div>

<script>
(function(){
	const POLL_MS = <?php echo (int) $poll; ?> * 1000;
	const ajaxUrl = '<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
	const nonce   = '<?php echo esc_js( wp_create_nonce('armo_df_waiter') ); ?>';

	const drawer  = document.getElementById('armoDfDrawer');
	const closeBtn= document.getElementById('armoDfDrawerClose');
	const titleEl = document.getElementById('armoDfDrawerTitle');
	const metaEl  = document.getElementById('armoDfDrawerMeta');
	const joinEl  = document.getElementById('armoDfJoinCode');
	const itemsEl = document.getElementById('armoDfItems');

	let currentSession = null;
	let timer = null;

	function openDrawer(){
		drawer.classList.add('is-open');
		drawer.setAttribute('aria-hidden','false');
	}
	function closeDrawer(){
		drawer.classList.remove('is-open');
		drawer.setAttribute('aria-hidden','true');
		currentSession = null;
		if(timer){ clearInterval(timer); timer=null; }
	}
	closeBtn && closeBtn.addEventListener('click', closeDrawer);

	const cancelBtn = document.getElementById('armoDfCancelOrder');
	cancelBtn && cancelBtn.addEventListener('click', async () => {
		if (!currentSession) return;
		const msg = <?php echo json_encode( esc_html__( '¿Cancelar esta orden? Esto borrará todos los productos.', 'armo-dineflow' ) ); ?>;
		if (!confirm(msg)) return;
		try {
				const r = await fetch(ajaxUrl, {
					method: 'POST',
					cache: 'no-store',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
						'Cache-Control': 'no-cache'
					},
				body: new URLSearchParams({
					action: 'armo_df_cancel_order',
					nonce: nonce,
					session_id: String(currentSession)
				})
			});
			const j = await r.json();
			if (!j || !j.success) {
				alert((j && j.data && j.data.message) ? j.data.message : 'No fue posible cancelar.');
				return;
			}
			closeDrawer();
			// Reload to refresh lists
			window.location.reload();
		} catch (e) {
			console.error(e);
			alert('Error al cancelar.');
		}
	});
	drawer && drawer.addEventListener('click', (e)=>{ if(e.target === drawer){ closeDrawer(); } });

	async function fetchSession(sessionId){
		const form = new FormData();
		form.append('action','armo_df_waiter_get_session');
		form.append('nonce', nonce);
		form.append('session_id', sessionId);

		let res;
		try {
			res = await fetch(ajaxUrl, {
				method:'POST',
				credentials:'same-origin',
				cache: 'no-store',
				headers: { 'Cache-Control': 'no-cache' },
				body: form
			});
		} catch (e) {
			itemsEl.innerHTML = '<div class="armo-df-muted">Error de red al consultar la orden.</div>';
			return null;
		}

		let data = null;
		try {
			data = await res.json();
		} catch (e) {
			itemsEl.innerHTML = '<div class="armo-df-muted">Respuesta inválida del servidor (no JSON).</div>';
			return null;
		}

		if(!data || !data.success){
			const msg = (data && data.data && data.data.message) ? data.data.message : 'Error al consultar la orden.';
			itemsEl.innerHTML = '<div class="armo-df-muted">'+escapeHtml(msg)+'</div>';
			return null;
		}
		return data.data;
	}

	function renderItems(items, totals){
		if(!items || !items.length){
			itemsEl.innerHTML = '<div class="armo-df-muted"><?php echo esc_js( $t('waiter_no_items') ); ?></div>';
			return;
		}
		const lines = items.map(it=>{
			const title = (it.title || ('#'+it.product_id));
			const pricing = it.pricing || {};
			const unit = pricing.unit_total || '';
			const lineTotal = pricing.line_total || '';
			const addons = Array.isArray(pricing.addons) ? pricing.addons : [];
			const addonsHtml = addons.length ? (`<div class="armo-df-item__addons">` + addons.map(a=>{
				const label = a.label || a.key || '<?php echo esc_js( $t('addon') ); ?>';
				const value = a.value ? `: ${a.value}` : '';
				const price = a.price ? ` (+${a.price})` : '';
				return `<div>• ${escapeHtml(label)}${escapeHtml(value)}${escapeHtml(price)}</div>`;
			}).join('') + `</div>`) : '';
			return `<div class="armo-df-item">
				<div class="armo-df-item__left">
					<div class="armo-df-item__title">${escapeHtml(title)}</div>
					<div class="armo-df-item__meta">ID: ${it.product_id}${unit ? ' · ' + escapeHtml(unit) : ''}</div>
					${addonsHtml}
				</div>
				<div class="armo-df-item__right">
					<div class="armo-df-item__qty">x${it.qty}</div>
					<button class="armo-df-item__remove" type="button" data-item-id="${it.id}" title="<?php echo esc_js( esc_html__( 'Eliminar', 'armo-dineflow' ) ); ?>">×</button>
					${lineTotal ? `<div class="armo-df-item__line">${escapeHtml(lineTotal)}</div>` : ''}
				</div>
			</div>`;
		}).join('');

		const totalHtml = (totals && totals.total_text) ? (`
			<div class="armo-df-total">
				<div><?php echo esc_js( $t('total') ); ?></div>
				<div style="font-weight:700;">${escapeHtml(totals.total_text)}</div>
			</div>
		`) : '';

		itemsEl.innerHTML = lines + totalHtml;
	}

	// Remove a single item from the session (physical delete)
	itemsEl && itemsEl.addEventListener('click', async (e) => {
		const btn = e.target && e.target.closest ? e.target.closest('.armo-df-item__remove') : null;
		if (!btn) return;
		if (!currentSession) return;
		const itemId = parseInt(btn.getAttribute('data-item-id') || '0', 10);
		if (!itemId) return;
		if (!confirm(<?php echo json_encode( esc_html__( '¿Eliminar este producto de la orden?', 'armo-dineflow' ) ); ?>)) return;
		btn.disabled = true;
		try {
			const fd = new FormData();
			fd.append('action', 'armo_df_waiter_remove_item');
			fd.append('nonce', nonce);
			fd.append('session_id', String(currentSession));
			fd.append('item_id', String(itemId));
				const res = await fetch(ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					cache: 'no-store',
					headers: { 'Cache-Control': 'no-cache' },
					body: fd
				});
			const json = await res.json();
			if (!json || !json.success) {
				alert((json && json.data && json.data.message) ? json.data.message : 'Error');
				return;
			}
			await refresh();
		} finally {
			btn.disabled = false;
		}
	});

	function escapeHtml(str){
		return String(str)
			.replaceAll('&','&amp;')
			.replaceAll('<','&lt;')
			.replaceAll('>','&gt;')
			.replaceAll('"','&quot;')
			.replaceAll("'","&#039;");
	}

	async function refresh(){
		if(!currentSession) return;
		const payload = await fetchSession(currentSession);
		if(!payload) return;

		const table = payload.table || {};
		const session = payload.session || {};
		const name = table.name ? table.name : ('Mesa ' + (session.table_id || ''));
		titleEl.textContent = name;

		const parts = [];
		if(table.location) parts.push(table.location);
		if(table.seats) parts.push(table.seats + ' sillas');
		if(session.id) parts.push('#' + session.id);
		metaEl.innerHTML = parts.map(p=>`<span>${escapeHtml(p)}</span>`).join('');
		joinEl.textContent = session.join_code ? session.join_code : '—';

		renderItems(payload.items || [], payload.totals || null);
	}

	document.querySelectorAll('.armo-df-open-session').forEach(btn=>{
		btn.addEventListener('click', async ()=>{
			const sid = btn.getAttribute('data-session');
			if(!sid) return;
			currentSession = sid;
			openDrawer();
			await refresh();
			if(timer){ clearInterval(timer); }
			timer = setInterval(refresh, POLL_MS);
		});
	});

	document.querySelectorAll('.armo-df-assume').forEach(btn=>{
		btn.addEventListener('click', async ()=>{
			btn.disabled = true;
			try{
				const form = new FormData();
				form.append('action','armo_df_assume_session');
				form.append('nonce', nonce);
				form.append('session_id', btn.getAttribute('data-session') || '');
					const res = await fetch(ajaxUrl, {
						method:'POST',
						credentials:'same-origin',
						cache: 'no-store',
						headers: { 'Cache-Control': 'no-cache' },
						body: form
					});
				const data = await res.json();
				if(data && data.success){
					location.reload();
					return;
				}
				let msg = (data && data.data && data.data.message) ? data.data.message : '';
				if(msg === 'not_logged_in'){ alert('<?php echo esc_js( $t('waiter_assume_not_logged') ); ?>'); }
				else if(msg === 'cannot_assume'){ alert('<?php echo esc_js( $t('waiter_assume_cannot') ); ?>'); }
				else { alert('<?php echo esc_js( $t('waiter_assume_fail') ); ?>'); }
			}catch(e){
				alert('<?php echo esc_js( $t('waiter_net_fail') ); ?>');
			}finally{
				btn.disabled = false;
			}
		});
	});
})();
</script>
