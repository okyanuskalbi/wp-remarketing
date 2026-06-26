<?php
/**
 * Plugin Name:       WP Remarketing
 * Plugin URI:        https://github.com/okyanuskalbi/wp-remarketing
 * Description:        Remarketing etiket/pixel yöneticisi — Google Ads, Google Tag Manager, Meta Pixel ve TikTok için merkezi, onay (consent) duyarlı etiket enjeksiyonu. WooCommerce ürün görüntüleme ve satın alma olaylarını destekler.
 * Version:           1.3.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Emre Yüksel
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-remarketing
 * Domain Path:       /languages
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

define( 'WPREM_VERSION', '1.3.2' );
define( 'WPREM_FILE', __FILE__ );
define( 'WPREM_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPREM_URL', plugin_dir_url( __FILE__ ) );
define( 'WPREM_OPTION', 'wprem_settings' );

require_once WPREM_DIR . 'includes/class-wprem-db.php';
require_once WPREM_DIR . 'includes/class-wprem-settings.php';
require_once WPREM_DIR . 'includes/class-wprem-tags.php';
require_once WPREM_DIR . 'includes/class-wprem-tracker.php';
require_once WPREM_DIR . 'includes/class-wprem-stats.php';
require_once WPREM_DIR . 'includes/class-wprem-updater.php';
require_once WPREM_DIR . 'includes/class-wprem-plugin.php';

/**
 * Boot the plugin.
 */
function wprem() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new WPREM_Plugin();
	}
	return $instance;
}

add_action( 'plugins_loaded', 'wprem' );

/**
 * Default settings written on activation.
 */
function wprem_activate() {
	if ( false === get_option( WPREM_OPTION ) ) {
		add_option( WPREM_OPTION, WPREM_Settings::defaults() );
	}
	WPREM_DB::install();
	delete_transient( 'wprem_update_release' ); // güncelleme önbelleğini temizle
}

/**
 * Clear the update-check cache when the plugin is deactivated, so the next
 * activation/check pulls a fresh release list.
 */
function wprem_deactivate() {
	delete_transient( 'wprem_update_release' );
}
register_activation_hook( __FILE__, 'wprem_activate' );
register_deactivation_hook( __FILE__, 'wprem_deactivate' );

/**
 * Declare WooCommerce HPOS (custom order tables) compatibility. We only touch
 * orders through the CRUD API ($order->get_meta/update_meta_data/save), so the
 * plugin is compatible — without this WooCommerce flags it as incompatible.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WPREM_FILE, true );
		}
	}
);
