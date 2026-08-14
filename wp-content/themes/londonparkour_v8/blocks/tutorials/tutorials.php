<?php
/**
 * Tutorials — Homepage "06 Tutorials" dark board: featured series + shelf.
 *
 * Ported from src/stories/Blocks/Tutorials/Tutorials.js (`y8c5z9` / `kXZsn`).
 *
 * When source is not manual, featured and shelf project from lp_series terms
 * with episode counts, runtime and posters taken from the lp_tutorial posts
 * in each series. Featured defaults to the series tagged START HERE.
 * Manual source keeps the editor-typed featured group and shelf rows.
 *
 * Fixed dark board (`bg-secondary`); content uses the `neutral-content` family.
 *
 * @param string $args['eyebrow']
 * @param string $args['meta']
 * @param string $args['kicker']
 * @param string $args['title']
 * @param string $args['note']
 * @param array  $args['featured']
 * @param int    $args['featured_series']
 * @param string $args['shelf_label']
 * @param array  $args['shelf_cta']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_featured = array(
	'tag'       => 'START HERE',
	'series'    => 'SERIES 01 · BEGINNER',
	'title'     => 'Step Vault Basics',
	'logline'   => 'The cleanest way over a wall. Eight episodes that turn a messy hop into a sharp, repeatable vault.',
	'meta'      => '8 EPISODES · 34 MIN · LEON LAWRENCE',
	'cta_label' => 'WATCH SERIES',
	'href'      => '/tutorials/step-vault-basics',
);

$lp_default_shelf = array(
	array(
		'tag'      => 'POPULAR',
		'title'    => 'Momentum Progressions',
		'episodes' => '6 EPS',
		'href'     => '/tutorials/momentum',
	),
	array(
		'tag'      => 'LEVEL UP',
		'title'    => 'High Wall Variants',
		'episodes' => '4 EPS',
		'href'     => '/tutorials/high-wall',
	),
	array(
		'tag'      => 'TECHNIQUE',
		'title'    => 'Precision Deep Dive',
		'episodes' => '7 EPS',
		'href'     => '/tutorials/precision',
	),
	array(
		'tag'      => 'ADVANCED',
		'title'    => 'Flow Combinations',
		'episodes' => '9 EPS',
		'href'     => '/tutorials/flow',
	),
);

$lp_source      = (string) ( $args['source'] ?? 'latest' );
$lp_eyebrow     = (string) ( $args['eyebrow'] ?? '06 — TUTORIALS' );
$lp_kicker      = (string) ( $args['kicker'] ?? 'NOW STREAMING' );
$lp_title       = (string) ( $args['title'] ?? 'Series worth watching.' );
$lp_note        = (string) ( $args['note'] ?? 'Taught progressions you can follow at home — then bring to class. Open a line and work the episodes in order.' );
$lp_shelf_label = (string) ( $args['shelf_label'] ?? 'MORE ON THE BOARD' );

$lp_featured_term = 0;
$lp_featured      = array();
$lp_from_cpt      = false;

if ( 'manual' !== $lp_source ) {
	$lp_featured_term = lp_tutorials_featured_series_id( lp_tutorials_term_id( $args['featured_series'] ?? 0 ) );
	if ( $lp_featured_term ) {
		$lp_projected = lp_series_project_featured( $lp_featured_term );
		if ( is_array( $lp_projected ) && '' !== (string) ( $lp_projected['title'] ?? '' ) ) {
			$lp_featured = $lp_projected;
			$lp_from_cpt = true;
		}
	}
}

if ( ! $lp_from_cpt ) {
	$lp_featured = is_array( $args['featured'] ?? null ) ? $args['featured'] : array();
	if ( '' === (string) ( $lp_featured['title'] ?? '' ) ) {
		$lp_featured = $lp_default_featured;
	}
}

$lp_feat_tag       = (string) ( $lp_featured['tag'] ?? '' );
$lp_feat_series    = (string) ( $lp_featured['series'] ?? '' );
$lp_feat_title     = (string) ( $lp_featured['title'] ?? '' );
$lp_feat_logline   = (string) ( $lp_featured['logline'] ?? '' );
$lp_feat_meta      = (string) ( $lp_featured['meta'] ?? '' );
$lp_feat_cta       = (string) ( $lp_featured['cta_label'] ?? 'WATCH SERIES' );
$lp_feat_href      = (string) ( $lp_featured['href'] ?? '#' );
$lp_feat_poster    = ! empty( $lp_featured['poster'] ) ? (int) $lp_featured['poster'] : 0;

if ( $lp_from_cpt ) {
	$lp_meta = lp_tutorials_board_meta();
} else {
	$lp_meta = (string) ( $args['meta'] ?? '8 SERIES · 48 EPISODES' );
}

$lp_shelf_cta = lp_action( $args['shelf_cta'] ?? null );
if ( ! $lp_shelf_cta ) {
	$lp_shelf_cta = array(
		'label'  => 'BROWSE ALL SERIES →',
		'href'   => lp_tutorials_series_url(),
		'target' => '',
	);
} else {
	$path = (string) wp_parse_url( (string) $lp_shelf_cta['href'], PHP_URL_PATH );
	if ( '/tutorials' === untrailingslashit( $path ) ) {
		$lp_shelf_cta['href'] = lp_tutorials_series_url();
	}
}

/**
 * Project a resolved term/manual row into a shelf card.
 *
 * @param array $item Source item.
 * @return array{tag:string,title:string,episodes:string,href:string,poster:int,poster_alt:string}
 */
