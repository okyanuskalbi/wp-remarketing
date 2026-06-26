<?php
/**
 * First-party analytics capture.
 *
 * A small async beacon (assets/tracker.js) posts pageview + UTM data to a REST
 * endpoint; the server enriches it with bot detection and IP-derived geo (the
 * raw IP is never stored — only a salted hash and the resolved region/city).
 * WooCommerce add-to-cart and purchase events are captured server-side and
 * attributed to the visitor's session.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_Tracker {

	const COOKIE_SID = 'wprem_sid';
	const NS         = 'wprem/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		if ( ! $this->enabled() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'on_add_to_cart' ), 10, 6 );
		add_action( 'woocommerce_thankyou', array( $this, 'on_purchase' ), 10, 1 );
	}

	private function enabled() {
		return ! empty( WPREM_Settings::value( 'analytics' ) );
	}

	private function geo_enabled() {
		return ! empty( WPREM_Settings::value( 'geo_lookup' ) );
	}

	/* ----------------------------------------------------------------------
	 * Front-end beacon
	 * ------------------------------------------------------------------- */

	public function enqueue() {
		if ( is_admin() ) {
			return;
		}
		if ( ! empty( WPREM_Settings::value( 'disable_admins' ) ) && current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_enqueue_script(
			'wprem-tracker',
			WPREM_URL . 'assets/tracker.js',
			array(),
			WPREM_VERSION,
			true
		);
		wp_localize_script(
			'wprem-tracker',
			'WPREM_TRACK',
			array( 'url' => esc_url_raw( rest_url( self::NS . '/track' ) ) )
		);
	}

	/* ----------------------------------------------------------------------
	 * REST endpoint
	 * ------------------------------------------------------------------- */

	public function register_routes() {
		register_rest_route(
			self::NS,
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_track' ),
				'permission_callback' => '__return_true', // public analytics beacon
			)
		);
	}

	/**
	 * Handle a pageview beacon.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_track( $request ) {
		if ( ! $this->enabled() ) {
			return new WP_REST_Response( array( 'ok' => false ), 200 );
		}

		$p   = $request->get_json_params();
		$p   = is_array( $p ) ? $p : array();
		$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$ip  = $this->client_ip();
		$bot = $this->is_bot( $ua );

		$geo = ( ! $bot && $this->geo_enabled() ) ? $this->geo( $ip ) : array(
			'country' => '',
			'region'  => '',
			'city'    => '',
			'lat'     => 0,
			'lon'     => 0,
		);

		$row = array(
			'session_id'   => $this->clean_sid( $p['sid'] ?? '' ),
			'visitor_hash' => $this->visitor_hash( $ip, $ua ),
			'event_type'   => 'pageview',
			'utm_source'   => $this->clean( $p['utm_source'] ?? '', 100 ),
			'utm_medium'   => $this->clean( $p['utm_medium'] ?? '', 100 ),
			'utm_campaign' => $this->clean( $p['utm_campaign'] ?? '', 150 ),
			'utm_term'     => $this->clean( $p['utm_term'] ?? '', 150 ),
			'utm_content'  => $this->clean( $p['utm_content'] ?? '', 150 ),
			'referrer'     => $this->clean_url( $p['referrer'] ?? '' ),
			'landing_path' => $this->clean( $p['path'] ?? '', 255 ),
			'country'      => $geo['country'],
			'region'       => $geo['region'],
			'city'         => $geo['city'],
			'lat'          => $geo['lat'],
			'lon'          => $geo['lon'],
			'device'       => $this->device( $ua ),
			'is_bot'       => $bot ? 1 : 0,
		);

		$this->insert( $row );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/* ----------------------------------------------------------------------
	 * WooCommerce events
	 * ------------------------------------------------------------------- */

	public function on_add_to_cart( $cart_item_key, $product_id ) {
		if ( ! $this->enabled() ) {
			return;
		}
		$attr = $this->session_attribution();
		$row  = array_merge(
			$attr,
			array(
				'event_type' => 'add_to_cart',
				'product_id' => absint( $product_id ),
			)
		);
		$this->insert( $row );
	}

	public function on_purchase( $order_id ) {
		if ( ! $this->enabled() ) {
			return;
		}
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || $order->get_meta( '_wprem_stat_tracked' ) ) {
			return; // fire once per order
		}

		$attr = $this->session_attribution();

		// Prefer the order's own address for region/city when geo was empty.
		if ( '' === $attr['region'] ) {
			$attr['region'] = sanitize_text_field( $order->get_billing_state() );
		}
		if ( '' === $attr['city'] ) {
			$attr['city'] = sanitize_text_field( $order->get_billing_city() );
		}
		if ( '' === $attr['country'] ) {
			$attr['country'] = sanitize_text_field( $order->get_billing_country() );
		}

		$row = array_merge(
			$attr,
			array(
				'event_type' => 'purchase',
				'order_id'   => absint( $order_id ),
				'value'      => (float) $order->get_total(),
				'currency'   => $order->get_currency(),
			)
		);
		$this->insert( $row );

		$order->update_meta_data( '_wprem_stat_tracked', '1' );
		$order->save();
	}

	/**
	 * Resolve this visitor's session id + first-touch UTM + geo from the most
	 * recent pageview row, so cart/purchase events inherit the same attribution.
	 *
	 * @return array
	 */
	private function session_attribution() {
		global $wpdb;
		$sid = $this->clean_sid( isset( $_COOKIE[ self::COOKIE_SID ] ) ? wp_unslash( $_COOKIE[ self::COOKIE_SID ] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$ip  = $this->client_ip();

		$base = array(
			'session_id'   => $sid,
			'visitor_hash' => $this->visitor_hash( $ip, $ua ),
			'utm_source'   => '',
			'utm_medium'   => '',
			'utm_campaign' => '',
			'utm_term'     => '',
			'utm_content'  => '',
			'referrer'     => '',
			'landing_path' => '',
			'country'      => '',
			'region'       => '',
			'city'         => '',
			'lat'          => 0,
			'lon'          => 0,
			'device'       => $this->device( $ua ),
			'is_bot'       => $this->is_bot( $ua ) ? 1 : 0,
		);

		if ( '' === $sid ) {
			return $base;
		}

		$table = WPREM_DB::table();
		$found = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT utm_source, utm_medium, utm_campaign, utm_term, utm_content, country, region, city, lat, lon, device
				 FROM $table WHERE session_id = %s AND event_type = 'pageview'
				 ORDER BY id DESC LIMIT 1",
				$sid
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( $found ) {
			$computed_device = $base['device'];
			$base            = array_merge( $base, $found );
			// Keep the live UA-derived device when the stored pageview predates it.
			if ( '' === $base['device'] ) {
				$base['device'] = $computed_device;
			}
		}
		return $base;
	}

	/* ----------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	private function insert( $row ) {
		global $wpdb;
		$row['created_at'] = current_time( 'mysql' );
		$wpdb->insert( WPREM_DB::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Best-effort client IP. Used only transiently for bot/geo/hash — never stored.
	 *
	 * @return string
	 */
	private function client_ip() {
		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $keys as $k ) {
			if ( empty( $_SERVER[ $k ] ) ) {
				continue;
			}
			$raw = sanitize_text_field( wp_unslash( $_SERVER[ $k ] ) );
			$ip  = trim( explode( ',', $raw )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '';
	}

	/**
	 * Salted, non-reversible visitor fingerprint (no raw IP stored).
	 *
	 * @param string $ip Client IP.
	 * @param string $ua User agent.
	 * @return string
	 */
	private function visitor_hash( $ip, $ua ) {
		if ( '' === $ip && '' === $ua ) {
			return '';
		}
		return hash( 'sha256', $ip . '|' . $ua . '|' . wp_salt( 'auth' ) );
	}

	/**
	 * Resolve country/region(il)/city from IP via ip-api.com, cached 12h per IP.
	 *
	 * @param string $ip Client IP.
	 * @return array
	 */
	private function geo( $ip ) {
		$empty = array(
			'country' => '',
			'region'  => '',
			'city'    => '',
			'lat'     => 0,
			'lon'     => 0,
		);
		if ( '' === $ip ) {
			return $empty;
		}

		$key    = 'wprem_geo_' . md5( $ip );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$resp = wp_remote_get(
			'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,countryCode,regionName,city,lat,lon',
			array( 'timeout' => 2 )
		);
		if ( is_wp_error( $resp ) ) {
			set_transient( $key, $empty, HOUR_IN_SECONDS );
			return $empty;
		}

		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $data ) || ( $data['status'] ?? '' ) !== 'success' ) {
			set_transient( $key, $empty, HOUR_IN_SECONDS );
			return $empty;
		}

		$geo = array(
			'country' => substr( sanitize_text_field( $data['countryCode'] ?? '' ), 0, 2 ),
			'region'  => $this->clean( $data['regionName'] ?? '', 100 ),
			'city'    => $this->clean( $data['city'] ?? '', 100 ),
			'lat'     => isset( $data['lat'] ) ? round( (float) $data['lat'], 6 ) : 0,
			'lon'     => isset( $data['lon'] ) ? round( (float) $data['lon'], 6 ) : 0,
		);
		set_transient( $key, $geo, 12 * HOUR_IN_SECONDS );
		return $geo;
	}

	/**
	 * Classify the visitor's device/platform from the user agent.
	 *
	 * @param string $ua User agent.
	 * @return string 'Mobil', 'Tablet' or 'Masaüstü'.
	 */
	private function device( $ua ) {
		if ( '' === $ua ) {
			return '';
		}
		if ( preg_match( '/ipad|tablet|playbook|silk|(android(?!.*mobile))/i', $ua ) ) {
			return 'Tablet';
		}
		if ( preg_match( '/mobi|iphone|ipod|android.*mobile|windows phone|blackberry|opera mini/i', $ua ) ) {
			return 'Mobil';
		}
		return 'Masaüstü';
	}

	/**
	 * Compact bot detection on the user agent.
	 *
	 * @param string $ua User agent.
	 * @return bool
	 */
	private function is_bot( $ua ) {
		if ( '' === $ua ) {
			return true; // no UA → treat as non-human
		}
		$pattern = '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|embedly|'
			. 'pingdom|bitlybot|ia_archiver|semrush|ahrefs|mj12|dotbot|petalbot|bingpreview|'
			. 'headless|phantomjs|python-requests|python-urllib|curl|wget|axios|go-http|'
			. 'okhttp|java\/|libwww|scrapy|httpclient/i';
		$bot = (bool) preg_match( $pattern, $ua );
		/**
		 * Filter bot detection so a security/bot-management plugin (Wordfence,
		 * Cloudflare, etc.) can supply a more authoritative verdict.
		 *
		 * @param bool   $bot Whether the request looks like a bot.
		 * @param string $ua  User agent.
		 */
		return (bool) apply_filters( 'wprem_is_bot', $bot, $ua );
	}

	private function clean( $v, $max ) {
		$v = sanitize_text_field( (string) $v );
		return mb_substr( $v, 0, $max );
	}

	private function clean_url( $v ) {
		return mb_substr( esc_url_raw( (string) $v ), 0, 255 );
	}

	private function clean_sid( $v ) {
		$v = preg_replace( '/[^A-Za-z0-9]/', '', (string) $v );
		return substr( (string) $v, 0, 32 );
	}
}
