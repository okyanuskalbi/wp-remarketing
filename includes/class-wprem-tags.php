<?php
/**
 * Front-end tag injection.
 *
 * Outputs remarketing snippets into the page head/footer, honouring the
 * consent mode and admin-exclusion settings. All dynamic IDs are validated
 * in WPREM_Settings::sanitize() before storage and re-escaped on output.
 *
 * @package WP_Remarketing
 */

defined( 'ABSPATH' ) || exit;

class WPREM_Tags {

	/**
	 * Resolved settings for this request.
	 *
	 * @var array
	 */
	private $s;

	public function __construct() {
		add_action( 'wp_head', array( $this, 'head' ), 5 );
		add_action( 'wp_body_open', array( $this, 'body_open' ), 5 );
		add_action( 'wp_footer', array( $this, 'footer' ), 99 );
	}

	/**
	 * Base activation check, independent of consent: plugin enabled, front-end,
	 * and not an excluded admin. Always (re)populates $this->s so each hook
	 * callback is self-contained.
	 *
	 * @return bool
	 */
	private function base_active() {
		$this->s = WPREM_Settings::get();

		if ( empty( $this->s['enabled'] ) ) {
			return false;
		}
		if ( is_admin() ) {
			return false;
		}
		if ( ! empty( $this->s['disable_admins'] ) && current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Whether tracking tags may be printed for the current visitor
	 * (base activation AND consent granted).
	 *
	 * @return bool
	 */
	private function should_render() {
		return $this->base_active() && $this->has_consent();
	}

	/**
	 * Consent gate. In "cookie" / "banner" mode the configured cookie must equal "1".
	 *
	 * @return bool
	 */
	private function has_consent() {
		if ( 'off' === $this->s['consent_mode'] ) {
			$granted = true;
		} else {
			$name    = $this->s['consent_cookie'];
			$granted = isset( $_COOKIE[ $name ] ) && '1' === $_COOKIE[ $name ]; // phpcs:ignore WordPress.Security.NonceVerification
		}
		/**
		 * Filter the marketing-consent decision. Lets dedicated consent managers
		 * (CookieYes, Complianz, Borlabs, ...) gate the remarketing tags instead
		 * of conflicting with this plugin's own consent mode.
		 *
		 * @param bool   $granted Whether marketing tags may load.
		 * @param string $mode    Current consent mode (off|cookie|banner).
		 */
		return (bool) apply_filters( 'wprem_has_consent', $granted, $this->s['consent_mode'] );
	}

	public function head() {
		if ( ! $this->should_render() ) {
			return;
		}
		$this->google_ads();
		$this->gtm_head();
		$this->meta_pixel();
		$this->tiktok();
	}

	public function body_open() {
		if ( ! $this->should_render() ) {
			return;
		}
		$this->gtm_body();
	}

	/**
	 * Footer runs independently of consent so the consent banner (which is
	 * what collects consent in "banner" mode) can be shown to first-time
	 * visitors. Tracking events themselves remain gated by has_consent().
	 */
	public function footer() {
		if ( ! $this->base_active() ) {
			return;
		}

		if ( $this->has_consent() && ! empty( $this->s['woo_events'] ) ) {
			$this->woo_events();
		}

		if ( 'banner' === $this->s['consent_mode'] && ! $this->cookie_set() ) {
			$this->consent_banner();
		}
	}

	private function cookie_set() {
		return isset( $_COOKIE[ $this->s['consent_cookie'] ] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/* ----------------------------------------------------------------------
	 * Individual tag renderers
	 * ------------------------------------------------------------------- */

	private function google_ads() {
		$id = $this->s['google_ads_id'];
		if ( '' === $id ) {
			return;
		}
		$id = esc_js( $id );
		?>
<!-- WP Remarketing: Google Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $this->s['google_ads_id'] ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
window.gtag = window.gtag || function(){dataLayer.push(arguments);};
gtag('js', new Date());
gtag('config', '<?php echo $id; // already esc_js ?>', {'conversion_linker': true});
</script>
		<?php
	}

	private function gtm_head() {
		$id = $this->s['gtm_id'];
		if ( '' === $id ) {
			return;
		}
		$id = esc_js( $id );
		?>
<!-- WP Remarketing: Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo $id; // already esc_js ?>');</script>
		<?php
	}

	private function gtm_body() {
		$id = $this->s['gtm_id'];
		if ( '' === $id ) {
			return;
		}
		?>
<!-- WP Remarketing: GTM (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $id ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
		<?php
	}

	private function meta_pixel() {
		$id = $this->s['meta_pixel_id'];
		if ( '' === $id ) {
			return;
		}
		$id = esc_js( $id );
		?>
<!-- WP Remarketing: Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo $id; // already esc_js ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo esc_attr( $this->s['meta_pixel_id'] ); ?>&ev=PageView&noscript=1"/></noscript>
		<?php
	}

	private function tiktok() {
		$id = $this->s['tiktok_id'];
		if ( '' === $id ) {
			return;
		}
		$id = esc_js( $id );
		?>
<!-- WP Remarketing: TikTok Pixel -->
<script>
!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];
ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};
var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
ttq.load('<?php echo $id; // already esc_js ?>');
ttq.page();
}(window,document,'ttq');
</script>
		<?php
	}

	/**
	 * Emit WooCommerce ViewContent / Purchase events for the configured pixels.
	 */
	private function woo_events() {
		if ( ! function_exists( 'is_product' ) ) {
			return;
		}

		$has_any = '' !== $this->s['meta_pixel_id']
			|| '' !== $this->s['tiktok_id']
			|| '' !== $this->s['google_ads_id'];
		if ( ! $has_any ) {
			return;
		}

		if ( is_product() ) {
			$product = wc_get_product( get_the_ID() );
			if ( $product ) {
				$value = wc_get_price_to_display( $product );
				if ( '' === $value || null === $value ) {
					$value = 0;
				}
				$this->emit_event(
					'ViewContent',
					'ViewContent',
					'view_item',
					array(
						'content_ids'  => array( (string) $product->get_id() ),
						'content_type' => 'product',
						'value'        => (float) $value,
						'currency'     => get_woocommerce_currency(),
					)
				);
			}
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			$order_id = absint( get_query_var( 'order-received' ) );
			$order    = $order_id ? wc_get_order( $order_id ) : false;
			// Fire once per order: WooCommerce thank-you page is reachable on refresh.
			if ( $order && ! $order->get_meta( '_wprem_purchase_tracked' ) ) {
				$this->emit_event(
					'Purchase',
					'CompletePayment',
					'purchase',
					array(
						'value'          => (float) $order->get_total(),
						'currency'       => $order->get_currency(),
						'transaction_id' => (string) $order->get_id(),
					)
				);
				$order->update_meta_data( '_wprem_purchase_tracked', '1' );
				$order->save();
			}
		}
	}

	/**
	 * Push one event to the configured pixels with JSON-encoded params.
	 *
	 * @param string $meta_event   Meta (fbq) event name.
	 * @param string $tiktok_event TikTok (ttq) event name.
	 * @param string $google_event Google Ads (gtag) event name.
	 * @param array  $params       Event payload (value, currency, etc.).
	 */
	private function emit_event( $meta_event, $tiktok_event, $google_event, $params ) {
		$json = wp_json_encode( $params );
		if ( false === $json ) {
			$json = '{}';
		}
		echo "\n<!-- WP Remarketing: event -->\n<script>\n";
		if ( '' !== $this->s['meta_pixel_id'] ) {
			printf(
				"if(window.fbq){fbq('track','%s',%s);}\n",
				esc_js( $meta_event ),
				$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
			);
		}
		if ( '' !== $this->s['tiktok_id'] ) {
			printf(
				"if(window.ttq){ttq.track('%s',%s);}\n",
				esc_js( $tiktok_event ),
				$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
			);
		}
		if ( '' !== $this->s['google_ads_id'] ) {
			$g               = $params;
			$g['send_to']    = $this->s['google_ads_id'];
			$gjson           = wp_json_encode( $g );
			if ( false === $gjson ) {
				$gjson = '{}';
			}
			printf(
				"if(window.gtag){gtag('event','%s',%s);}\n",
				esc_js( $google_event ),
				$gjson // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
			);
		}
		echo "</script>\n";
	}

	/**
	 * Minimal consent banner used only in "banner" mode.
	 */
	private function consent_banner() {
		$cookie = esc_js( $this->s['consent_cookie'] );
		?>
<div id="wprem-consent" style="position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#1d2327;color:#fff;padding:14px 18px;font:14px/1.5 sans-serif;display:flex;gap:14px;align-items:center;justify-content:center;flex-wrap:wrap">
	<span><?php esc_html_e( 'Pazarlama çerezlerini kullanmamıza izin veriyor musunuz?', 'wp-remarketing' ); ?></span>
	<button type="button" id="wprem-accept" style="background:#2271b1;color:#fff;border:0;padding:8px 16px;border-radius:4px;cursor:pointer"><?php esc_html_e( 'Kabul et', 'wp-remarketing' ); ?></button>
	<button type="button" id="wprem-decline" style="background:transparent;color:#fff;border:1px solid #fff;padding:8px 16px;border-radius:4px;cursor:pointer"><?php esc_html_e( 'Reddet', 'wp-remarketing' ); ?></button>
</div>
<script>
(function(){
	var name='<?php echo $cookie; // already esc_js ?>';
	function set(v){document.cookie=name+'='+v+';path=/;max-age=15552000;SameSite=Lax';document.getElementById('wprem-consent').style.display='none';if(v==='1'){location.reload();}}
	document.getElementById('wprem-accept').addEventListener('click',function(){set('1');});
	document.getElementById('wprem-decline').addEventListener('click',function(){set('0');});
})();
</script>
		<?php
	}
}
