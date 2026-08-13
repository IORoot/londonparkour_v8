<?php
/**
 * Tutorials — Homepage "06 Tutorials" dark board: featured series + shelf.
 *
 * Ported from src/stories/Blocks/Tutorials/Tutorials.js.
 *
 * Featured is its own group (Locations/Coaches pattern). The shelf takes the
 * term source control against lp_series via lp_resolve_term_source(). Manual
 * rows and term records project to the same shelf-card shape.
 *
 * Fixed dark board (`bg-secondary`); content uses the `neutral-content` family.
 *
 * @param string $args['eyebrow']
 * @param string $args['meta']
 * @param string $args['kicker']
 * @param string $args['title']
 * @param string $args['note']
 * @param array  $args['featured']
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

$lp_eyebrow     = (string) ( $args['eyebrow'] ?? '06 — TUTORIALS' );
$lp_meta        = (string) ( $args['meta'] ?? '8 SERIES · 48 EPISODES' );
$lp_kicker      = (string) ( $args['kicker'] ?? 'NOW STREAMING' );
$lp_title       = (string) ( $args['title'] ?? 'Series worth watching.' );
$lp_note        = (string) ( $args['note'] ?? 'Taught progressions you can follow at home — then bring to class. Open a line and work the episodes in order.' );
$lp_shelf_label = (string) ( $args['shelf_label'] ?? 'MORE ON THE BOARD' );

$lp_featured = is_array( $args['featured'] ?? null ) ? $args['featured'] : array();
if ( '' === (string) ( $lp_featured['title'] ?? '' ) ) {
	$lp_featured = $lp_default_featured;
}

$lp_feat_tag       = (string) ( $lp_featured['tag'] ?? '' );
$lp_feat_series    = (string) ( $lp_featured['series'] ?? '' );
$lp_feat_title     = (string) ( $lp_featured['title'] ?? '' );
$lp_feat_logline   = (string) ( $lp_featured['logline'] ?? '' );
$lp_feat_meta      = (string) ( $lp_featured['meta'] ?? '' );
$lp_feat_cta       = (string) ( $lp_featured['cta_label'] ?? 'WATCH SERIES' );
$lp_feat_href      = (string) ( $lp_featured['href'] ?? '#' );
$lp_feat_poster    = ! empty( $lp_featured['poster'] ) ? (int) $lp_featured['poster'] : 0;

$lp_shelf_cta = lp_action( $args['shelf_cta'] ?? null );
if ( ! $lp_shelf_cta ) {
	$lp_shelf_cta = array(
		'label'  => 'BROWSE ALL SERIES →',
		'href'   => '/tutorials',
		'target' => '',
	);
}

/**
 * Project a resolved term/manual row into a shelf card.
 *
 * @param array $item Source item.
 * @return array{tag:string,title:string,episodes:string,href:string,poster:int,poster_alt:string}
 */
$lp_project_shelf = static function ( array $item ): array {
	$episodes = (string) ( $item['episodes'] ?? '' );
	if ( '' === $episodes && isset( $item['episode_count'] ) && '' !== (string) $item['episode_count'] ) {
		$episodes = (int) $item['episode_count'] . ' EPS';
	}

	$href = (string) ( $item['href'] ?? '' );
	if ( '' === $href ) {
		$href = (string) ( $item['url'] ?? '#' );
	}

	return array(
		'tag'        => (string) ( $item['tag'] ?? '' ),
		'title'      => (string) ( $item['title'] ?? '' ),
		'episodes'   => $episodes,
		'href'       => $href ? $href : '#',
		'poster'     => ! empty( $item['poster'] ) ? (int) $item['poster'] : 0,
		'poster_alt' => (string) ( $item['poster_alt'] ?? '' ),
	);
};

$lp_shelf = array_map( $lp_project_shelf, lp_resolve_term_source( $args, 'lp_series' ) );
$lp_shelf = array_values(
	array_filter(
		$lp_shelf,
		static function ( array $card ): bool {
			return '' !== $card['title'];
		}
	)
);
if ( ! $lp_shelf ) {
	$lp_shelf = array_map( $lp_project_shelf, $lp_default_shelf );
}

