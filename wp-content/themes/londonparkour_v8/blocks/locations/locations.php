<?php
/**
 * Locations — "07 — WHERE WE TRAIN": the flagship card, the site list and the
 * client strip, all on the accent band.
 *
 * Ported from src/stories/Blocks/Locations/Locations.js.
 *
 * Takes the CPT source control for the site list; the flagship is its own
 * group, because it is a different projection of the same entity (a photo card,
 * not a text row) — the same split coaches makes for its lead coach.
 *
 * Ground is `bg-accent`, so every foreground here is the `accent-content`
 * family. `text-primary` can never appear as a foreground on this band: in both
 * dark themes `bg-accent` IS `primary`, so primary text vanishes into its own
 * ground. That is why the flagship tag, the row focus ring and the chevron
 * hover are all `accent-content` — see the source's docblock for the full
 * mapping, which this port carries over unchanged.
 *
 * The site row is deliberately NOT components/list-row.php. ListRow is a
 * recessed board row (`bg-secondary`, hover `bg-neutral`) sitting on top of its
 * ground; these rows carry no fill of their own, sit directly on `bg-accent`,
 * and need a leading glyph and a trailing category label that ListRow has no
 * slot for. The source makes the same call and explains it at length. Only the
 * chevron is shared — elements/chevron.php variant `accent_band` exists for
 * exactly this row.
 *
 * @param string $args['eyebrow']
 * @param string $args['heading']
 * @param string $args['note']
 * @param array  $args['flagship']      tag/name/meta/image/image_alt/link.
 * @param string $args['clients_label']
 * @param array  $args['clients']       Rows of array( 'label' => … ).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_flagship = array(
	'tag'  => 'FLAGSHIP INDOOR SITE',
	'name' => 'Vauxhall — The Arches',
	'meta' => 'SW8 1SR · 4 MIN FROM VAUXHALL · OPEN 07:00–22:00',
);

$lp_default_sites = array(
	array(
		'title' => 'Peckham Rye',
		'meta'  => 'SE15 3UA · Rye Lane · 6 min',
		'type'  => 'INDOOR',
	),
	array(
		'title' => 'Southbank Undercroft',
		'meta'  => 'SE1 8XX · Waterloo · 8 min',
		'type'  => 'OUTDOOR',
	),
	array(
		'title' => 'Stratford East',
		'meta'  => 'E20 1EJ · Stratford · 5 min',
		'type'  => 'INDOOR',
	),
	array(
		'title' => 'Hackney Marshes',
		'meta'  => 'E9 5PF · Homerton · 10 min',
		'type'  => 'OUTDOOR',
	),
	array(
		'title' => 'Wembley Park',
		'meta'  => 'HA9 0FJ · Wembley Park · 7 min',
		'type'  => 'OUTDOOR',
	),
);

$lp_default_clients = array( 'NIKE', 'BBC', 'RED BULL', 'UCL', 'ADIDAS', 'SKY', 'PUMA', 'O2' );

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_row_base        = 'group relative flex items-center gap-[16px] w-full py-[20px] px-[16px] sm:px-[24px] hover:bg-accent-content/5 border-b border-accent-content/15 transition-colors duration-150 no-underline text-left';
$lp_row_interactive = 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-accent-content';

$lp_eyebrow       = (string) ( $args['eyebrow'] ?? '07 — WHERE WE TRAIN' );
$lp_heading       = (string) ( $args['heading'] ?? 'Six spots across London.' );
$lp_note          = (string) ( $args['note'] ?? 'Every site is a ten-minute walk from a tube or overground station. We checked.' );
$lp_clients_label = (string) ( $args['clients_label'] ?? "WE'VE WORKED WITH" );

$lp_flagship = is_array( $args['flagship'] ?? null ) ? $args['flagship'] : array();

if ( '' === (string) ( $lp_flagship['name'] ?? '' ) ) {
	$lp_flagship = $lp_default_flagship;
}

$lp_flag_tag      = (string) ( $lp_flagship['tag'] ?? '' );
$lp_flag_name     = (string) ( $lp_flagship['name'] ?? '' );
$lp_flag_meta     = (string) ( $lp_flagship['meta'] ?? '' );
$lp_flag_image    = ! empty( $lp_flagship['image'] ) ? (int) $lp_flagship['image'] : 0;
$lp_flag_link     = lp_action( $lp_flagship['link'] ?? null );
$lp_flag_is_link  = (bool) $lp_flag_link;

// One query layer; the projection is this block's own. A location record gives
// title/meta/type; a manual row supplies the same three names.
$lp_sites = array_map(
	static function ( array $item ): array {
		return array(
			'title' => (string) ( $item['title'] ?? '' ),
			'meta'  => (string) ( $item['meta'] ?? '' ),
			'type'  => (string) ( $item['type'] ?? '' ),
			'href'  => (string) ( $item['url'] ?? '' ),
		);
	},
	lp_resolve_source( $args, 'lp_location', array( 'exclude_flag' => 'is_flagship' ) )
);

if ( ! $lp_sites ) {
	$lp_sites = $lp_default_sites;
}

$lp_clients = array();

foreach ( is_array( $args['clients'] ?? null ) ? $args['clients'] : array() as $lp_row ) {
	$lp_text = is_array( $lp_row ) ? (string) ( $lp_row['label'] ?? '' ) : (string) $lp_row;
	if ( '' !== $lp_text ) {
		$lp_clients[] = $lp_text;
	}
}

if ( ! $lp_clients ) {
	$lp_clients = $lp_default_clients;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'bg-accent px-6 md:px-16 pt-[72px] pb-16', $lp_spacing ); ?>" data-component="locations"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col gap-[40px]">
		<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-[24px]">
			<div class="flex flex-col gap-[16px]">
				<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-accent-content/70"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<h2 class="font-heading text-step-3 font-semibold tracking-[-1px] text-accent-content max-w-[700px]"><?php echo esc_html( $lp_heading ); ?></h2>
			</div>
			<?php if ( '' !== $lp_note ) : ?>
				<p class="font-body text-step--2 text-accent-content/70 max-w-[280px]"><?php echo esc_html( $lp_note ); ?></p>
			<?php endif; ?>
		</div>

		<div class="grid lg:grid-cols-2 gap-[24px] items-stretch">
			<?php if ( $lp_flag_is_link ) : ?>
			<a class="group relative flex flex-col justify-end overflow-hidden min-h-[300px] lg:min-h-[472px] no-underline" href="<?php echo esc_url( $lp_flag_link['href'] ); ?>" data-component="location-flagship">
			<?php else : ?>
			<div class="group relative flex flex-col justify-end overflow-hidden min-h-[300px] lg:min-h-[472px] no-underline" data-component="location-flagship">
			<?php endif; ?>
				<?php
				if ( $lp_flag_image ) {
					$lp_photo = array(
						'image_id' => $lp_flag_image,
						'scrim'    => 'locations_flagship',
						'size'     => 'lp_wide',
						'sizes'    => '(min-width: 1024px) 50vw, 100vw',
					);
					if ( array_key_exists( 'image_alt', $lp_flagship ) ) {
						$lp_photo['alt'] = (string) $lp_flagship['image_alt'];
					}
					lp_part( 'components/media-photo', $lp_photo );
				} else {
					// The source lays the scrim over the band even with no photo.
					?>
					<div class="absolute inset-0 bg-neutral/35" aria-hidden="true"></div>
					<?php
				}
				?>
				<div class="relative flex flex-col gap-[8px] p-[28px]">
					<?php if ( '' !== $lp_flag_tag ) : ?>
						<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-accent-content"><?php echo esc_html( $lp_flag_tag ); ?></span>
					<?php endif; ?>
					<p class="font-heading text-step-1 font-semibold tracking-[-0.3px] text-accent-content"><?php echo esc_html( $lp_flag_name ); ?></p>
					<?php if ( '' !== $lp_flag_meta ) : ?>
						<p class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-accent-content/85"><?php echo esc_html( $lp_flag_meta ); ?></p>
					<?php endif; ?>
				</div>
			<?php echo $lp_flag_is_link ? '</a>' : '</div>'; ?>

			<ul role="list" class="flex flex-col w-full m-0 p-0 list-none border-t border-accent-content/15">
				<?php
				foreach ( $lp_sites as $lp_site ) :
					$lp_site_link = '' !== $lp_site['href'];
					?>
					<li>
						<?php if ( $lp_site_link ) : ?>
						<a class="<?php echo lp_classes( $lp_row_base, $lp_row_interactive ); ?>" data-component="location-site-row" href="<?php echo esc_url( $lp_site['href'] ); ?>">
						<?php else : ?>
						<div class="<?php echo esc_attr( $lp_row_base ); ?>" data-component="location-site-row">
						<?php endif; ?>
							<span class="text-accent-content/70 shrink-0" aria-hidden="true"><?php lp_icon( 'icon-map-pin', 'w-3.5 h-3.5' ); ?></span>
							<div class="flex-1 min-w-0 flex flex-col gap-[6px]">
								<p class="font-heading text-[15px] font-medium tracking-[-0.2px] text-accent-content truncate"><?php echo esc_html( $lp_site['title'] ); ?></p>
								<p class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-accent-content/70 truncate"><?php echo esc_html( $lp_site['meta'] ); ?></p>
							</div>
							<?php if ( '' !== $lp_site['type'] ) : ?>
								<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-accent-content/70 shrink-0 hidden sm:inline"><?php echo esc_html( $lp_site['type'] ); ?></span>
							<?php endif; ?>
							<?php lp_part( 'elements/chevron', array( 'variant' => 'accent_band' ) ); ?>
						<?php echo $lp_site_link ? '</a>' : '</div>'; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="flex flex-wrap items-center gap-x-[40px] gap-y-[16px] pt-[16px] border-t border-accent-content/15">
			<span class="font-label text-[10px] font-normal tracking-[0.5px] uppercase text-accent-content/70"><?php echo esc_html( $lp_clients_label ); ?></span>
			<?php foreach ( $lp_clients as $lp_client ) : ?>
				<span class="font-heading text-[15px] font-normal tracking-[0.2px] text-accent-content/85"><?php echo esc_html( $lp_client ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
</section>
