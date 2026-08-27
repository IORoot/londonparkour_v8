<?php
/**
 * archive-lp_location.php — class-site listing at /classes/locations/.
 *
 * Sites only (spots are stripped in lp_filter_location_archive). Copy and
 * SitePanel markup come from the Classes Map meeting-points band.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_sites    = function_exists( 'lp_locations_by_kind' ) ? lp_locations_by_kind( 'site' ) : array();
$lp_site_n   = count( $lp_sites );
$lp_classes  = lp_classes_page_url( 'classes' );
$lp_map      = lp_classes_page_url( 'classes-map' );
$lp_listings = function_exists( 'lp_classes_listings_url' ) ? lp_classes_listings_url() : $lp_classes;

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

get_header();
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
				array( 'label' => 'LOCATIONS' ),
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
			'title' => $lp_sites_lbl,
			'note'  => 'Find your class on the map, then scroll for meeting points and travel details. Every site is a ten-minute walk from a tube or overground station.',
		)
	);
	?>

	<?php if ( $lp_sites ) : ?>
		<div class="w-full bg-base-200" data-component="location-archive-sites">
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
				<div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16 gap-y-24 lg:gap-y-16">
					<?php
					foreach ( $lp_sites as $lp_site ) :
						$lp_sid   = (int) $lp_site->ID;
						$lp_type  = (string) get_field( 'type', $lp_sid );
						$lp_tag   = (string) get_field( 'tag', $lp_sid );
						$lp_count = function_exists( 'lp_location_class_count_label' ) ? lp_location_class_count_label( $lp_sid ) : '';
						$lp_sv    = lp_location_streetview_url( $lp_sid );
						?>
						<div id="site-<?php echo esc_attr( $lp_site->post_name ); ?>">
							<?php
							lp_part(
								'components/site-panel',
								array(
									'kicker'          => $lp_tag !== '' ? $lp_tag : strtoupper( $lp_type ),
									'kind'            => strtolower( $lp_type ),
									'name'            => get_the_title( $lp_site ) . '.',
									'code'            => (string) get_field( 'meta', $lp_sid ),
									'count'           => $lp_count,
									'meeting_point'   => (string) get_field( 'meeting_point', $lp_sid ),
									'transport_rail'  => (string) get_field( 'transport_rail', $lp_sid ),
									'transport_bus'   => (string) get_field( 'transport_bus', $lp_sid ),
									'streetview_href' => $lp_sv,
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
			'next' => array(
				'keyword' => 'ALL CLASSES →',
				'label'   => 'Back to the class listings',
				'href'    => $lp_listings,
			),
		)
	);
	?>
</main>

<?php
get_footer();
