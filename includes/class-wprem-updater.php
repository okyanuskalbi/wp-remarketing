<?php
/**
 * Self-hosted update checker — surfaces GitHub Releases as WordPress plugin
 * updates so the plugin can be updated from the admin with one click.
 *
 * No third-party library: queries the public GitHub Releases API, compares the
 * latest tag against the installed version, and feeds the release zip asset into
 * WordPress's normal update flow. Responses are cached 12h to respect rate limits.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_Updater {

	const REPO          = 'okyanuskalbi/wp-remarketing';
	const TRANSIENT     = 'wprem_update_release';
	const CACHE_SECONDS = 12 * HOUR_IN_SECONDS;

	/**
	 * @var string Plugin basename, e.g. wp-remarketing/wp-remarketing.php.
	 */
	private $basename;

	public function __construct() {
		$this->basename = plugin_basename( WPREM_FILE );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		// Drop the cache right after WP updates the plugin, so a re-check is fresh.
		add_action( 'upgrader_process_complete', array( $this, 'flush_cache' ), 10, 0 );
	}

	/**
	 * Fetch the latest GitHub release (cached). Returns null on failure.
	 *
	 * @return array|null { version, zip, html_url, published_at }
	 */
	private function latest_release() {
		// "Yeniden kontrol et" (force-check) bizim önbelleği de baypas etsin.
		$force = ! empty( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $force ) {
			$cached = get_transient( self::TRANSIENT );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$resp = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 5,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'wp-remarketing-updater',
				),
			)
		);
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			set_transient( self::TRANSIENT, array(), HOUR_IN_SECONDS );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_transient( self::TRANSIENT, array(), HOUR_IN_SECONDS );
			return null;
		}

		// Prefer an uploaded .zip asset; fall back to the auto source zipball.
		$zip = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( isset( $asset['browser_download_url'] ) && substr( $asset['browser_download_url'], -4 ) === '.zip' ) {
					$zip = $asset['browser_download_url'];
					break;
				}
			}
		}
		if ( '' === $zip ) {
			$zip = isset( $data['zipball_url'] ) ? $data['zipball_url'] : '';
		}

		$release = array(
			'version'      => ltrim( sanitize_text_field( $data['tag_name'] ), 'vV' ),
			'zip'          => esc_url_raw( $zip ),
			'html_url'     => esc_url_raw( $data['html_url'] ?? '' ),
			'published_at' => sanitize_text_field( $data['published_at'] ?? '' ),
			'body'         => isset( $data['body'] ) ? wp_kses_post( $data['body'] ) : '',
		);
		set_transient( self::TRANSIENT, $release, self::CACHE_SECONDS );
		return $release;
	}

	/**
	 * Inject an available update into the update_plugins transient.
	 *
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->latest_release();
		if ( ! $release || empty( $release['version'] ) || empty( $release['zip'] ) ) {
			return $transient;
		}

		if ( ! version_compare( $release['version'], WPREM_VERSION, '>' ) ) {
			return $transient;
		}

		$item = array(
			'slug'        => dirname( $this->basename ),
			'plugin'      => $this->basename,
			'new_version' => $release['version'],
			'url'         => $release['html_url'],
			'package'     => $release['zip'],
		);

		$transient->response[ $this->basename ] = (object) $item;
		return $transient;
	}

	/**
	 * Populate the "View details" modal for this plugin.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action API action.
	 * @param object             $args   Request args.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( $this->basename ) !== $args->slug ) {
			return $result;
		}

		$release = $this->latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'WP Remarketing',
			'slug'          => dirname( $this->basename ),
			'version'       => $release['version'],
			'author'        => 'Emre',
			'homepage'      => $release['html_url'],
			'download_link' => $release['zip'],
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'changelog' => $release['body'] ? nl2br( esc_html( $release['body'] ) ) : esc_html__( 'Sürüm notları GitHub Releases sayfasında.', 'wp-remarketing' ),
			),
		);
	}

	/**
	 * Flush the cached release after an update so the next check is fresh.
	 */
	public function flush_cache() {
		delete_transient( self::TRANSIENT );
	}
}
