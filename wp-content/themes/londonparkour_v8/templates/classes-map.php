<?php
/**
 * Template Name: Classes — Map
 *
 * ClassesMap — Storybook `ClassesMap.js`. Sites come from published
 * `lp_location` posts (real lat/lon). The Network map is Leaflet + Carto
 * Dark Matter (`assets/js/elements/SiteNetworkMap.js`).
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

$lp_count_by_site = array();

foreach ( $lp_classes as $lp_class ) {
	$lp_site_id = lp_class_location_id( (int) $lp_class->ID );

	if ( $lp_site_id ) {
		$lp_count_by_site[ $lp_site_id ] = ( $lp_count_by_site[ $lp_site_id ] ?? 0 ) + 1;
	}
}

$lp_site_n    = count( $lp_sites );
$lp_class_n   = count( $lp_classes );
$lp_sites_lbl = sprintf(
	/* translators: %d: number of training sites */
	_n( '%d site. One network.', '%d sites. One network.', $lp_site_n, 'londonparkour_v8' ),
	$lp_site_n
);
// SectionHead expects title case like the Storybook string.
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

$lp_streetview = static function ( int $lp_id ): string {
	$lp_custom = (string) get_field( 'streetview', $lp_id );
	if ( '' !== trim( $lp_custom ) ) {
		return $lp_custom;
	}

	$lp_lat = trim( (string) get_field( 'latitude', $lp_id ) );
	$lp_lon = trim( (string) get_field( 'longitude', $lp_id ) );
	if ( '' === $lp_lat || '' === $lp_lon ) {
		return '';
	}

	return sprintf(
		'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=%s,%s',
		rawurlencode( $lp_lat ),
		rawurlencode( $lp_lon )
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
		<div class="px-6 lg:px-16 pt-scale-2xl pb-10">
			<?php
			lp_part(
				'components/section-head',
				array(
					'surface' => 'board',
					'heading' => $lp_sites_lbl,
					'note'    => sprintf(
						/* translators: %d: number of weekly classes */
						_n(
							'Pick a pin to see what runs there. %d weekly class across London, and nothing further than ten minutes from a station.',
							'Pick a pin to see what runs there. %d weekly classes across London, and nothing further than ten minutes from a station.',
							$lp_class_n,
							'londonparkour_v8'
						),
						$lp_class_n
					),
				)
			);
			?>
		</div>
		<div class="flex flex-col lg:flex-row pb-scale-2xl px-6 lg:px-16">
			<div class="w-full lg:w-[300px] xl:w-[340px] lg:shrink-0 flex flex-col bg-base-100 mb-0">
				<div class="px-[22px] py-[15px]">
					<?php
					lp_part(
						'components/meta-row',
						array(
							'surface' => 'page',
							'left'    => 'SITES',
							'right'   => (string) $lp_site_n,
						)
					);
					?>
				</div>
				<?php if ( $lp_sites ) : ?>
					<ul role="list" class="flex flex-col m-0 p-0 list-none">
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
							?>
							<li>
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
					</ul>
				<?php endif; ?>
			</div>

			<div class="w-full flex-1 min-w-0 flex flex-col bg-base-300 overflow-hidden">
				<div class="px-[22px] py-3 bg-base-100">
					<?php
					lp_part(
						'components/meta-row',
						array(
							'surface' => 'page',
							'left'    => 'SITE NETWORK',
							'right'   => 'CLICK PIN FOR DETAILS ↗',
						)
					);
					?>
				</div>

				<div class="relative w-full min-h-[480px] lg:min-h-[640px] h-[min(75vh,800px)] overflow-hidden bg-neutral" data-component="site-network-map">
					<div class="absolute inset-0 z-0" data-mount="leaflet"></div>
					<template data-site-pins>
						<?php
						foreach ( $lp_sites as $lp_site ) :
							$lp_lat = trim( (string) get_field( 'latitude', $lp_site->ID ) );
							$lp_lon = trim( (string) get_field( 'longitude', $lp_site->ID ) );
							if ( '' === $lp_lat || '' === $lp_lon ) {
								continue;
							}

							$lp_type  = (string) get_field( 'type', $lp_site->ID );
							$lp_count = (int) ( $lp_count_by_site[ $lp_site->ID ] ?? 0 );
							$lp_sub   = array_filter(
								array(
									$lp_count ? sprintf( _n( '%d CLASS', '%d CLASSES', $lp_count, 'londonparkour_v8' ), $lp_count ) : '',
									strtoupper( $lp_type ),
								)
							);
							$lp_slug  = $lp_site->post_name ? $lp_site->post_name : (string) $lp_site->ID;
							?>
							<div
								data-site-pin
								data-site-id="<?php echo esc_attr( $lp_slug ); ?>"
								data-name="<?php echo esc_attr( get_the_title( $lp_site ) ); ?>"
								data-lat="<?php echo esc_attr( $lp_lat ); ?>"
								data-lon="<?php echo esc_attr( $lp_lon ); ?>"
							>
								<?php
								lp_part(
									'components/map-pin',
									array(
										'variant' => 'icon',
										'name'    => get_the_title( $lp_site ),
										'sub'     => implode( ' · ', $lp_sub ),
									)
								);
								?>
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
								'label'   => 'SITE',
								'icon_id' => 'icon-map-pin',
								'surface' => 'page',
								'tone'    => 'ink',
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
						'note'    => 'Coaches are on the meeting point ten minutes before the hour, holding a yellow flag.',
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
						$lp_sv    = $lp_streetview( (int) $lp_site->ID );
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
