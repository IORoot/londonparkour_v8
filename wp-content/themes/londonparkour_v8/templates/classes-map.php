<?php
/**
 * Template Name: Classes — Map
 *
 * ClassesMap, ported from src/stories/Pages/ClassesMap/ClassesMap.js (`dGpgK`).
 * A page template rather than an archive: its unit is a SITE, and sites are
 * lp_location posts shown alongside a class index, so there is no single query
 * for WordPress to route.
 *
 * Section order: header cluster → The Network → Meeting Points → onward.
 * Nav/footer are get_header()/get_footer(); the promoted cluster does not
 * render nav, so the masthead's <h1> sits inside <main> and <nav> outside it.
 *
 * ── Pin placement: real coordinates, not the source's ──────────────────────
 *
 * The source positions its six pins at hardcoded x/y percentages and its own
 * JSDoc says they are approximate and that the .pen has no pixel-space
 * contract to transcribe. Those numbers are therefore NOT ported. lp_location
 * already carries `latitude`/`longitude`, so pins are projected from real
 * coordinates and a site added tomorrow lands in the right place without
 * anyone editing a percentage.
 *
 * ponytail: equirectangular projection — longitude is not scaled by
 * cos(latitude). Across London's ~0.27° of longitude the horizontal error is
 * under a pixel at this size, and the ground is an explicitly labelled
 * placeholder, not a map. A real basemap (Leaflet/Mapbox) is the upgrade path,
 * and it would replace the projection wholesale rather than refine it.
 *
 * ── What is derived rather than copied ─────────────────────────────────────
 *
 * The source's CLASS_INDEX, PINS and SITE_PANELS are literal arrays. Here all
 * three read the database: the index is published clasbpro_class posts, the pins and
 * panels are published lp_location posts, and each site's class count is
 * counted. The design's own numbers would go stale the first time an editor
 * added a class — the same call PORT-FINDINGS §17 made for the view-rail tab
 * metas.
 *
 * The `meeting_point` / `transport_rail` / `transport_bus` strings the panels
 * print are transcribed from this source file's own SITE_PANELS constant into
 * bin/demo-content/lp_location.json, not authored.
 *
 * ── The one gap, recorded rather than papered over ─────────────────────────
 *
 * The source's index meta reads `STRATFORD EAST · WEDNESDAYS 18:00 · WITH
 * ANDY` — a weekly RECURRENCE. Clasbpro still exposes dated occurrences rather
 * than a human recurrence string, so the meta names the next real session
 * ("MON 07:15") instead of claiming a weekly pattern. Same class of gap as
 * §19's SITES and §20's date_label. Closing it needs a recurrence field, which
 * is the repo owner's decision, not a port decision.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_sites = get_posts(
	array(
		'post_type'      => 'lp_location',
		'post_status'    => 'publish',
		'posts_per_page' => 24,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	)
);

$lp_classes = get_posts(
	lp_class_active_meta_query(
		array(
			'post_type'      => lp_class_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => 24,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	)
);

$lp_classes = lp_class_dedupe_by_title( $lp_classes );

/*
 * One pass over the classes, keyed by location id — so the per-site counts
 * below are a lookup rather than a query inside the site loop.
 */
$lp_count_by_site = array();

foreach ( $lp_classes as $lp_class ) {
	$lp_site_id = lp_class_location_id( (int) $lp_class->ID );

	if ( $lp_site_id ) {
		$lp_count_by_site[ $lp_site_id ] = ( $lp_count_by_site[ $lp_site_id ] ?? 0 ) + 1;
	}
}

/*
 * Pins: bounding box over every site that HAS both coordinates, then a linear
 * projection into the placeholder box, inset so an edge pin is not clipped by
 * its own marker. Latitude increases northward and CSS `top` increases
 * downward, so the vertical axis inverts. A single site (or several stacked on
 * one point) has no span to scale against and centres instead of dividing by
 * zero.
 */
$lp_inset  = 8.0;
$lp_span   = 100.0 - ( $lp_inset * 2 );
$lp_points = array();

