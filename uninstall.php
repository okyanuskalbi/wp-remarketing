<?php
/**
 * Runs when the plugin is deleted from the WordPress admin.
 *
 * @package WP_Remarketing
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wprem_settings' );
