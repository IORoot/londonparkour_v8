<?php
/**
 * Hero — the page-opening claim beside the next-sessions board.
 *
 * Ported from src/stories/Blocks/Hero/Hero.js.
 *
 * Takes the CPT source control for the board; each row is
 * components/departure-row.php, which the source mounts the same way.
 *
 * The photo is media-photo's `hero` scrim (bg-neutral/90), which also makes it
 * eager + fetchpriority=high — this is the LCP element on the front page. With
 * no photo the source still lays a solid bg-neutral panel, so that stays.
 *
 * @param string $args['eyebrow']
 * @param string $args['headline']
 * @param string $args['lead']
 * @param array  $args['primary_action']
 * @param array  $args['secondary_action']
 * @param int    $args['media']            Attachment id.
 * @param string $args['media_alt']
 * @param string $args['board_title']
 * @param string $args['board_stamp']
 * @param string $args['board_foot_label']
 * @param string $args['board_foot_href']
 * @param string $args['board_foot_count']
 * @param string $args['scroll_label']
 * @param array  $args['trust']            Rows of array( 'label' => … ).
 * @param string $args['rating']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_sessions = array(
	array(
		'time'     => '18:30',
		'title'    => 'Beginners Parkour',
		'location' => 'Vauxhall · Level 1',
		'spaces'   => '4 LEFT',
	),
	array(
		'time'     => '19:45',
		'title'    => 'All Levels Open Gym',
		'location' => 'Stratford East · Open',
		'spaces'   => '2 LEFT',
	),
	array(
		'time'     => 'FRI 07:00',
		'title'    => 'Sunrise Session',
		'location' => 'Southbank · Open',
		'spaces'   => '9 SPACES',
	),
	array(
		'time'     => 'SAT 10:00',
		'title'    => 'Kids & Families',
		'location' => 'Peckham Rye · Ages 6–12',
		'spaces'   => 'WAITLIST',
	),
);

$lp_default_trust = array( '2,400+ TRAINED', '11 YEARS', '6 LONDON SITES' );

$lp_eyebrow    = (string) ( $args['eyebrow'] ?? 'PARKOUR CLASSES / LONDON / EST. 2015' );
$lp_headline   = (string) ( $args['headline'] ?? 'Every wall is a door.' );
$lp_lead       = (string) ( $args['lead'] ?? 'Parkour is the practice of getting where you want to go. We teach it across six London sites, to every age and every body. No experience needed.' );
$lp_board_ttl  = (string) ( $args['board_title'] ?? 'NEXT SESSIONS' );
$lp_board_stmp = (string) ( $args['board_stamp'] ?? 'UPDATED 09:12 · THU 30 JUL' );
$lp_foot_label = (string) ( $args['board_foot_label'] ?? 'View full timetable' );
$lp_foot_href  = (string) ( $args['board_foot_href'] ?? '#' );
$lp_foot_count = (string) ( $args['board_foot_count'] ?? '32 SESSIONS / WEEK' );
$lp_scroll     = (string) ( $args['scroll_label'] ?? '↓ SCROLL' );
$lp_rating     = (string) ( $args['rating'] ?? '4.9 ★ (312)' );

$lp_primary   = lp_action( $args['primary_action'] ?? null );
$lp_secondary = lp_action( $args['secondary_action'] ?? null );

// One query layer; the projection is this block's own. A class record gives
// time/title/location/spaces; a manual row supplies the same four names.
$lp_sessions = array_map(
	static function ( array $item ): array {
		return array(
			'time'     => (string) ( $item['time'] ?? '' ),
			'title'    => (string) ( $item['title'] ?? '' ),
			'location' => (string) ( $item['location'] ?? '' ),
			'spaces'   => (string) ( $item['spaces'] ?? '' ),
			'sold_out' => ! empty( $item['sold_out'] ),
			'href'     => (string) ( $item['url'] ?? '' ),
		);
	},
	lp_resolve_source( $args, 'lp_class', array( 'expand' => 'sessions' ) )
);

if ( ! $lp_sessions ) {
	$lp_sessions = $lp_default_sessions;
}

$lp_trust = array();

foreach ( is_array( $args['trust'] ?? null ) ? $args['trust'] : array() as $lp_row ) {
	$lp_text = is_array( $lp_row ) ? (string) ( $lp_row['label'] ?? '' ) : (string) $lp_row;
	if ( '' !== $lp_text ) {
		$lp_trust[] = $lp_text;
	}
}

if ( ! $lp_trust ) {
	$lp_trust = $lp_default_trust;
}

$lp_media_id  = ! empty( $args['media'] ) ? (int) $args['media'] : 0;
$lp_has_media = (bool) $lp_media_id;

$lp_spacing = lp_section_spacing( $args );
?>
<div class="<?php echo lp_classes( 'relative overflow-hidden bg-neutral', $lp_spacing ); ?>" data-component="hero"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<?php
	if ( $lp_has_media ) {
		$lp_photo = array(
			'image_id' => $lp_media_id,
			'scrim'    => 'hero',
			'size'     => 'lp_wide_lg',
			'sizes'    => '100vw',
		);
		if ( array_key_exists( 'media_alt', $args ) ) {
			$lp_photo['alt'] = (string) $args['media_alt'];
		}
		lp_part( 'components/media-photo', $lp_photo );
	} else {
		?>
		<div class="absolute inset-0 bg-neutral" aria-hidden="true"></div>
		<?php
	}
	?>
	<div class="relative pt-[132px] px-16 pb-12 flex flex-col">
		<div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-12 xl:gap-x-[72px]">
			<div class="flex flex-col gap-8 xl:max-w-[664px]" data-slot="claim">
				<p class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-primary"><?php echo esc_html( $lp_eyebrow ); ?></p>
				<h1 class="font-display text-step-7 font-bold tracking-[-4px] leading-[0.92] text-neutral-content"><?php echo esc_html( $lp_headline ); ?></h1>
				<p class="font-body text-step--1 text-neutral-content/80 max-w-[470px]"><?php echo esc_html( $lp_lead ); ?></p>
				<div class="flex items-center gap-[28px] flex-wrap">
					<?php
					if ( $lp_primary ) {
						lp_part(
							'elements/button',
							array(
								'variant'          => 'primary',
								'label'            => $lp_primary['label'],
								'href'             => $lp_primary['href'],
								'trailing_icon_id' => 'icon-arrow-right',
							)
						);
					}
					?>
					<?php if ( $lp_secondary ) : ?>
						<a href="<?php echo esc_url( $lp_secondary['href'] ); ?>" class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-neutral-content/80 hover:text-primary transition-colors duration-150"><?php echo esc_html( $lp_secondary['label'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="w-full xl:w-[576px] xl:shrink-0 xl:mt-[231px] bg-neutral/95" data-slot="board">
				<div class="flex items-center justify-between gap-3 px-5 py-[15px] border-b border-neutral-content/10">
					<span class="font-label text-step--2 font-semibold tracking-[1px] uppercase text-primary"><?php echo esc_html( $lp_board_ttl ); ?></span>
					<span class="font-label text-step--2 font-normal tracking-[0.6px] text-neutral-content/50"><?php echo esc_html( $lp_board_stmp ); ?></span>
				</div>
				<div data-slot="rows">
					<?php foreach ( $lp_sessions as $lp_session ) : ?>
						<div class="px-5"><?php lp_part( 'components/departure-row', $lp_session ); ?></div>
					<?php endforeach; ?>
				</div>
				<div class="flex items-center justify-between gap-3 px-5 py-[15px] border-t border-neutral-content/10">
					<a href="<?php echo esc_url( $lp_foot_href ); ?>" class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-neutral-content/80 hover:text-primary transition-colors duration-150"><?php echo esc_html( $lp_foot_label ); ?></a>
					<span class="font-label text-step--2 font-normal tracking-[0.6px] text-neutral-content/50"><?php echo esc_html( $lp_foot_count ); ?></span>
				</div>
			</div>
		</div>
		<div class="flex items-center justify-between gap-4 flex-wrap mt-[94px] pt-[22px] border-t border-neutral-content/20">
			<span class="font-label text-step--2 font-normal tracking-[1px] uppercase text-neutral-content/80"><?php echo esc_html( $lp_scroll ); ?></span>
			<div class="flex items-center gap-[22px] flex-wrap">
				<?php foreach ( $lp_trust as $lp_i => $lp_item ) : ?>
					<?php if ( $lp_i > 0 ) : ?>
						<span class="w-px h-[11px] bg-neutral-content/20" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="font-label text-step--2 font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_item ); ?></span>
				<?php endforeach; ?>
				<span class="w-px h-[11px] bg-neutral-content/20" aria-hidden="true"></span>
				<span class="font-label text-step--2 font-normal tracking-[0.8px] uppercase text-primary"><?php echo esc_html( $lp_rating ); ?></span>
			</div>
		</div>
	</div>
</div>
