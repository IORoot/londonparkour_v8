<?php
/**
 * Template Name: Classes — Map
 *
 * Class sites and map-only training spots from `lp_location`. The Network map
 * is Leaflet + Carto Voyager (`assets/js/elements/SiteNetworkMap.js`).
 * Meeting Points lists sites only.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_sites = lp_locations_by_kind( 'site' );
$lp_spots = lp_locations_by_kind( 'spot' );

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

$lp_count_by_site = array();

foreach ( $lp_classes as $lp_class ) {
	$lp_site_id = lp_class_location_id( (int) $lp_class->ID );

	if ( $lp_site_id ) {
		$lp_count_by_site[ $lp_site_id ] = ( $lp_count_by_site[ $lp_site_id ] ?? 0 ) + 1;
	}
}

$lp_site_n    = count( $lp_sites );
$lp_spot_n    = count( $lp_spots );
$lp_class_n   = count( $lp_classes );
$lp_sites_lbl = sprintf(
	/* translators: %d: number of training sites */
	_n( '%d site. One network.', '%d sites. One network.', $lp_site_n, 'londonparkour_v8' ),
	$lp_site_n
);
$lp_sites_lbl = preg_replace_callback(
	'/^\d+/',
	static function ( $m ) {
		$words = array(
			1 => 'One',
			2 => 'Two',
			3 => 'Three',
			4 => 'Four',
			5 => 'Five',
			6 => 'Six',
		);
		$n     = (int) $m[0];
		return $words[ $n ] ?? (string) $n;
	},
	$lp_sites_lbl
);

$lp_map_places = array_merge( $lp_sites, $lp_spots );

