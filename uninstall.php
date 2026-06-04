<?php
/**
 * Qaiyo Clean Gallery – uninstall.
 *
 * Csak takarítja a tranziens cache-t. A galéria posztokat és meta adatokat
 * NEM törli, hogy újratelepítéskor megmaradjanak.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Optimizer transients (cg_upload_* + qcg_upload_*)
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\\_transient\\_qcg\\_upload\\_%'
		OR option_name LIKE '\\_transient\\_timeout\\_qcg\\_upload\\_%'
		OR option_name LIKE '\\_transient\\_cg\\_upload\\_%'
		OR option_name LIKE '\\_transient\\_timeout\\_cg\\_upload\\_%'"
);
