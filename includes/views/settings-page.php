<?php
/**
 * Settings page markup. Receives $s (settings array) from WPREM_Settings::render().
 *
 * @package WP_Remarketing
 * @var array $s
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap wprem-wrap">
	<h1><?php esc_html_e( 'WP Remarketing', 'wp-remarketing' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Remarketing etiketlerini tek yerden yönetin. Boş bırakılan alanlar enjekte edilmez.', 'wp-remarketing' ); ?>
	</p>

	<form action="options.php" method="post">
		<?php settings_fields( WPREM_Settings::GROUP ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Eklenti aktif', 'wp-remarketing' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( WPREM_OPTION ); ?>[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?> />
						<?php esc_html_e( 'Etiketleri sitede yayınla', 'wp-remarketing' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wprem-google-ads"><?php esc_html_e( 'Google Ads (AW-)', 'wp-remarketing' ); ?></label></th>
				<td>
					<input type="text" id="wprem-google-ads" class="regular-text" placeholder="AW-123456789"
						name="<?php echo esc_attr( WPREM_OPTION ); ?>[google_ads_id]"
						value="<?php echo esc_attr( $s['google_ads_id'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Google Ads dönüşüm/remarketing etiketi kimliği.', 'wp-remarketing' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wprem-gtm"><?php esc_html_e( 'Google Tag Manager (GTM-)', 'wp-remarketing' ); ?></label></th>
				<td>
					<input type="text" id="wprem-gtm" class="regular-text" placeholder="GTM-XXXXXX"
						name="<?php echo esc_attr( WPREM_OPTION ); ?>[gtm_id]"
						value="<?php echo esc_attr( $s['gtm_id'] ); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wprem-meta"><?php esc_html_e( 'Meta Pixel ID', 'wp-remarketing' ); ?></label></th>
				<td>
					<input type="text" id="wprem-meta" class="regular-text" placeholder="1234567890"
						name="<?php echo esc_attr( WPREM_OPTION ); ?>[meta_pixel_id]"
						value="<?php echo esc_attr( $s['meta_pixel_id'] ); ?>" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wprem-tiktok"><?php esc_html_e( 'TikTok Pixel ID', 'wp-remarketing' ); ?></label></th>
				<td>
					<input type="text" id="wprem-tiktok" class="regular-text" placeholder="C4XXXXXXXXXXXXXXXXXX"
						name="<?php echo esc_attr( WPREM_OPTION ); ?>[tiktok_id]"
						value="<?php echo esc_attr( $s['tiktok_id'] ); ?>" />
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Onay (Consent)', 'wp-remarketing' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Onay modu', 'wp-remarketing' ); ?></th>
				<td>
					<?php
					$modes = array(
						'off'    => __( 'Kapalı — etiketler her zaman yüklenir', 'wp-remarketing' ),
						'cookie' => __( 'Çerez — onay çerezi varsa yükle', 'wp-remarketing' ),
						'banner' => __( 'Banner — basit onay bandı göster', 'wp-remarketing' ),
					);
					foreach ( $modes as $key => $label ) :
						?>
						<label style="display:block;margin-bottom:4px">
							<input type="radio" name="<?php echo esc_attr( WPREM_OPTION ); ?>[consent_mode]"
								value="<?php echo esc_attr( $key ); ?>" <?php checked( $s['consent_mode'], $key ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wprem-cookie"><?php esc_html_e( 'Onay çerez adı', 'wp-remarketing' ); ?></label></th>
				<td>
					<input type="text" id="wprem-cookie" class="regular-text"
						name="<?php echo esc_attr( WPREM_OPTION ); ?>[consent_cookie]"
						value="<?php echo esc_attr( $s['consent_cookie'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Değeri "1" olduğunda etiketler yüklenir.', 'wp-remarketing' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Gelişmiş', 'wp-remarketing' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'WooCommerce olayları', 'wp-remarketing' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( WPREM_OPTION ); ?>[woo_events]" value="1" <?php checked( $s['woo_events'], 1 ); ?> />
						<?php esc_html_e( 'Ürün görüntüleme ve satın alma olaylarını gönder', 'wp-remarketing' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Yöneticileri hariç tut', 'wp-remarketing' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( WPREM_OPTION ); ?>[disable_admins]" value="1" <?php checked( $s['disable_admins'], 1 ); ?> />
						<?php esc_html_e( 'Giriş yapmış yöneticilerde etiketleri yükleme', 'wp-remarketing' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'İstatistik (ilk-taraf)', 'wp-remarketing' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'İstatistik toplama', 'wp-remarketing' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( WPREM_OPTION ); ?>[analytics]" value="1" <?php checked( $s['analytics'], 1 ); ?> />
						<?php esc_html_e( 'Ziyaret, UTM, sepete ekleme ve satışları kaydet', 'wp-remarketing' ); ?>
					</label>
					<p class="description">
						<?php
						printf(
							/* translators: %s: stats page link */
							esc_html__( 'Raporlar: %s', 'wp-remarketing' ),
							'<a href="' . esc_url( admin_url( 'options-general.php?page=' . WPREM_Stats::PAGE ) ) . '">' . esc_html__( 'WP Remarketing İstatistik', 'wp-remarketing' ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'İl/şehir çözümü', 'wp-remarketing' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( WPREM_OPTION ); ?>[geo_lookup]" value="1" <?php checked( $s['geo_lookup'], 1 ); ?> />
						<?php esc_html_e( 'IP konumundan il/şehir çöz (ham IP saklanmaz, anonimleştirilir)', 'wp-remarketing' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
