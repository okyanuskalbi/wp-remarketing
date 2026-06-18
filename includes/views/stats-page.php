<?php
/**
 * Stats page markup. Variables provided by WPREM_Stats::render():
 *
 * @package WP_Remarketing
 * @var int   $days
 * @var array $t        Totals row.
 * @var array $by_src   Source/medium breakdown.
 * @var array $by_camp  Campaign breakdown.
 * @var array $by_il    Region (il) breakdown.
 * @var int   $sessions
 * @var int   $purchases
 * @var float $conv
 */

defined( 'ABSPATH' ) || exit;

$base = admin_url( 'options-general.php?page=' . WPREM_Stats::PAGE );

/**
 * Render a breakdown table.
 *
 * @param string $heading Section title.
 * @param string $col     First-column label.
 * @param array  $rows    Breakdown rows.
 */
$render_table = function ( $heading, $col, $rows ) {
	echo '<h2>' . esc_html( $heading ) . '</h2>';
	echo '<table class="widefat striped" style="max-width:880px"><thead><tr>';
	echo '<th>' . esc_html( $col ) . '</th><th>Oturum</th><th>Sepete ekleme</th><th>Satış</th><th>Ciro</th>';
	echo '</tr></thead><tbody>';
	if ( empty( $rows ) ) {
		echo '<tr><td colspan="5"><em>' . esc_html__( 'Veri yok.', 'wp-remarketing' ) . '</em></td></tr>';
	}
	foreach ( (array) $rows as $r ) {
		echo '<tr>';
		echo '<td>' . esc_html( $r['label'] ) . '</td>';
		echo '<td>' . esc_html( number_format_i18n( (int) $r['sessions'] ) ) . '</td>';
		echo '<td>' . esc_html( number_format_i18n( (int) $r['atc'] ) ) . '</td>';
		echo '<td>' . esc_html( number_format_i18n( (int) $r['purchases'] ) ) . '</td>';
		echo '<td>' . esc_html( WPREM_Stats::money( $r['revenue'] ) ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
};
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
			array( __( 'Tekil ziyaretçi', 'wp-remarketing' ), number_format_i18n( (int) ( $t['visitors'] ?? 0 ) ) ),
			array( __( 'Oturum', 'wp-remarketing' ), number_format_i18n( $sessions ) ),
			array( __( 'Sayfa görüntüleme', 'wp-remarketing' ), number_format_i18n( (int) ( $t['pageviews'] ?? 0 ) ) ),
			array( __( 'Sepete ekleme', 'wp-remarketing' ), number_format_i18n( (int) ( $t['atc'] ?? 0 ) ) ),
			array( __( 'Satış', 'wp-remarketing' ), number_format_i18n( $purchases ) ),
			array( __( 'Dönüşüm', 'wp-remarketing' ), $conv . '%' ),
			array( __( 'Ciro', 'wp-remarketing' ), WPREM_Stats::money( $t['revenue'] ?? 0 ) ),
			array( __( 'Bot isabeti', 'wp-remarketing' ), number_format_i18n( (int) ( $t['bot_hits'] ?? 0 ) ) ),
		);
		foreach ( $cards as $c ) {
			echo '<div class="wprem-card"><span class="wprem-card-num">' . esc_html( $c[1] ) . '</span><span class="wprem-card-lbl">' . esc_html( $c[0] ) . '</span></div>';
		}
		?>
	</div>

	<?php
	$render_table( __( 'Kaynak / Mecra', 'wp-remarketing' ), __( 'Kaynak / Mecra', 'wp-remarketing' ), $by_src );
	$render_table( __( 'Kampanya', 'wp-remarketing' ), __( 'Kampanya', 'wp-remarketing' ), $by_camp );
	$render_table( __( 'İl', 'wp-remarketing' ), __( 'İl', 'wp-remarketing' ), $by_il );
	?>

	<p class="description" style="margin-top:18px">
		<?php esc_html_e( 'Bot isabetleri tablolardan hariç tutulur. İl bilgisi IP konumundan çözülür; ham IP saklanmaz.', 'wp-remarketing' ); ?>
	</p>
</div>
