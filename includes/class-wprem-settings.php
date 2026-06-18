<?php
/**
 * Settings storage, defaults and admin settings page.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_Settings {

	const PAGE  = 'wp-remarketing';
	const GROUP = 'wprem_settings_group';

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'        => 1,
			'google_ads_id'  => '',
			'gtm_id'         => '',
			'meta_pixel_id'  => '',
			'tiktok_id'      => '',
			'consent_mode'   => 'off', // off | cookie | banner
			'consent_cookie' => 'wprem_consent',
			'woo_events'     => 1,
			'disable_admins' => 1,
			'analytics'      => 1,
			'geo_lookup'     => 1,
		);
	}

	/**
	 * Read merged settings (stored over defaults).
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( WPREM_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Convenience getter for a single key.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function value( $key ) {
		$all = self::get();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Register hooks for the admin UI.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( WPREM_FILE ),
			array( $this, 'action_links' )
		);
	}

	public function action_links( $links ) {
		$url   = admin_url( 'options-general.php?page=' . self::PAGE );
		$links = array_merge(
			array( '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ayarlar', 'wp-remarketing' ) . '</a>' ),
			$links
		);
		return $links;
	}

	public function menu() {
		add_options_page(
			__( 'WP Remarketing', 'wp-remarketing' ),
			__( 'WP Remarketing', 'wp-remarketing' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	public function assets( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wprem-admin', WPREM_URL . 'assets/admin.css', array(), WPREM_VERSION );
	}

	public function register() {
		register_setting(
			self::GROUP,
			WPREM_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize and validate every field before it is stored.
	 *
	 * @param array $input Raw POST data.
	 * @return array
	 */
	public function sanitize( $input ) {
		$out = self::defaults();
		$in  = is_array( $input ) ? $input : array();

		$out['enabled']        = empty( $in['enabled'] ) ? 0 : 1;
		$out['woo_events']     = empty( $in['woo_events'] ) ? 0 : 1;
		$out['disable_admins'] = empty( $in['disable_admins'] ) ? 0 : 1;
		$out['analytics']      = empty( $in['analytics'] ) ? 0 : 1;
		$out['geo_lookup']     = empty( $in['geo_lookup'] ) ? 0 : 1;

		$out['google_ads_id'] = self::clean_id( $in['google_ads_id'] ?? '', '/^AW-[A-Za-z0-9]+$/' );
		$out['gtm_id']        = self::clean_id( $in['gtm_id'] ?? '', '/^GTM-[A-Za-z0-9]+$/' );
		$out['meta_pixel_id'] = preg_replace( '/\D/', '', (string) ( $in['meta_pixel_id'] ?? '' ) );
		$out['tiktok_id']     = self::clean_id( $in['tiktok_id'] ?? '', '/^[A-Za-z0-9]+$/' );

		$consent             = isset( $in['consent_mode'] ) ? sanitize_key( $in['consent_mode'] ) : 'off';
		$out['consent_mode'] = in_array( $consent, array( 'off', 'cookie', 'banner' ), true ) ? $consent : 'off';

		$cookie                = sanitize_key( $in['consent_cookie'] ?? 'wprem_consent' );
		$out['consent_cookie'] = $cookie ? $cookie : 'wprem_consent';

		return $out;
	}

	/**
	 * Trim and validate an ID against a pattern; empty on mismatch.
	 *
	 * @param string $raw     Raw value.
	 * @param string $pattern Regex including delimiters.
	 * @return string
	 */
	private static function clean_id( $raw, $pattern ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		return preg_match( $pattern, $raw ) ? $raw : '';
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = self::get();
		require WPREM_DIR . 'includes/views/settings-page.php';
	}
}
