<?php
/**
 * Analytics reporting — admin "İstatistik" page and aggregate queries.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_Stats {

	const PAGE = 'wp-remarketing-stats';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function assets( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wprem-admin', WPREM_URL . 'assets/admin.css', array(), WPREM_VERSION );
	}

	public function menu() {
		add_submenu_page(
			'options-general.php',
			__( 'WP Remarketing İstatistik', 'wp-remarketing' ),
			__( 'WP Remarketing İstatistik', 'wp-remarketing' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Resolve the selected range (days) from the query string.
	 *
	 * @return int
	 */
	private function range_days() {
		$allowed = array( 7, 30, 90 );
		$req     = isset( $_GET['range'] ) ? absint( $_GET['range'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
		return in_array( $req, $allowed, true ) ? $req : 30;
	}

	private function since( $days ) {
		return gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
	}

	/**
	 * Headline totals for the period.
	 *
	 * @param string $since Lower datetime bound.
	 * @return array
	 */
	private function totals( $since ) {
		global $wpdb;
		$table = WPREM_DB::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(DISTINCT CASE WHEN event_type='pageview' AND is_bot=0 THEN session_id END) AS sessions,
					COUNT(DISTINCT CASE WHEN event_type='pageview' AND is_bot=0 THEN visitor_hash END) AS visitors,
					SUM(event_type='pageview' AND is_bot=0) AS pageviews,
					SUM(event_type='pageview' AND is_bot=1) AS bot_hits,
					SUM(event_type='add_to_cart' AND is_bot=0) AS atc,
					SUM(event_type='purchase') AS purchases,
					SUM(CASE WHEN event_type='purchase' THEN value ELSE 0 END) AS revenue
				FROM $table WHERE created_at >= %s",
				$since
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB
		return $row ? $row : array();
	}

	/**
	 * Sessions that added to cart but never purchased (abandoned carts), with the
	 * visitor's il/ilçe, platform and source/UTM. One row per session, newest first.
	 *
	 * @param string $since Lower datetime bound.
	 * @param int    $limit Row cap.
	 * @return array
	 */
	private function abandoned( $since, $limit = 200 ) {
		global $wpdb;
		$table = WPREM_DB::table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					a.session_id,
					MAX(a.created_at) AS last_seen,
					COUNT(*) AS atc_count,
					MAX(a.region) AS region,
					MAX(a.city) AS city,
					MAX(a.device) AS device,
					MAX(a.utm_source) AS utm_source,
					MAX(a.utm_medium) AS utm_medium,
					MAX(a.utm_campaign) AS utm_campaign,
					MAX(a.referrer) AS referrer
				FROM $table a
				WHERE a.event_type = 'add_to_cart'
					AND a.is_bot = 0
					AND a.session_id <> ''
					AND a.created_at >= %s
					AND NOT EXISTS (
						SELECT 1 FROM $table p
						WHERE p.session_id = a.session_id AND p.event_type = 'purchase'
					)
				GROUP BY a.session_id
				ORDER BY last_seen DESC
				LIMIT %d",
				$since,
				$limit
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$days  = $this->range_days();
		$since = $this->since( $days );

		$t         = $this->totals( $since );
		$abandoned = $this->abandoned( $since );

		$sessions  = (int) ( $t['sessions'] ?? 0 );
		$purchases = (int) ( $t['purchases'] ?? 0 );
		$conv      = $sessions > 0 ? round( $purchases / $sessions * 100, 2 ) : 0;

		require WPREM_DIR . 'includes/views/stats-page.php';
	}

	/**
	 * Format a money value for display.
	 *
	 * @param mixed $v Raw value.
	 * @return string
	 */
	public static function money( $v ) {
		return number_format_i18n( (float) $v, 2 );
	}
}
