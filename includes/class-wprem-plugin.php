<?php
/**
 * Plugin bootstrap — wires the settings and tag components together.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_Plugin {

	/**
	 * @var WPREM_Settings
	 */
	public $settings;

	/**
	 * @var WPREM_Tags
	 */
	public $tags;

	public function __construct() {
		load_plugin_textdomain( 'wp-remarketing', false, dirname( plugin_basename( WPREM_FILE ) ) . '/languages' );

		if ( is_admin() ) {
			$this->settings = new WPREM_Settings();
		} else {
			$this->tags = new WPREM_Tags();
		}
	}
}
