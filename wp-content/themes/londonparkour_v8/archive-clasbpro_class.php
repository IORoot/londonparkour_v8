<?php
/**
 * archive-clasbpro_class.php — ClassesListings.
 *
 * Ported from src/stories/Pages/ClassesListings/ClassesListings.js (`KwyHc`).
 * Read that file's docblock before touching this one — it records which copy
 * was transcribed from the design file at leaf depth and which earlier pass
 * had wrongly hedged it as "representative".
 *
 * Section order: header cluster → all class types → onward.
 * Nav/footer are get_header()/get_footer(), outside the one <main>. The source
 * places the whole cluster BEFORE its <main> and its own docblock calls that a
 * defect it could not fix from the directory it owned — the masthead's <h1>
 * belongs inside the main landmark. parts/components/classes-header-cluster.php
 * does not render nav, so that is fixed here rather than carried over.
 *
 * The featured-class band (`ZlCME`) was removed by owner request — every
 * published class sits in the MediaCard grid. All Class Types stays inline
 * (MetaRow plus grid) and is not promoted — PORT-BRIEF rule 3a.
 *
 * Listings are deduped by title via lp_class_dedupe_by_title() so one product
 * name does not appear once per weekday clasbpro row.
 *
 * BOOK opens the shared booking drawer — no /book/… hrefs.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/** Flatten one class into components/media-card.php's args. */
$lp_card = static function ( WP_Post $lp_post ): array {
	$lp_id       = (int) $lp_post->ID;
	$lp_levels   = get_the_terms( $lp_post, 'lp_level' );
	$lp_location = lp_class_location_id( $lp_id );
	$lp_price    = lp_class_price_display( $lp_id );
	$lp_dur      = lp_class_duration( $lp_id );

	return array(
		'aspect'   => 'wide',
		'image_id' => lp_class_image_id( $lp_id ) ?: 0,
		'kicker'   => is_array( $lp_levels ) && $lp_levels ? strtoupper( $lp_levels[0]->name ) : '',
		'meta'     => $lp_location ? strtoupper( get_the_title( $lp_location ) ) : '',
		'title'    => get_the_title( $lp_post ),
		'note'     => get_the_excerpt( $lp_post ),
		'foot'     => trim( $lp_price . ( '' !== $lp_dur ? ' · ' . strtoupper( $lp_dur ) : '' ), ' ·' ),
		'href'     => (string) get_permalink( $lp_post ),
	);
};

$lp_classes = array();
while ( have_posts() ) {
	the_post();
	$lp_classes[] = get_post();
}

$lp_classes = lp_class_dedupe_by_title( $lp_classes );

$lp_arch = (string) get_post_type_archive_link( lp_class_post_type() );

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/classes-header-cluster',
		array(
			'crumbs'        => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'CLASSES',
					'href'  => lp_classes_page_url( 'classes' ),
				),
				array( 'label' => 'LISTINGS' ),
			),
			'action'        => array(
				'label' => 'AGENDA VIEW ↗',
				'href'  => lp_classes_page_url( 'classes' ),
			),
			'masthead'      => array(
				'title' => 'Every class we run.',
				'note'  => 'Filter by type, site, age or day. Every session is coach-led, capped at twelve, and fifteen pounds to drop in — no contract, ever.',
			),
			'active'        => '',
			'filter_action' => $lp_arch,
			'filter_values' => lp_class_filter_values(),
		)
	);
	?>

	<?php if ( $lp_classes ) : ?>
		<div class="w-full bg-base-100" data-component="listings-all-class-types">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
				<?php
				lp_part(
					'components/meta-row',
					array(
						'left'  => 'ALL CLASS TYPES',
						'right' => (string) count( $lp_classes ),
					)
				);
				?>
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
					<?php foreach ( $lp_classes as $lp_post ) : ?>
						<div><?php lp_part( 'components/media-card', $lp_card( $lp_post ) ); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php
		$lp_pagination = lp_pagination_args( null, 'CLASSES' );
		if ( $lp_pagination ) {
			lp_part( 'components/pagination', $lp_pagination );
		}
		?>
	<?php endif; ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← AGENDA VIEW',
				'label'   => 'This week, hour by hour',
				'href'    => lp_classes_page_url( 'classes' ),
			),
			'next' => array(
				'keyword' => 'MAP VIEW →',
				'label'   => 'Where we train across London',
				'href'    => lp_classes_page_url( 'classes-map' ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