$lp_spacing = lp_section_spacing( $args );
?>
<section
	class="<?php echo lp_classes( 'w-full bg-secondary px-6 pt-[96px] pb-[100px] lg:px-16', $lp_spacing ); ?>"
	data-component="tutorials"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<div class="flex flex-col gap-10">
		<header class="flex flex-col gap-4">
			<div class="flex items-baseline justify-between gap-4 flex-wrap">
				<span class="font-label text-[12px] font-semibold tracking-[0.5px] uppercase text-primary"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<span class="font-label text-[11px] font-normal tracking-[0.6px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_meta ); ?></span>
			</div>
			<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
		</header>

		<div class="flex flex-col lg:flex-row lg:items-end gap-6 lg:gap-10">
			<div class="flex flex-col gap-4 max-w-[640px]">
				<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary"><?php echo esc_html( $lp_kicker ); ?></span>
				<h2 class="font-heading text-step-3 font-semibold leading-[1.02] tracking-[-1.6px] text-neutral-content m-0"><?php echo esc_html( $lp_title ); ?></h2>
			</div>
			<p class="font-body text-[14px] leading-[1.6] text-neutral-content/70 max-w-[420px] m-0 lg:ml-auto"><?php echo esc_html( $lp_note ); ?></p>
		</div>

		<a
			href="<?php echo esc_url( $lp_feat_href ); ?>"
			class="grid lg:grid-cols-[340px_1fr] bg-secondary no-underline overflow-hidden"
			data-component="tutorials-featured"
		>
			<div class="relative min-h-[220px] bg-neutral overflow-hidden">
				<?php
				if ( $lp_feat_poster ) {
					$lp_photo = array(
						'image_id' => $lp_feat_poster,
						'scrim'    => 'none',
						'size'     => 'lp_wide',
						'sizes'    => '(min-width: 1024px) 340px, 100vw',
					);
					if ( array_key_exists( 'poster_alt', $lp_featured ) ) {
						$lp_photo['alt'] = (string) $lp_featured['poster_alt'];
					}
					lp_part( 'components/media-photo', $lp_photo );
				}
				?>
			</div>
			<div class="flex flex-col justify-between gap-6 p-7 lg:p-9">
				<div class="flex flex-col gap-4">
					<div class="flex items-center gap-2.5 flex-wrap">
						<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-primary"><?php echo esc_html( $lp_feat_tag ); ?></span>
						<span class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_feat_series ); ?></span>
					</div>
					<h3 class="font-heading text-[28px] font-semibold tracking-[-0.8px] text-neutral-content m-0"><?php echo esc_html( $lp_feat_title ); ?></h3>
					<p class="font-body text-[14px] leading-[1.55] text-neutral-content/70 m-0 max-w-[520px]"><?php echo esc_html( $lp_feat_logline ); ?></p>
				</div>
				<div class="flex items-center justify-between gap-4 flex-wrap">
					<span class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_feat_meta ); ?></span>
					<span class="font-label text-[11px] font-semibold tracking-[0.8px] uppercase text-primary"><?php echo esc_html( $lp_feat_cta ); ?> →</span>
				</div>
			</div>
		</a>

		<div class="flex flex-col gap-5">
			<div class="flex items-baseline justify-between gap-4">
				<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_shelf_label ); ?></span>
				<a
					href="<?php echo esc_url( $lp_shelf_cta['href'] ); ?>"
					class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-primary hover:text-primary/70 transition-colors"
					<?php echo ! empty( $lp_shelf_cta['target'] ) ? ' target="' . esc_attr( $lp_shelf_cta['target'] ) . '"' : ''; ?>
				><?php echo esc_html( $lp_shelf_cta['label'] ); ?></a>
			</div>
			<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
				<?php foreach ( $lp_shelf as $lp_card ) : ?>
					<a
						href="<?php echo esc_url( $lp_card['href'] ); ?>"
						class="flex flex-col bg-neutral hover:bg-secondary transition-colors duration-150 no-underline min-h-[160px]"
						data-component="tutorials-shelf-card"
					>
						<div class="relative aspect-[16/10] bg-secondary overflow-hidden">
							<?php
							if ( $lp_card['poster'] ) {
								$lp_card_photo = array(
									'image_id' => $lp_card['poster'],
									'scrim'    => 'none',
									'size'     => 'lp_wide',
									'sizes'    => '(min-width: 1024px) 25vw, 50vw',
								);
								if ( '' !== $lp_card['poster_alt'] || array_key_exists( 'poster_alt', $lp_card ) ) {
									$lp_card_photo['alt'] = $lp_card['poster_alt'];
								}
								lp_part( 'components/media-photo', $lp_card_photo );
							}
							?>
						</div>
						<div class="flex flex-col gap-2 p-4">
							<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-primary"><?php echo esc_html( $lp_card['tag'] ); ?></span>
							<span class="font-heading text-[16px] font-semibold tracking-[-0.3px] text-neutral-content"><?php echo esc_html( $lp_card['title'] ); ?></span>
							<span class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_card['episodes'] ); ?></span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
