=== Armo DineFlow ===
Contributors: armolab
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.6
License: GPLv2 or later
Text Domain: armo-dineflow

Reset técnico estable v1.1.0:
- Rewrites /dineflow/* con controlador + views (sin fatal por namespace).
- Settings: Logo restaurante, Idioma ES/EN del front, colores UI, URLs Takeaway/Delivery.
- QR: render/descarga via Google Chart (escaneable) + tamaño/margen configurables.
- Pages: Mode selector, Waiter, Kitchen, Join (base).

== Changelog ==
= 1.1.1 =
Reset estable: arquitectura front, settings y QR escaneable.

= 1.1.1 =
Fix: autoloader interno para evitar "Class not found" en activación.


= 1.1.2 =
- Fix QR URL (no double-encoding) + PNG validation.
- Tables: name + seats + location.
- Admin: color picker.
- Front: more complete EN/ES strings.
- Mode selector modal on WooCommerce pages when mode not set.


= 1.1.3 =
- Delivery providers chooser route.
- Tables UI upgrades (cards + edit/delete + copy).
- QR download opens new tab and keeps admin.
- Clear in color picker reverts to defaults.


= 1.1.4 =
- Fix fatal error: add Settings::boot() back-compat.


= 1.1.5 =
- Fix QR provider (QuickChart) to avoid Google Chart 404.
- QR download now links directly to PNG (no server proxy).


= 1.1.6 =
- QR Join now forces Dine In, creates/reuses table session, and supports Join Code.
- Waiter panel lists unassigned sessions and allows assuming a table.
- New DB table: armo_df_sessions.


= 1.1.13 =
- Waiter UX: cards per table, detail drawer with AJAX polling.
- Fix: waiter sections spacing.
- Add session items table and capture WooCommerce add-to-cart into Dine In session.
