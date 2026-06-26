<?php
/**
 * Stats page markup. Variables provided by WPREM_Stats::render():
 *
 * @package WP_Remarketing
 * @var int   $days
 * @var array $t        Totals row.
 * @var array $by_src   Source/medium breakdown.
 * @var array $by_camp  Campaign breakdown.
 * @var array $by_il       Region (il/ilçe) breakdown.
 * @var array $by_platform Device/platform breakdown.
 * @var array $abandoned   Abandoned-cart sessions.
 * @var array $geo_points  Heat-map points (il/ilçe + coordinates + counts).
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
	// Build map points payload: [lat, lon, sessions, "label", atc, purchases].
	$map_points = array();
	foreach ( (array) $geo_points as $g ) {
		$label = $g['region'] . ( $g['city'] ? ' / ' . $g['city'] : '' );
		$map_points[] = array(
			(float) $g['lat'],
			(float) $g['lon'],
			(int) $g['sessions'],
			$label,
			(int) $g['atc'],
			(int) $g['purchases'],
		);
	}
	?>
	<h2><?php esc_html_e( 'Ziyaretçi ısı haritası', 'wp-remarketing' ); ?></h2>
	<?php if ( empty( $map_points ) ) : ?>
		<p class="description"><?php esc_html_e( 'Henüz konum verisi yok. (Ayarlardan "IP konum çözümü" açık olmalı ve yeni ziyaretler gelmeli.)', 'wp-remarketing' ); ?></p>
	<?php else : ?>
		<div id="wprem-map" style="height:460px;max-width:1100px;border:1px solid #dcdcde;border-radius:6px;margin-bottom:8px"></div>
		<script>
		( function () {
			var pts = <?php echo wp_json_encode( $map_points ); ?>;
			function init() {
				if ( ! window.L || ! document.getElementById( 'wprem-map' ) ) { return; }
				var map = L.map( 'wprem-map' );
				L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					maxZoom: 18,
					attribution: '© OpenStreetMap'
				} ).addTo( map );

				var maxS = 1, bounds = [];
				pts.forEach( function ( p ) { if ( p[2] > maxS ) { maxS = p[2]; } } );

				var heat = pts.map( function ( p ) { return [ p[0], p[1], p[2] / maxS ]; } );
				if ( L.heatLayer ) {
					L.heatLayer( heat, { radius: 28, blur: 20, maxZoom: 9 } ).addTo( map );
				}

				pts.forEach( function ( p ) {
					bounds.push( [ p[0], p[1] ] );
					L.circleMarker( [ p[0], p[1] ], {
						radius: 5, color: '#d63638', weight: 1, fillOpacity: 0.6
					} ).addTo( map ).bindPopup(
						'<strong>' + p[3] + '</strong><br>Oturum: ' + p[2] +
						'<br>Sepete ekleme: ' + p[4] + '<br>Satış: ' + p[5]
					);
				} );

				if ( bounds.length ) {
					map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: 11 } );
				} else {
					map.setView( [ 39, 35 ], 5 );
				}
			}
			if ( document.readyState === 'loading' ) {
				document.addEventListener( 'DOMContentLoaded', init );
			} else {
				init();
			}
		} )();
		</script>
	<?php endif; ?>

	<?php
	$render_table( __( 'Kaynak / Mecra', 'wp-remarketing' ), __( 'Kaynak / Mecra', 'wp-remarketing' ), $by_src );
	$render_table( __( 'Kampanya', 'wp-remarketing' ), __( 'Kampanya', 'wp-remarketing' ), $by_camp );
	$render_table( __( 'Platform', 'wp-remarketing' ), __( 'Platform', 'wp-remarketing' ), $by_platform );
	$render_table( __( 'İl / İlçe', 'wp-remarketing' ), __( 'İl / İlçe', 'wp-remarketing' ), $by_il );
	?>

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
		<?php esc_html_e( 'Sepette bırakanlar: sepete ekleyip satın almamış oturumlardır. Bot isabetleri tablolardan hariç tutulur. İl/ilçe IP konumundan çözülür; ham IP saklanmaz.', 'wp-remarketing' ); ?>
	</p>
</div>
