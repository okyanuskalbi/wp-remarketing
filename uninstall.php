<?php
/**
 * Runs when the plugin is deleted from the WordPress admin.
 *
 * @package WP_Remarketing
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wprem-db.php';

delete_option( 'wprem_settings' );
WPREM_DB::drop();
