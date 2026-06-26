<?php
/**
 * Stats page markup. Variables provided by WPREM_Stats::render():
 *
 * @package WP_Remarketing
 * @var int   $days
 * @var array $t         Totals row.
 * @var array $abandoned Abandoned-cart sessions.
 * @var int   $sessions
 * @var int   $purchases
 * @var float $conv
 */

defined( 'ABSPATH' ) || exit;

$base = admin_url( 'options-general.php?page=' . WPREM_Stats::PAGE );
?>
<div class="wrap wprem-wrap">
	<h1><?php esc_html_e( 'WP Remarketing İstatistik', 'wp-remarketing' ); ?></h1>

	<p class="wprem-range">
		<?php esc_html_e( 'Dönem:', 'wp-remarketing' ); ?>
		<?php foreach ( array( 7, 30, 90 ) as $d ) : ?>
			<?php if ( $d === $days ) : ?>
				<strong><?php echo esc_html( $d ); ?> <?php esc_html_e( 'gün', 'wp-remarketing' ); ?></strong>
			<?php else : ?>
				<a href="<?php echo esc_url( $base . '&range=' . $d ); ?>"><?php echo esc_html( $d ); ?> <?php esc_html_e( 'gün', 'wp-remarketing' ); ?></a>
			<?php endif; ?>
			<?php echo $d !== 90 ? ' · ' : ''; ?>
		<?php endforeach; ?>
	</p>

	<div class="wprem-cards">
		<?php
		$cards = array(
			array( __( 'Oturum', 'wp-remarketing' ), number_format_i18n( $sessions ) ),
			array( __( 'Sepete ekleme', 'wp-remarketing' ), number_format_i18n( (int) ( $t['atc'] ?? 0 ) ) ),
			array( __( 'Satış', 'wp-remarketing' ), number_format_i18n( $purchases ) ),
			array( __( 'Dönüşüm', 'wp-remarketing' ), $conv . '%' ),
			array( __( 'Ciro', 'wp-remarketing' ), WPREM_Stats::money( $t['revenue'] ?? 0 ) ),
		);
		foreach ( $cards as $c ) {
			echo '<div class="wprem-card"><span class="wprem-card-num">' . esc_html( $c[1] ) . '</span><span class="wprem-card-lbl">' . esc_html( $c[0] ) . '</span></div>';
		}
		?>
	</div>

	<h2><?php esc_html_e( 'Sepette bırakanlar', 'wp-remarketing' ); ?></h2>
	<table class="widefat striped" style="max-width:1100px"><thead><tr>
		<th><?php esc_html_e( 'Son görülme', 'wp-remarketing' ); ?></th>
		<th><?php esc_html_e( 'İl / İlçe', 'wp-remarketing' ); ?></th>
		<th><?php esc_html_e( 'Platform', 'wp-remarketing' ); ?></th>
		<th><?php esc_html_e( 'Kaynak / Mecra', 'wp-remarketing' ); ?></th>
		<th><?php esc_html_e( 'Kampanya', 'wp-remarketing' ); ?></th>
		<th><?php esc_html_e( 'Sepete ekleme', 'wp-remarketing' ); ?></th>
	</tr></thead><tbody>
	<?php if ( empty( $abandoned ) ) : ?>
		<tr><td colspan="6"><em><?php esc_html_e( 'Sepette bırakan yok.', 'wp-remarketing' ); ?></em></td></tr>
	<?php else : ?>
		<?php
		foreach ( (array) $abandoned as $a ) :
			$il    = $a['region'] ? $a['region'] : '(bilinmiyor)';
			$ilce  = $a['city'] ? ' / ' . $a['city'] : '';
			$src   = ( $a['utm_source'] ? $a['utm_source'] : '(doğrudan)' ) . ' / ' . ( $a['utm_medium'] ? $a['utm_medium'] : '-' );
			$camp  = $a['utm_campaign'] ? $a['utm_campaign'] : '—';
			$dev   = $a['device'] ? $a['device'] : '(bilinmiyor)';
			?>
			<tr>
				<td><?php echo esc_html( $a['last_seen'] ); ?></td>
				<td><?php echo esc_html( $il . $ilce ); ?></td>
				<td><?php echo esc_html( $dev ); ?></td>
				<td><?php echo esc_html( $src ); ?></td>
				<td><?php echo esc_html( $camp ); ?></td>
				<td><?php echo esc_html( number_format_i18n( (int) $a['atc_count'] ) ); ?></td>
			</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody></table>

	<p class="description" style="margin-top:18px">
		<?php esc_html_e( 'Sepette bırakanlar: sepete ekleyip satın almamış oturumlardır. Bot isabetleri hariç tutulur. İl/ilçe IP konumundan çözülür; ham IP saklanmaz.', 'wp-remarketing' ); ?>
	</p>
</div>