foreach ( $lp_sites as $lp_site ) {
	$lp_lat = (string) get_field( 'latitude', $lp_site->ID );
	$lp_lon = (string) get_field( 'longitude', $lp_site->ID );

	if ( '' === trim( $lp_lat ) || '' === trim( $lp_lon ) ) {
		continue;
	}

	$lp_points[ $lp_site->ID ] = array(
		'lat' => (float) $lp_lat,
		'lon' => (float) $lp_lon,
	);
}

$lp_lats    = wp_list_pluck( $lp_points, 'lat' );
$lp_lons    = wp_list_pluck( $lp_points, 'lon' );
$lp_min_lat = $lp_lats ? min( $lp_lats ) : 0.0;
$lp_max_lat = $lp_lats ? max( $lp_lats ) : 0.0;
$lp_min_lon = $lp_lons ? min( $lp_lons ) : 0.0;
$lp_max_lon = $lp_lons ? max( $lp_lons ) : 0.0;
$lp_lat_gap = $lp_max_lat - $lp_min_lat;
$lp_lon_gap = $lp_max_lon - $lp_min_lon;

$lp_project = static function ( array $lp_point ) use ( $lp_inset, $lp_span, $lp_min_lat, $lp_max_lat, $lp_min_lon, $lp_lat_gap, $lp_lon_gap ): array {
	return array(
		'left' => $lp_lon_gap > 0 ? $lp_inset + ( ( $lp_point['lon'] - $lp_min_lon ) / $lp_lon_gap ) * $lp_span : 50.0,
		'top'  => $lp_lat_gap > 0 ? $lp_inset + ( ( $lp_max_lat - $lp_point['lat'] ) / $lp_lat_gap ) * $lp_span : 50.0,
	);
};

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/classes-header-cluster',
		array(
			'crumbs'   => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'CLASSES',
					'href'  => (string) get_post_type_archive_link( lp_class_post_type() ),
				),
				array( 'label' => 'LOCATION MAP' ),
			),
			'action'   => array(
				'label' => 'ALL CLASSES ↗',
				'href'  => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
			'masthead' => array(
				'title' => 'Stride. Leap. Balance. Fly.',
				'note'  => 'Find your class on the map, then scroll for meeting points and travel details. Every site is a ten-minute walk from a tube or overground station.',
			),
			'active'   => 'map',
		)
	);
	?>

	<div class="w-full bg-neutral" data-component="map-network">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<?php
			lp_part(
				'components/section-head',
				array(
					'surface' => 'board',
					'heading' => 'Six sites. One network.',
					'note'    => 'Pick a pin to see what runs there. Ten weekly classes, seven coaches, and nothing further than ten minutes from a station.',
				)
			);
			?>
			<div class="flex flex-col lg:flex-row gap-10 lg:gap-16">
				<?php if ( $lp_classes ) : ?>
					<ul role="list" class="flex-1 min-w-0 flex flex-col m-0 p-0 list-none">
						<?php
						foreach ( $lp_classes as $lp_i => $lp_class ) :
							$lp_class_id = (int) $lp_class->ID;
							$lp_site_id  = lp_class_location_id( $lp_class_id );
							$lp_coach_id = 0;
							$lp_coaches  = lp_class_coach_ids( $lp_class_id );

							if ( $lp_coaches ) {
								$lp_coach_id = (int) $lp_coaches[0];
							}

							/*
							 * The next dated session, not a recurrence — see the
							 * docblock.
							 */
							$lp_sessions = lp_class_upcoming_sessions( $lp_class_id, 1 );
							$lp_next     = $lp_sessions[0] ?? null;

							$lp_meta = array_filter(
								array(
									$lp_site_id ? strtoupper( get_the_title( $lp_site_id ) ) : '',
									$lp_next ? trim( strtoupper( (string) ( $lp_next['date_label'] ?? '' ) ) . ' ' . (string) ( $lp_next['time'] ?? '' ) ) : '',
									$lp_coach_id ? 'WITH ' . strtoupper( get_the_title( $lp_coach_id ) ) : '',
								)
							);
							?>
							<li>
								<?php
								lp_part(
									'components/list-row',
									array(
										'surface' => 'page',
										'index'   => str_pad( (string) ( (int) $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
										'title'   => get_the_title( $lp_class ),
										'meta'    => implode( ' · ', $lp_meta ),
										'href'    => (string) get_permalink( $lp_class ),
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="w-full lg:w-[480px] lg:shrink-0 flex flex-col gap-[16px]">
					<?php
					lp_part(
						'components/meta-row',
						array(
							'surface' => 'board',
							'left'    => 'SITE NETWORK',
							'right'   => 'CLICK PIN FOR STREETVIEW ↗',
						)
					);
					?>
					<div class="relative w-full aspect-[4/3] bg-base-300 overflow-hidden">
						<?php
						foreach ( $lp_sites as $lp_site ) :
							if ( ! isset( $lp_points[ $lp_site->ID ] ) ) {
								continue;
							}

							$lp_at    = $lp_project( $lp_points[ $lp_site->ID ] );
							$lp_type  = (string) get_field( 'type', $lp_site->ID );
							$lp_flag  = (bool) get_field( 'is_flagship', $lp_site->ID );
							$lp_count = (int) ( $lp_count_by_site[ $lp_site->ID ] ?? 0 );

							$lp_sub = array_filter(
								array(
									$lp_count ? sprintf( _n( '%d CLASS', '%d CLASSES', $lp_count, 'londonparkour_v8' ), $lp_count ) : '',
									strtoupper( $lp_type ),
									$lp_flag ? 'FLAGSHIP' : '',
								)
							);
							?>
							<div class="absolute" style="left: <?php echo esc_attr( number_format( $lp_at['left'], 2, '.', '' ) ); ?>%; top: <?php echo esc_attr( number_format( $lp_at['top'], 2, '.', '' ) ); ?>%;">
								<?php
								lp_part(
									'components/map-pin',
									array(
										'variant'  => 'icon',
										'name'     => get_the_title( $lp_site ),
										'sub'      => implode( ' · ', $lp_sub ),
										'flagship' => $lp_flag,
										'href'     => (string) get_permalink( $lp_site ),
									)
								);
								?>
							</div>
						<?php endforeach; ?>
					</div>
					<p class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50 m-0">MAP PLACEHOLDER · PIN MARKERS</p>
					<div class="flex items-center gap-[24px]">
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => 'SITE',
								'icon_id' => 'icon-map-pin',
								'surface' => 'board',
								'tone'    => 'ink',
							)
						);

						lp_part(
							'elements/glyph-label',
							array(
								'label'   => 'FLAGSHIP',
								'icon_id' => 'icon-map-pin',
								'surface' => 'board',
								'tone'    => 'signal',
							)
						);
						?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php if ( $lp_sites ) : ?>
		<div class="w-full bg-base-100" data-component="map-meeting-points">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
				<?php
				lp_part(
					'components/section-head',
					array(
						'surface' => 'page',
						'eyebrow' => 'MEETING POINTS & TRAVEL',
						'heading' => 'Where to stand when you arrive.',
						'note'    => 'Coaches are on the meeting point ten minutes before the hour, holding a yellow flag.',
					)
				);
				?>
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16">
					<?php
					foreach ( $lp_sites as $lp_site ) :
						$lp_type  = (string) get_field( 'type', $lp_site->ID );
						$lp_flag  = (bool) get_field( 'is_flagship', $lp_site->ID );
						$lp_count = (int) ( $lp_count_by_site[ $lp_site->ID ] ?? 0 );

						$lp_kicker = array_filter( array( strtoupper( $lp_type ), $lp_flag ? 'FLAGSHIP' : '' ) );

						lp_part(
							'components/site-panel',
							array(
								'kicker'         => implode( ' · ', $lp_kicker ),
								'kind'           => strtolower( $lp_type ),
								'name'           => get_the_title( $lp_site ),
								'code'           => (string) get_field( 'meta', $lp_site->ID ),
								'count'          => $lp_count ? sprintf( _n( '%d CLASS', '%d CLASSES', $lp_count, 'londonparkour_v8' ), $lp_count ) : '',
								'meeting_point'  => (string) get_field( 'meeting_point', $lp_site->ID ),
								'transport_rail' => (string) get_field( 'transport_rail', $lp_site->ID ),
								'transport_bus'  => (string) get_field( 'transport_bus', $lp_site->ID ),
							)
						);
					endforeach;
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← AGENDA VIEW',
				'label'   => 'Everything on this week, by day',
				'href'    => lp_classes_page_url( 'classes-agenda' ),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Take the next open slot',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
