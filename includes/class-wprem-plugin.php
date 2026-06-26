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

	/**
	 * @var WPREM_Tracker
	 */
	public $tracker;

	/**
	 * @var WPREM_Stats
	 */
	public $stats;

	/**
	 * @var WPREM_Updater
	 */
	public $updater;

	public function __construct() {
		// WordPress 6.7+ requires translations to load no earlier than `init`.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			WPREM_DB::maybe_upgrade();
			$this->settings = new WPREM_Settings();
			$this->stats    = new WPREM_Stats();
			$this->updater  = new WPREM_Updater();
		} else {
			$this->tags = new WPREM_Tags();
		}

		// Tracker runs on both front-end (beacon/Woo hooks) and REST requests.
		$this->tracker = new WPREM_Tracker();
	}

	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-remarketing',
			false,
			dirname( plugin_basename( WPREM_FILE ) ) . '/languages'
		);
	}
}