$lp_mast_media = lp_demo_media_id( 'DSC01072.jpeg' );
$lp_mast_url   = $lp_mast_media ? '' : (string) get_theme_file_uri( 'bin/demo-media/DSC01072.jpeg' );

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
					'href'  => lp_classes_page_url( 'classes' ),
				),
				array( 'label' => 'LOCATION MAP' ),
			),
			'action'   => array(
				'label' => 'ALL CLASSES ↗',
				'href'  => lp_classes_page_url( 'classes' ),
			),
			'masthead' => array(
				'title'     => 'Stride. Leap. Balance. Fly.',
				'note'      => 'Find your class on the map, then scroll for meeting points and travel details. Every site is a ten-minute walk from a tube or overground station.',
				'media_id'  => $lp_mast_media,
				'media_url' => $lp_mast_url,
				'media_alt' => '',
			),
			'active'      => 'map',
			'show_filter' => false,
		)
	);
	?>

	<div class="w-full bg-neutral" data-component="map-network">
		<div class="px-6 lg:px-16 pt-scale-2xl pb-10">
			<?php
			lp_part(
				'components/section-head',
				array(
					'surface' => 'board',
					'heading' => $lp_sites_lbl,
					'note'    => sprintf(
						/* translators: %d: number of class locations */
						_n(
							'Pick a pin to see what runs there. %d location across London, and nothing further than ten minutes from a station.',
							'Pick a pin to see what runs there. %d locations across London, and nothing further than ten minutes from a station.',
							$lp_site_n,
							'londonparkour_v8'
						),
						$lp_site_n
					),
				)
			);
			?>
		</div>
		<div class="flex flex-col lg:flex-row lg:items-stretch pb-scale-2xl px-6 lg:px-16" data-map-stage>
			<div class="w-full lg:w-[300px] xl:w-[340px] lg:shrink-0 flex flex-col bg-base-100 mb-0 overflow-hidden min-h-0" data-map-sidebar>
				<div class="shrink-0 flex border-b border-base-300" role="tablist" aria-label="Map places">
					<button
						type="button"
						role="tab"
						aria-selected="true"
						data-map-list-tab="classes"
						class="flex-1 px-[22px] py-[15px] font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-accent border-b-2 border-accent bg-transparent cursor-pointer"
					>
						CLASSES · <?php echo esc_html( (string) $lp_site_n ); ?>
					</button>
					<button
						type="button"
						role="tab"
						aria-selected="false"
						data-map-list-tab="spots"
						class="flex-1 px-[22px] py-[15px] font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-base-content/50 border-b-2 border-transparent bg-transparent cursor-pointer"
					>
						SPOTS · <?php echo esc_html( (string) $lp_spot_n ); ?>
					</button>
				</div>

				<ul role="list" class="flex flex-col m-0 p-0 list-none" data-map-list="classes">
					<?php
					foreach ( $lp_sites as $lp_i => $lp_site ) :
						$lp_type  = (string) get_field( 'type', $lp_site->ID );
						$lp_count = (int) ( $lp_count_by_site[ $lp_site->ID ] ?? 0 );
						$lp_slug  = $lp_site->post_name ? $lp_site->post_name : (string) $lp_site->ID;
						$lp_meta  = array_filter(
							array(
								$lp_count ? sprintf( _n( '%d CLASS', '%d CLASSES', $lp_count, 'londonparkour_v8' ), $lp_count ) : '',
								strtoupper( $lp_type ),
							)
						);
						$lp_lat = trim( (string) get_field( 'latitude', $lp_site->ID ) );
						$lp_lon = trim( (string) get_field( 'longitude', $lp_site->ID ) );
						?>
						<li
							data-site-flyto
							data-kind="site"
							data-site-id="<?php echo esc_attr( $lp_slug ); ?>"
							data-lat="<?php echo esc_attr( $lp_lat ); ?>"
							data-lon="<?php echo esc_attr( $lp_lon ); ?>"
						>
							<?php
							lp_part(
								'components/list-row',
								array(
									'surface' => 'page',
									'index'   => str_pad( (string) ( (int) $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
									'title'   => get_the_title( $lp_site ),
									'meta'    => implode( ' · ', $lp_meta ),
									'href'    => '#site-' . $lp_slug,
								)
							);
							?>
						</li>
					<?php endforeach; ?>
					<?php if ( ! $lp_sites ) : ?>
						<li class="px-[22px] py-4 font-label text-[11px] uppercase tracking-[0.8px] text-base-content/50">No class locations yet.</li>
					<?php endif; ?>
				</ul>

				<ul role="list" class="flex flex-col m-0 p-0 list-none hidden" data-map-list="spots" hidden>
					<?php
					foreach ( $lp_spots as $lp_i => $lp_spot ) :
						$lp_slug = $lp_spot->post_name ? $lp_spot->post_name : (string) $lp_spot->ID;
						$lp_lat  = trim( (string) get_field( 'latitude', $lp_spot->ID ) );
						$lp_lon  = trim( (string) get_field( 'longitude', $lp_spot->ID ) );
						$lp_sv   = lp_location_streetview_url( (int) $lp_spot->ID );
						?>
						<li
							data-site-flyto
							data-kind="spot"
							data-site-id="<?php echo esc_attr( $lp_slug ); ?>"
							data-lat="<?php echo esc_attr( $lp_lat ); ?>"
							data-lon="<?php echo esc_attr( $lp_lon ); ?>"
							data-streetview="<?php echo esc_attr( $lp_sv ); ?>"
						>
							<?php
							lp_part(
								'components/list-row',
								array(
									'surface' => 'page',
									'index'   => str_pad( (string) ( (int) $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
									'title'   => get_the_title( $lp_spot ),
									'meta'    => 'TRAINING SPOT',
									'href'    => $lp_sv ? $lp_sv : '#',
								)
							);
							?>
						</li>
					<?php endforeach; ?>
					<?php if ( ! $lp_spots ) : ?>
						<li class="px-[22px] py-4 font-label text-[11px] uppercase tracking-[0.8px] text-base-content/50">No training spots yet.</li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="w-full flex-1 min-w-0 flex flex-col bg-base-300 overflow-hidden min-h-0" data-map-panel>
				<div class="px-[22px] py-3 bg-base-100">
					<?php
					lp_part(
						'components/meta-row',
						array(
							'surface' => 'page',
							'left'    => 'CLASS NETWORK',
							'right'   => 'CLICK PIN FOR DETAILS ↗',
						)
					);
					?>
				</div>

				<div class="relative w-full min-h-[480px] lg:min-h-[640px] h-[min(75vh,800px)] overflow-hidden bg-neutral" data-component="site-network-map">
					<div class="absolute inset-0 z-0" data-mount="leaflet"></div>
					<template data-site-pins>
						<?php
						foreach ( $lp_map_places as $lp_place ) :
							$lp_lat = trim( (string) get_field( 'latitude', $lp_place->ID ) );
							$lp_lon = trim( (string) get_field( 'longitude', $lp_place->ID ) );
							if ( '' === $lp_lat || '' === $lp_lon ) {
								continue;
							}

							$lp_kind  = lp_location_kind( (int) $lp_place->ID );
							$lp_slug  = $lp_place->post_name ? $lp_place->post_name : (string) $lp_place->ID;
							$lp_sv    = lp_location_streetview_url( (int) $lp_place->ID );
							$lp_is_spot = 'spot' === $lp_kind;

							if ( $lp_is_spot ) {
								$lp_sub = 'TRAINING SPOT';
							} else {
								$lp_type  = (string) get_field( 'type', $lp_place->ID );
								$lp_count = (int) ( $lp_count_by_site[ $lp_place->ID ] ?? 0 );
								$lp_sub   = implode(
									' · ',
									array_filter(
										array(
											$lp_count ? sprintf( _n( '%d CLASS', '%d CLASSES', $lp_count, 'londonparkour_v8' ), $lp_count ) : '',
											strtoupper( $lp_type ),
										)
									)
								);
							}
							?>
							<div
								data-site-pin
								data-kind="<?php echo esc_attr( $lp_kind ); ?>"
								data-site-id="<?php echo esc_attr( $lp_slug ); ?>"
								data-name="<?php echo esc_attr( get_the_title( $lp_place ) ); ?>"
								data-lat="<?php echo esc_attr( $lp_lat ); ?>"
								data-lon="<?php echo esc_attr( $lp_lon ); ?>"
								data-streetview="<?php echo esc_attr( $lp_sv ); ?>"
							>
								<?php if ( $lp_is_spot ) : ?>
									<span data-spot-marker>
										<?php
										lp_part(
											'components/map-pin',
											array(
												'variant'  => 'icon',
												'name'     => get_the_title( $lp_place ),
												'flagship' => true,
												'label'    => false,
											)
										);
										?>
									</span>
									<template data-spot-popup>
										<?php
										lp_part(
											'components/map-pin',
											array(
												'variant'  => 'icon',
												'name'     => get_the_title( $lp_place ),
												'sub'      => $lp_sub,
												'flagship' => true,
											)
										);
										?>
									</template>
								<?php else : ?>
									<?php
									lp_part(
										'components/map-pin',
										array(
											'variant' => 'icon',
											'name'    => get_the_title( $lp_place ),
											'sub'     => $lp_sub,
										)
									);
									?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</template>
				</div>

				<div class="flex flex-wrap items-center justify-between gap-4 px-[22px] py-3 bg-base-100">
					<div class="flex items-center gap-[24px]">
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => 'CLASS',
								'icon_id' => 'icon-map-pin',
								'surface' => 'page',
								'tone'    => 'ink',
							)
						);
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => 'SPOT',
								'icon_id' => 'icon-map-pin',
								'surface' => 'page',
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
		<div class="w-full bg-base-200" data-component="map-meeting-points">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
				<?php
				lp_part(
					'components/section-head',
					array(
						'surface' => 'page',
						'eyebrow' => 'MEETING POINTS & TRAVEL',
						'heading' => 'Where to stand when you arrive.',
						'note'    => 'Coaches are on the meeting point ten minutes before.',
					)
				);
				?>
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16">
					<?php
					foreach ( $lp_sites as $lp_site ) :
						$lp_type  = (string) get_field( 'type', $lp_site->ID );
						$lp_tag   = (string) get_field( 'tag', $lp_site->ID );
						$lp_count = (int) ( $lp_count_by_site[ $lp_site->ID ] ?? 0 );
						$lp_slug  = $lp_site->post_name ? $lp_site->post_name : (string) $lp_site->ID;
						$lp_sv    = lp_location_streetview_url( (int) $lp_site->ID );
						?>
						<div id="site-<?php echo esc_attr( $lp_slug ); ?>">
							<?php
							lp_part(
								'components/site-panel',
								array(
									'kicker'          => $lp_tag !== '' ? $lp_tag : strtoupper( $lp_type ),
									'kind'            => strtolower( $lp_type ),
									'name'            => get_the_title( $lp_site ) . '.',
									'code'            => (string) get_field( 'meta', $lp_site->ID ),
									'count'           => $lp_count ? sprintf( _n( '%d CLASS', '%d CLASSES', $lp_count, 'londonparkour_v8' ), $lp_count ) : '',
									'meeting_point'   => (string) get_field( 'meeting_point', $lp_site->ID ),
									'transport_rail'  => (string) get_field( 'transport_rail', $lp_site->ID ),
									'transport_bus'   => (string) get_field( 'transport_bus', $lp_site->ID ),
									'streetview_href' => $lp_sv,
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
				'keyword' => '← AGENDA VIEW',
				'label'   => 'Everything on this week, by day',
				'href'    => lp_classes_page_url( 'classes' ),
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
