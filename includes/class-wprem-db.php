<?php
/**
 * Analytics storage — custom events table install/upgrade.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_DB {

	const DB_VERSION    = '1';
	const VERSION_OPT   = 'wprem_db_version';

	/**
	 * Fully-qualified events table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'wprem_events';
	}

	/**
	 * Create or update the events table via dbDelta.
	 */
	public static function install() {
		global $wpdb;
		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		// dbDelta is whitespace/format sensitive: two spaces after PRIMARY KEY,
		// lowercase types, one definition per line.
		$sql = "CREATE TABLE $table (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	session_id varchar(32) NOT NULL DEFAULT '',
	visitor_hash varchar(64) NOT NULL DEFAULT '',
	event_type varchar(20) NOT NULL DEFAULT 'pageview',
	utm_source varchar(100) NOT NULL DEFAULT '',
	utm_medium varchar(100) NOT NULL DEFAULT '',
	utm_campaign varchar(150) NOT NULL DEFAULT '',
	utm_term varchar(150) NOT NULL DEFAULT '',
	utm_content varchar(150) NOT NULL DEFAULT '',
	referrer varchar(255) NOT NULL DEFAULT '',
	landing_path varchar(255) NOT NULL DEFAULT '',
	country varchar(2) NOT NULL DEFAULT '',
	region varchar(100) NOT NULL DEFAULT '',
	city varchar(100) NOT NULL DEFAULT '',
	is_bot tinyint(1) NOT NULL DEFAULT 0,
	product_id bigint(20) unsigned NOT NULL DEFAULT 0,
	order_id bigint(20) unsigned NOT NULL DEFAULT 0,
	value decimal(12,2) NOT NULL DEFAULT 0,
	currency varchar(3) NOT NULL DEFAULT '',
	created_at datetime DEFAULT NULL,
	PRIMARY KEY  (id),
	KEY event_type (event_type),
	KEY created_at (created_at),
	KEY utm_source (utm_source),
	KEY session_id (session_id)
) $collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::VERSION_OPT, self::DB_VERSION );
	}

	/**
	 * Run install() when the stored schema version is behind.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPT ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Drop the events table (used on uninstall).
	 */
	public static function drop() {
		global $wpdb;
		$table = self::table();
		$wpdb->query( "DROP TABLE IF EXISTS $table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		delete_option( self::VERSION_OPT );
	}
}