$lp_project_shelf = static function ( array $item ): array {
	if ( ! empty( $item['id'] ) ) {
		$projected = lp_series_project_shelf( (int) $item['id'] );
		if ( is_array( $projected ) ) {
			return $projected;
		}
	}

	$episodes = (string) ( $item['episodes'] ?? '' );
	if ( '' === $episodes && isset( $item['episode_count'] ) && '' !== (string) $item['episode_count'] ) {
		$episodes = (int) $item['episode_count'] . ' EPS';
	}

	$href = (string) ( $item['href'] ?? '' );
	if ( '' === $href ) {
		$href = (string) ( $item['url'] ?? '#' );
	}

	$tag = strtoupper( trim( (string) ( $item['tag'] ?? '' ) ) );
	if ( '' === $tag ) {
		$tag = lp_series_shelf_eyebrow(
			array(
				'series_label' => (string) ( $item['series'] ?? $item['series_label'] ?? '' ),
				'tags'         => (string) ( $item['tags'] ?? '' ),
			)
		);
	}

	return array(
		'tag'        => $tag,
		'title'      => (string) ( $item['title'] ?? '' ),
		'episodes'   => $episodes,
		'href'       => $href ? $href : '#',
		'poster'     => ! empty( $item['poster'] ) ? (int) $item['poster'] : 0,
		'poster_alt' => (string) ( $item['poster_alt'] ?? '' ),
	);
};

$lp_shelf = array_map(
	$lp_project_shelf,
	lp_resolve_term_source(
		$args,
		'lp_series',
		array(
			'exclude'    => $lp_featured_term,
			'hide_empty' => 'manual' !== $lp_source,
			'orderby'    => 'name',
			'order'      => 'DESC',
		)
	)
);
$lp_shelf = array_values(
	array_filter(
		$lp_shelf,
		static function ( array $card ): bool {
			return '' !== $card['title'];
		}
	)
);
if ( ! $lp_shelf && ! $lp_from_cpt ) {
	$lp_shelf = array_map( $lp_project_shelf, $lp_default_shelf );
}

