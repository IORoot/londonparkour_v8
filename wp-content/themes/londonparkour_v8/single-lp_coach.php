<?php
/**
 * single-lp_coach.php — coach profile.
 *
 * There is no Storybook Coach Detail page. Without this template the CPT
 * falls through to single.php (BlogDetail), which runs lp_blog_parse_markdown()
 * / lp_blog_inline_markdown() and esc_html()s imported HTML bios.
 *
 * Layout is composed from existing Concourse pieces:
 *   - Page chrome: breadcrumb-rail, page-masthead (no photo — masthead media
 *     is a 16:9 cover crop), fact-row rail, page-onward.
 *   - Portrait + quote: Coaches lead column (blocks/coaches/coaches.php),
 *     with media-photo `layout=plain` + `large` so the upload is not cropped.
 *   - Body HTML: the ClassDetail / WorkshopDetail wp_kses_post wrapper
 *     (single-clasbpro_class.php). Skip the_content / wpautop on markup that
 *     already has block tags.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lp_id      = (int) get_the_ID();
	$lp_post    = get_post( $lp_id );
	$lp_title   = get_the_title( $lp_id );
	$lp_archive = (string) get_post_type_archive_link( 'lp_coach' );
	$lp_classes = lp_classes_page_url( 'classes' );

	$lp_role      = (string) get_field( 'role', $lp_id );
	$lp_specialty = (string) get_field( 'specialty', $lp_id );
	$lp_quote     = (string) get_field( 'quote', $lp_id );
	$lp_bio       = (string) get_field( 'bio', $lp_id );

	$lp_location_id = (int) get_field( 'location', $lp_id );
	$lp_location    = $lp_location_id ? get_the_title( $lp_location_id ) : '';

	$lp_photo = has_post_thumbnail( $lp_id ) ? (int) get_post_thumbnail_id( $lp_id ) : 0;

	$lp_body = $lp_post ? (string) $lp_post->post_content : '';
	if ( '' === trim( wp_strip_all_tags( $lp_body ) ) ) {
		$lp_body = $lp_bio;
	}
	if ( '' !== $lp_body && false === strpos( $lp_body, '<' ) ) {
		$lp_body = wpautop( $lp_body );
	}

	$lp_facts = array();
	if ( '' !== $lp_role ) {
		$lp_facts[] = array(
			'label' => 'ROLE',
			'value' => $lp_role,
		);
	}
	if ( '' !== $lp_specialty ) {
		$lp_facts[] = array(
			'label' => 'SPECIALTY',
			'value' => $lp_specialty,
		);
	}
	if ( '' !== $lp_location ) {
		$lp_facts[] = array(
			'label' => 'SITE',
			'value' => $lp_location,
		);
	}

	$lp_prev_post = get_adjacent_post( false, '', true );
	$lp_next_post = get_adjacent_post( false, '', false );

	$lp_onward_prev = array(
		'keyword' => '← ALL COACHES',
		'label'   => 'The people who teach the practice.',
		'href'    => $lp_archive,
	);
	if ( $lp_prev_post ) {
		$lp_prev_role     = (string) get_field( 'role', $lp_prev_post );
		$lp_onward_prev   = array(
			'keyword' => '← ' . strtoupper( get_the_title( $lp_prev_post ) ),
			'label'   => '' !== $lp_prev_role ? $lp_prev_role : get_the_title( $lp_prev_post ),
			'href'    => (string) get_permalink( $lp_prev_post ),
		);
	}

	$lp_onward_next = array(
		'keyword' => 'TRAIN WITH US →',
		'label'   => 'Find a class',
		'href'    => $lp_classes,
	);
	if ( $lp_next_post ) {
		$lp_next_role     = (string) get_field( 'role', $lp_next_post );
		$lp_onward_next   = array(
			'keyword' => strtoupper( get_the_title( $lp_next_post ) ) . ' →',
			'label'   => '' !== $lp_next_role ? $lp_next_role : get_the_title( $lp_next_post ),
			'href'    => (string) get_permalink( $lp_next_post ),
		);
	}
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
						'label' => 'COACHES',
						'href'  => $lp_archive,
					),
					array( 'label' => strtoupper( $lp_title ) ),
				),
				'action' => array(
					'label' => 'ALL COACHES ↗',
					'href'  => $lp_archive,
				),
			)
		);

		lp_part(
			'components/page-masthead',
			array(
				'title' => $lp_title,
				'note'  => $lp_role,
			)
		);
		?>

		<?php if ( $lp_facts ) : ?>
			<div class="w-full bg-neutral" data-component="coach-detail-fact-rail">
				<div class="px-6 lg:px-16 py-scale-s grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-x-[40px] gap-y-6" data-mount="rail">
					<?php foreach ( $lp_facts as $lp_fact ) : ?>
						<?php lp_part( 'components/fact-row', array_merge( $lp_fact, array( 'surface' => 'board' ) ) ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<section class="w-full bg-base-100 px-6 py-16 lg:py-[120px] lg:px-16" data-component="coach-detail-profile">
			<div class="flex flex-col lg:flex-row gap-[72px] items-start">
				<div class="w-full lg:w-[556px] lg:shrink-0 flex flex-col">
					<?php if ( $lp_photo ) : ?>
						<div class="relative w-full bg-base-300">
							<?php
							lp_part(
								'components/media-photo',
								array(
									'image_id'      => $lp_photo,
									'alt'           => $lp_title,
									'layout'        => 'plain',
									'size'          => 'large',
									'sizes'         => '(min-width: 1024px) 556px, 100vw',
									'loading'       => 'eager',
									'fetchpriority' => 'high',
								)
							);
							?>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $lp_quote ) : ?>
						<div class="mt-[26px] flex flex-col gap-[14px]">
							<p class="font-body text-step--1 font-normal tracking-[0.2px] leading-[1.6] text-base-content/70"><?php echo esc_html( $lp_quote ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<div class="flex-1 min-w-0 flex flex-col gap-[28px]">
					<?php if ( '' !== trim( wp_strip_all_tags( $lp_body ) ) ) : ?>
						<?php /* Div not <p>: post content may already contain block tags. */ ?>
						<div class="m-0 font-label text-[15px] font-normal leading-[1.75] tracking-[0.1px] text-base-content/80 flex flex-col gap-[22px] [&_a]:text-accent [&_ul]:list-disc [&_ol]:list-decimal [&_ul]:pl-5 [&_ol]:pl-5"><?php echo wp_kses_post( $lp_body ); ?></div>
					<?php endif; ?>
					<?php
					lp_part(
						'elements/text-link',
						array(
							'label'   => 'TRAIN WITH US →',
							'href'    => $lp_classes,
							'variant' => 'page_accent',
							'class'   => 'inline-block self-start',
						)
					);
					?>
				</div>
			</div>
		</section>

		<?php
		lp_part(
			'components/page-onward',
			array(
				'prev' => $lp_onward_prev,
				'next' => $lp_onward_next,
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();
