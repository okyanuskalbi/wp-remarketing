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
		// WordPress 6.7+ requires translations to load no earlier than `init`.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			$this->settings = new WPREM_Settings();
		} else {
			$this->tags = new WPREM_Tags();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-remarketing',
			false,
			dirname( plugin_basename( WPREM_FILE ) ) . '/languages'
		);
	}
}