$lp_spacing = lp_section_spacing( $args );
?>
<section
	class="<?php echo lp_classes( 'w-full bg-secondary px-6 pt-[96px] pb-[100px] lg:px-16', $lp_spacing ); ?>"
	data-component="tutorials"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<header>
		<div class="flex items-baseline justify-between gap-4 flex-wrap pb-[14px]">
			<span class="font-label text-[11px] font-bold tracking-[1px] uppercase text-primary"><?php echo esc_html( $lp_eyebrow ); ?></span>
			<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_meta ); ?></span>
		</div>
		<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
	</header>

	<div class="flex flex-col gap-12 pt-10">
		<div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-10">
			<div class="flex flex-col gap-4 max-w-[640px]">
				<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-primary"><?php echo esc_html( $lp_kicker ); ?></span>
				<h2 class="font-heading text-[48px] font-semibold leading-[1.02] tracking-[-1.4px] text-neutral-content m-0"><?php echo esc_html( $lp_title ); ?></h2>
			</div>
			<p class="font-body text-[12px] leading-[1.6] text-neutral-content/70 max-w-[320px] m-0"><?php echo esc_html( $lp_note ); ?></p>
		</div>

		<a
			href="<?php echo esc_url( $lp_feat_href ); ?>"
			class="group grid lg:grid-cols-[1.2fr_1fr] bg-neutral no-underline border border-primary hover:bg-primary transition-colors duration-150"
			data-component="tutorials-featured"
		>
			<div class="relative aspect-[16/9] w-full bg-neutral">
				<?php
				if ( $lp_feat_poster ) {
					$lp_photo = array(
						'image_id' => $lp_feat_poster,
						'scrim'    => 'none',
						'layout'   => 'contain',
						'size'     => 'lp_wide',
						'sizes'    => '(min-width: 1024px) 55vw, 100vw',
					);
					if ( array_key_exists( 'poster_alt', $lp_featured ) ) {
						$lp_photo['alt'] = (string) $lp_featured['poster_alt'];
					}
					lp_part( 'components/media-photo', $lp_photo );
				}
				?>
				<span class="absolute inset-0 bg-gradient-to-r from-transparent via-neutral/60 via-70% to-neutral group-hover:via-primary/60 group-hover:to-primary pointer-events-none" aria-hidden="true"></span>
			</div>
			<div class="flex flex-col justify-center gap-4 px-10 py-9">
				<div class="flex items-center gap-2.5 flex-wrap">
					<?php if ( '' !== $lp_feat_tag ) : ?>
						<span class="inline-flex items-center py-[5px] px-[9px] bg-primary font-label text-[9px] font-bold tracking-[1px] uppercase text-primary-content group-hover:bg-neutral group-hover:text-primary"><?php echo esc_html( $lp_feat_tag ); ?></span>
					<?php endif; ?>
					<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( $lp_feat_series ); ?></span>
				</div>
				<h3 class="font-heading text-[40px] font-semibold tracking-[-1px] text-neutral-content m-0 group-hover:text-neutral"><?php echo esc_html( $lp_feat_title ); ?></h3>
				<p class="font-body text-[13px] leading-[1.55] text-neutral-content/70 m-0 group-hover:text-neutral/80"><?php echo esc_html( $lp_feat_logline ); ?></p>
				<div class="flex items-center justify-between gap-4 flex-wrap pt-2">
					<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( $lp_feat_meta ); ?></span>
					<span class="inline-flex items-center gap-2 py-3 px-4 bg-primary font-label text-[11px] font-bold tracking-[1px] uppercase text-primary-content group-hover:bg-neutral group-hover:text-primary">
						<?php lp_icon( 'icon-play', 'w-3.5 h-3.5 shrink-0' ); ?>
						<?php echo esc_html( $lp_feat_cta ); ?>
					</span>
				</div>
			</div>
		</a>
	</div>

	<div class="flex flex-col gap-4 pt-7">
		<div class="flex items-baseline justify-between gap-4">
			<span class="font-label text-[10px] font-bold tracking-[1.1px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_shelf_label ); ?></span>
			<a
				href="<?php echo esc_url( $lp_shelf_cta['href'] ); ?>"
				class="font-label text-[10px] font-bold tracking-[1px] uppercase text-primary hover:text-primary/70 transition-colors"
				<?php echo ! empty( $lp_shelf_cta['target'] ) ? ' target="' . esc_attr( $lp_shelf_cta['target'] ) . '"' : ''; ?>
			><?php echo esc_html( $lp_shelf_cta['label'] ); ?></a>
		</div>
		<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
			<?php foreach ( $lp_shelf as $lp_card ) : ?>
				<a
					href="<?php echo esc_url( $lp_card['href'] ); ?>"
					class="group flex flex-col bg-neutral hover:bg-primary transition-colors duration-150 no-underline border border-neutral-content/10"
					data-component="tutorials-shelf-card"
				>
					<div class="relative aspect-[16/9] w-full bg-neutral">
						<?php
						if ( $lp_card['poster'] ) {
							$lp_card_photo = array(
								'image_id' => $lp_card['poster'],
								'scrim'    => 'none',
								'layout'   => 'contain',
								'size'     => 'lp_wide',
								'sizes'    => '(min-width: 1024px) 25vw, 50vw',
							);
							if ( '' !== $lp_card['poster_alt'] || array_key_exists( 'poster_alt', $lp_card ) ) {
								$lp_card_photo['alt'] = $lp_card['poster_alt'];
							}
							lp_part( 'components/media-photo', $lp_card_photo );
						}
						?>
						<span class="absolute inset-0 bg-gradient-to-b from-transparent from-40% to-neutral/90 group-hover:to-primary/90 pointer-events-none" aria-hidden="true"></span>
					</div>
					<div class="flex flex-col gap-2 px-3.5 pt-3.5 pb-4">
						<span class="font-label text-[9px] font-bold tracking-[1px] uppercase text-primary group-hover:text-neutral"><?php echo esc_html( $lp_card['tag'] ); ?></span>
						<span class="font-heading text-[18px] font-semibold tracking-[-0.3px] text-neutral-content group-hover:text-neutral"><?php echo esc_html( $lp_card['title'] ); ?></span>
						<span class="font-label text-[10px] font-semibold tracking-[0.7px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( $lp_card['episodes'] ); ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
