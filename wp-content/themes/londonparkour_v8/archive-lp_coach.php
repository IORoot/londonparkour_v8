<?php
/**
 * archive-lp_coach.php — coach roster at /coaches/.
 *
 * There is no Storybook Coaches Index page. The grid, card type, and copy are
 * the homepage Coaches `grid` layout (src/stories/Blocks/Coaches/Coaches.js /
 * blocks/coaches/coaches.php). Page chrome (breadcrumb, masthead, onward)
 * follows archive-lp_location.php.
 *
 * DELIBERATE DEVIATION from the homepage block: portraits use media-photo
 * `layout=contain` + WordPress `large` (soft scale, not a hard crop) in an
 * `aspect-square` box, instead of the block's fill/`lp_portrait_lg` in
 * `aspect-[3/4]`. The archive was falling through to blog cards
 * (`aspect-[16/9]` + `object-cover` + `lp_wide`), which cropped every
 * portrait. Live coach uploads are 1:1; a 3:4 contain box letterboxed them.
 * The homepage Coaches block is unchanged.
 *
 * Cards link through to single-lp_coach.php — the homepage grid is not linked.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_classes = lp_classes_page_url( 'classes' );

$lp_coaches = array();
$lp_i       = 0;
while ( have_posts() ) {
	the_post();
	++$lp_i;
	$lp_id        = (int) get_the_ID();
	$lp_coaches[] = array(
		'index' => sprintf( '%02d', $lp_i ),
		'name'  => get_the_title( $lp_id ),
		'role'  => (string) get_field( 'role', $lp_id ),
		'bio'   => (string) get_field( 'bio', $lp_id ),
		'href'  => (string) get_permalink( $lp_id ),
		'photo' => has_post_thumbnail( $lp_id ) ? (int) get_post_thumbnail_id( $lp_id ) : 0,
	);
}

$lp_count = count( $lp_coaches );
$lp_meta  = $lp_count ? sprintf( '(%02d)', $lp_count ) : '';

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
				array( 'label' => 'COACHES' ),
			),
			'action' => array(
				'label' => 'TRAIN WITH US ↗',
				'href'  => $lp_classes,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'The people who teach the practice.',
			'note'  => 'London-based coaches with decades of experience — teaching across the city since 2005.',
		)
	);
	?>

	<?php if ( $lp_coaches ) : ?>
		<section class="w-full bg-base-100 px-6 py-16 lg:py-[120px] lg:px-16" data-component="coaches" data-layout="grid">
			<div class="flex flex-col gap-8 lg:gap-12">
				<header class="flex flex-col gap-[18px]">
					<div class="flex items-baseline justify-between gap-4">
						<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65">COACHES / THE TEAM</span>
						<?php if ( '' !== $lp_meta ) : ?>
							<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_meta ); ?></span>
						<?php endif; ?>
					</div>
					<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
				</header>

				<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
					<?php foreach ( $lp_coaches as $lp_coach ) : ?>
						<article class="flex flex-col" data-component="coach-grid-card">
							<a href="<?php echo esc_url( (string) ( $lp_coach['href'] ?? '' ) ); ?>" class="relative aspect-square overflow-hidden bg-base-300">
								<?php
								if ( ! empty( $lp_coach['photo'] ) ) {
									lp_part(
										'components/media-photo',
										array(
											'image_id' => (int) $lp_coach['photo'],
											'alt'      => (string) ( $lp_coach['name'] ?? '' ),
											'layout'   => 'contain',
											'size'     => 'large',
											'sizes'    => '(min-width: 1024px) 25vw, 50vw',
											'class'    => 'object-top',
										)
									);
								}
								?>
							</a>
							<div class="flex flex-col gap-1.5 lg:gap-2.5 pt-3 lg:pt-5">
								<div class="flex items-baseline justify-between gap-3">
									<span class="font-label text-[11px] font-semibold tracking-[0.6px] text-base-content/65"><?php echo esc_html( (string) ( $lp_coach['index'] ?? '' ) ); ?></span>
								</div>
								<h3 class="font-heading text-[16px] lg:text-[22px] font-semibold tracking-[-0.4px] leading-tight text-base-content m-0">
									<a href="<?php echo esc_url( (string) ( $lp_coach['href'] ?? '' ) ); ?>" class="hover:text-accent transition-colors duration-150"><?php echo esc_html( (string) ( $lp_coach['name'] ?? '' ) ); ?></a>
								</h3>
								<p class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-base-content/65 m-0"><?php echo esc_html( (string) ( $lp_coach['role'] ?? '' ) ); ?></p>
								<?php if ( '' !== (string) ( $lp_coach['bio'] ?? '' ) ) : ?>
									<p class="font-body text-[12px] lg:text-[13px] leading-[1.45] lg:leading-[1.55] text-base-content/70 m-0"><?php echo esc_html( (string) $lp_coach['bio'] ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<footer class="flex flex-col gap-4">
					<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
					<div class="flex items-baseline justify-between gap-4 flex-wrap">
						<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-base-content/65">LONDON-BASED COACHES — TEACHING ACROSS THE CITY SINCE 2005</span>
						<a href="<?php echo esc_url( $lp_classes ); ?>" class="font-label text-[11px] font-semibold tracking-[0.5px] uppercase text-accent hover:text-accent/70 transition-colors">TRAIN WITH US →</a>
					</div>
				</footer>
			</div>
		</section>
	<?php endif; ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← HOME',
				'label'   => 'London Parkour',
				'href'    => home_url( '/' ),
			),
			'next' => array(
				'keyword' => 'TRAIN WITH US →',
				'label'   => 'Find a class',
				'href'    => $lp_classes,
			),
		)
	);
	?>
</main>

<?php
get_footer();
