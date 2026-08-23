<?php
/**
 * single-lp_location.php — LocationDetail for class sites only.
 *
 * Ported from src/stories/Pages/LocationDetail/LocationDetail.js. Spots never
 * reach this template (template_redirect 404). Meeting-point markup is copied
 * from single-clasbpro_class.php so the OSM map, travel copy, and class strings
 * stay byte-identical.
 *
 * Section order: breadcrumb → masthead → fact rail → meeting point (map +
 * travel) → classes at this site → other sites → onward.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lp_id       = (int) get_the_ID();
	$lp_title    = get_the_title( $lp_id );
	$lp_classes  = lp_classes_page_url( 'classes' );
	$lp_map      = lp_classes_page_url( 'classes-map' );
	$lp_archive  = (string) get_post_type_archive_link( 'lp_location' );
	$lp_listings = function_exists( 'lp_classes_listings_url' ) ? lp_classes_listings_url() : $lp_classes;

	$lp_tag            = (string) get_field( 'tag', $lp_id );
	$lp_type           = (string) get_field( 'type', $lp_id );
	$lp_meta           = (string) get_field( 'meta', $lp_id );
	$lp_meeting_point  = (string) get_field( 'meeting_point', $lp_id );
	$lp_transport_rail = (string) get_field( 'transport_rail', $lp_id );
	$lp_transport_bus  = (string) get_field( 'transport_bus', $lp_id );
	$lp_lat            = trim( (string) get_field( 'latitude', $lp_id ) );
	$lp_lon            = trim( (string) get_field( 'longitude', $lp_id ) );
	$lp_streetview     = lp_location_streetview_url( $lp_id );
	$lp_osm_maps       = lp_osm_maps_url( $lp_lat, $lp_lon );
	$lp_coords_label   = ( '' !== $lp_lat && '' !== $lp_lon ) ? sprintf( '%s / %s', $lp_lat, $lp_lon ) : '';
	$lp_foot_parts     = array_filter(
		array(
			( '' !== $lp_lat && '' !== $lp_lon ) ? sprintf( '%s°N %s°W', $lp_lat, ltrim( $lp_lon, '-' ) ) : '',
			$lp_meta,
		)
	);
	$lp_meeting_foot   = implode( ' · ', $lp_foot_parts );
	$lp_kicker_parts   = array_filter(
		array(
			$lp_type,
			$lp_tag ? $lp_tag : ( $lp_title ? strtoupper( $lp_title ) : '' ),
		)
	);
	$lp_meeting_kicker = implode( ' · ', $lp_kicker_parts );
	$lp_site_heading   = $lp_title ? ( rtrim( $lp_title, '.' ) . '.' ) : '';
	$lp_show_meeting   = (
		'' !== $lp_meeting_point
		|| '' !== $lp_transport_rail
		|| '' !== $lp_transport_bus
		|| ( '' !== $lp_lat && '' !== $lp_lon )
		|| '' !== $lp_meeting_foot
	);

	$lp_facts = array();
	if ( '' !== $lp_type ) {
		$lp_facts[] = array(
			'icon'  => 'icon-map-pin',
			'label' => 'TYPE',
			'value' => $lp_type,
		);
	}
	if ( '' !== $lp_tag || '' !== $lp_title ) {
		$lp_facts[] = array(
			'icon'  => 'icon-user',
			'label' => 'SITE',
			'value' => $lp_tag ? $lp_tag : $lp_title,
		);
	}
	if ( '' !== $lp_meta ) {
		$lp_facts[] = array(
			'icon'  => 'icon-clock',
			'label' => 'DETAILS',
			'value' => $lp_meta,
		);
	}

	$lp_classes_here = function_exists( 'lp_classes_at_location' ) ? lp_classes_at_location( $lp_id ) : array();
	$lp_count_label  = function_exists( 'lp_location_class_count_label' ) ? lp_location_class_count_label( $lp_id ) : '';
	$lp_board_rows   = array();
	foreach ( $lp_classes_here as $lp_class ) {
		$lp_cid   = (int) $lp_class->ID;
		$lp_raw   = function_exists( 'lp_clasbpro_raw' ) ? lp_clasbpro_raw( $lp_cid ) : null;
		$lp_time  = ( $lp_raw && function_exists( 'lp_booking_form_hhmm' ) )
			? lp_booking_form_hhmm( (string) ( $lp_raw['start_time'] ?? '' ) )
			: '';
		$lp_day   = $lp_raw ? strtoupper( (string) ( $lp_raw['day_of_week'] ?? '' ) ) : '';
		$lp_terms = get_the_terms( $lp_cid, 'lp_level' );
		$lp_level = ( is_array( $lp_terms ) && $lp_terms ) ? $lp_terms[0]->name : '';

		$lp_board_rows[] = array(
			'part' => 'components/board-row',
			'args' => array(
				'variant'     => 'default',
				'time'        => $lp_time,
				'date_label'  => $lp_day ? $lp_day : $lp_meta,
				'title'       => get_the_title( $lp_cid ),
				'subtitle'    => function_exists( 'lp_class_composed_subtitle' ) ? lp_class_composed_subtitle( $lp_cid ) : '',
				'location'    => $lp_title,
				'level'       => $lp_level,
				'href'        => (string) get_permalink( $lp_cid ),
				'show_spaces' => false,
			),
		);
	}

	$lp_other_sites = array();
	if ( function_exists( 'lp_locations_by_kind' ) ) {
		foreach ( lp_locations_by_kind( 'site' ) as $lp_site ) {
			if ( (int) $lp_site->ID === $lp_id ) {
				continue;
			}
			$lp_other_sites[] = $lp_site;
		}
	}
	$lp_next = $lp_other_sites[0] ?? null;

	$lp_media_id = has_post_thumbnail( $lp_id ) ? (int) get_post_thumbnail_id( $lp_id ) : 0;
	?>

	<main id="main">
		<?php
		lp_part(
			'components/breadcrumb-rail',
			array(
				'crumbs' => array(
					array(
						'label' => 'HOME',
						'href'  => home_url( '/' ),
					),
					array(
						'label' => 'CLASSES',
						'href'  => $lp_classes,
					),
					array(
						'label' => 'LOCATIONS',
						'href'  => $lp_archive,
					),
					array( 'label' => strtoupper( $lp_title ) ),
				),
				'action' => array(
					'label' => 'LOCATION MAP ↗',
					'href'  => $lp_map,
				),
			)
		);

		lp_part(
			'components/page-masthead',
			array(
				'title'    => $lp_title,
				'note'     => $lp_meeting_point ? $lp_meeting_point : $lp_meta,
				'media_id' => $lp_media_id,
			)
		);
		?>

		<?php if ( $lp_facts ) : ?>
			<div class="w-full bg-neutral" data-component="location-detail-fact-rail">
				<div class="px-6 lg:px-16 py-scale-s grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-x-[40px] gap-y-6" data-mount="rail">
					<?php foreach ( $lp_facts as $lp_fact ) : ?>
						<?php lp_part( 'components/fact-row', array_merge( $lp_fact, array( 'surface' => 'board' ) ) ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $lp_show_meeting ) : ?>
			<div class="w-full bg-base-200" data-component="class-detail-meeting-point">
				<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
					<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-[16px]">
						<div class="flex flex-col gap-[14px] min-w-0">
							<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">MEETING POINT</span>
							<?php if ( '' !== $lp_site_heading ) : ?>
								<h2 class="font-heading text-[36px] lg:text-[42px] font-bold leading-none tracking-[-1.6px] text-base-content m-0"><?php echo esc_html( $lp_site_heading ); ?></h2>
							<?php endif; ?>
						</div>
						<?php if ( '' !== $lp_streetview ) : ?>
							<a href="<?php echo esc_url( $lp_streetview ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[11px] font-semibold uppercase tracking-[1px] text-accent shrink-0">STREETVIEW ↗</a>
						<?php endif; ?>
					</div>

					<div class="flex flex-col lg:flex-row gap-12 items-start">
						<div class="w-full lg:w-1/2 min-w-0 flex flex-col gap-[28px] border-t border-base-content pt-[22px]">
							<?php if ( '' !== $lp_meeting_kicker ) : ?>
								<div class="flex items-center gap-[8px]">
									<span class="text-base-content" aria-hidden="true"><?php lp_icon( 'icon-map-pin', 'w-[12px] h-[12px]' ); ?></span>
									<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65"><?php echo esc_html( $lp_meeting_kicker ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $lp_meeting_point ) : ?>
								<div class="flex flex-col gap-[10px]">
									<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">MEETING POINT</span>
									<p class="font-label text-[14px] font-normal leading-[1.7] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp_meeting_point ); ?></p>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $lp_transport_rail || '' !== $lp_transport_bus ) : ?>
								<div class="flex flex-col gap-[10px]">
									<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">TRANSPORT</span>
									<?php if ( '' !== $lp_transport_rail ) : ?>
										<p class="font-body text-[13px] font-medium leading-[1.6] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp_transport_rail ); ?></p>
									<?php endif; ?>
									<?php if ( '' !== $lp_transport_bus ) : ?>
										<p class="font-body text-[12px] font-normal leading-[1.6] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp_transport_bus ); ?></p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $lp_meeting_foot || '' !== $lp_osm_maps ) : ?>
								<div class="flex flex-wrap items-center justify-between gap-[16px] border-t border-base-300 pt-[14px]">
									<?php if ( '' !== $lp_meeting_foot ) : ?>
										<span class="font-label text-[10px] font-medium uppercase tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_meeting_foot ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $lp_osm_maps ) : ?>
										<a href="<?php echo esc_url( $lp_osm_maps ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent">OPEN IN MAPS ↗</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( '' !== $lp_lat && '' !== $lp_lon && is_numeric( $lp_lat ) && is_numeric( $lp_lon ) ) : ?>
							<div class="w-full lg:w-1/2 flex flex-col gap-[12px]">
								<div
									class="relative isolate w-full h-[360px] bg-base-300 border border-base-300 overflow-hidden"
									data-component="class-detail-osm"
									data-lat="<?php echo esc_attr( $lp_lat ); ?>"
									data-lon="<?php echo esc_attr( $lp_lon ); ?>"
									data-name="<?php echo esc_attr( $lp_title ? $lp_title : 'Meeting point' ); ?>"
								>
									<div class="absolute inset-0 z-0 [&_.leaflet-container]:h-full [&_.leaflet-container]:w-full [&_.leaflet-container]:!z-0" data-mount="leaflet"></div>
									<div class="absolute inset-x-0 top-0 z-[500] flex items-center justify-between h-10 px-4 bg-base-100 border-b border-base-300 pointer-events-none">
										<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content">OPENSTREETMAP</span>
										<?php if ( '' !== $lp_coords_label ) : ?>
											<span class="font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_coords_label ); ?></span>
										<?php endif; ?>
									</div>
									<div class="absolute inset-x-0 bottom-0 z-[500] flex items-center justify-between h-11 px-4 bg-base-100 border-t border-base-300">
										<span class="font-label text-[9px] font-normal tracking-[0.7px] text-base-content/65">© OPENSTREETMAP CONTRIBUTORS</span>
										<?php if ( '' !== $lp_streetview ) : ?>
											<a href="<?php echo esc_url( $lp_streetview ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent pointer-events-auto">STREETVIEW ↗</a>
										<?php endif; ?>
									</div>
									<div class="hidden" data-meeting-pin aria-hidden="true">
										<?php
										lp_part(
											'components/map-pin',
											array(
												'name'     => $lp_title ? $lp_title : 'Meeting point',
												'variant'  => 'icon',
												'flagship' => true,
												'label'    => false,
											)
										);
										?>
									</div>
								</div>
								<p class="font-label text-[11px] font-normal leading-[1.5] tracking-[0.1px] text-base-content/65 m-0">Map centres on the meeting point.</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $lp_board_rows ) : ?>
			<div class="w-full bg-neutral" data-component="location-detail-classes">
				<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
					<?php
					lp_part(
						'components/section-head',
						array(
							'surface' => 'board',
							'eyebrow' => 'CLASSES',
							'heading' => $lp_site_heading,
							'note'    => sprintf( '(%02d)', count( $lp_board_rows ) ),
						)
					);

					lp_part(
						'components/board-shell',
						array(
							'board_title' => strtoupper( $lp_title ),
							'live_label'  => $lp_count_label,
							'columns'     => array( 'TIME', 'SESSION', 'LOCATION', 'LEVEL', 'AVAILABILITY' ),
							'rows'        => $lp_board_rows,
							'foot_left'   => 'ALL CLASSES →',
							'foot_href'   => $lp_listings,
							'foot_right'  => trim( $lp_count_label . ' · ' . strtoupper( $lp_title ), ' ·' ),
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $lp_other_sites ) : ?>
			<div class="w-full bg-base-200" data-component="location-detail-other-sites">
				<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
					<?php
					lp_part(
						'components/section-head',
						array(
							'surface' => 'page',
							'eyebrow' => 'MEETING POINTS & TRAVEL',
							'heading' => 'Three sites. One network.',
							'note'    => 'Coaches are on the meeting point ten minutes before.',
						)
					);
					?>
					<div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16">
						<?php
						foreach ( $lp_other_sites as $lp_site ) :
							$lp_sid   = (int) $lp_site->ID;
							$lp_stype = (string) get_field( 'type', $lp_sid );
							$lp_stag  = (string) get_field( 'tag', $lp_sid );
							$lp_scnt  = function_exists( 'lp_location_class_count_label' ) ? lp_location_class_count_label( $lp_sid ) : '';
							$lp_ssv   = lp_location_streetview_url( $lp_sid );
							?>
							<div id="site-<?php echo esc_attr( $lp_site->post_name ); ?>">
								<?php
								lp_part(
									'components/site-panel',
									array(
										'kicker'          => $lp_stag !== '' ? $lp_stag : strtoupper( $lp_stype ),
										'kind'            => strtolower( $lp_stype ),
										'name'            => get_the_title( $lp_site ) . '.',
										'code'            => (string) get_field( 'meta', $lp_sid ),
										'count'           => $lp_scnt,
										'meeting_point'   => (string) get_field( 'meeting_point', $lp_sid ),
										'transport_rail'  => (string) get_field( 'transport_rail', $lp_sid ),
										'transport_bus'   => (string) get_field( 'transport_bus', $lp_sid ),
										'streetview_href' => $lp_ssv,
										'href'            => get_permalink( $lp_site ),
										'image_id'        => has_post_thumbnail( $lp_site ) ? (int) get_post_thumbnail_id( $lp_site ) : 0,
									)
								);
								?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php
		lp_part(
			'components/page-onward',
			array(
				'prev' => array(
					'keyword' => '← LOCATION MAP',
					'label'   => 'Three sites. One network.',
					'href'    => $lp_map,
				),
				'next' => $lp_next
					? array(
						'keyword' => strtoupper( get_the_title( $lp_next ) ) . ' →',
						'label'   => (string) get_field( 'meeting_point', $lp_next->ID ) ?: get_the_title( $lp_next ),
						'href'    => (string) get_permalink( $lp_next ),
					)
					: array(
						'keyword' => 'ALL CLASSES →',
						'label'   => 'Back to the class listings',
						'href'    => $lp_listings,
					),
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();
