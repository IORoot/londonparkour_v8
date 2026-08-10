<?php
/**
 * SiteNav — the fixed dark bar: primary nav, Classes drop panel, mobile bar
 * and drawer.
 *
 * Ported from src/stories/Site/SiteNav/SiteNav.js.
 *
 * The whole component sits on the FIXED dark band (`bg-neutral`), so every
 * content colour is `neutral-content` / `text-primary` — never `base-content`,
 * which collapses onto the same hex as `neutral` in both light themes.
 *
 * THREE DELIBERATE DEPARTURES FROM docs/CONSOLIDATION.md, each measured:
 *
 * 1. The desktop CTA does NOT use `elements/button.php` variant `band`, and
 *    `band` is not a near miss to be nudged: it is `flex justify-between w-full
 *    h-[60px] px-[22px]` with `hover:bg-neutral hover:text-primary`, while this
 *    CTA is `inline-flex` at the BAR's height (76px / 58px by variant),
 *    `px-[30px]`, hovering `bg-primary/85`. Only the label span and arrow match
 *    — which is why `band` looks like the answer. It is AsidePanel's shape, not
 *    this one. The source says the same thing at length: the Book Block is a
 *    full-bar-height flush block ending exactly on the viewport edge, which a
 *    fixed-height padded control cannot be. See PORT-FINDINGS §10.
 *
 * 2. The two MOBILE CTAs DO go through `elements/button.php` (`primary`), as
 *    the source mounts them. HANDOFF's "both SiteNav CTAs bypass button.php"
 *    was true of an older source; only the desktop one is inline now.
 *
 * 3. The leading nav glyph is rendered by THIS file, outside the mounted
 *    `elements/nav-link.php`, exactly as the source does. nav-link's own
 *    `icon_id` slot puts a 14px glyph INSIDE the anchor on `hover:`; this design
 *    needs a 13px glyph OUTSIDE it, driven by the wrapper's `group-hover:`. Two
 *    different shapes; passing `icon_id` here would be wrong.
 *
 * The Classes item is a real `<button>`, not a nav-link: the Popover API's
 * `popovertarget` invoker only works on a button, and a link that silently
 * opens a non-navigating panel is an a11y anti-pattern. Its state classes
 * mirror nav-link's on purpose — nav-link's contract stays anchor-only.
 *
 * `el-popover` / `el-dialog` / `command` / `commandfor` are Tailwind Plus
 * Elements, already imported in assets/js/app.js. They carry over verbatim.
 *
 * @param string $args['brand']
 * @param string $args['home_href']
 * @param string $args['variant']          default|condensed.
 * @param bool   $args['over_hero']        Transparent bar over homepage Hero.
 * @param string $args['find_site_label']
 * @param string $args['find_site_href']
 * @param array  $args['links']            Rows of label/href/active/has_panel.
 * @param string $args['cta_label']
 * @param string $args['cta_href']
 * @param array  $args['classes_panel']    The drop panel's content.
 * @param string $args['mobile_menu_id']
 * @param string $args['panel_id']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_focus            = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$lp_focus_inset      = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';
$lp_focus_on_primary = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-content';

$lp_bar_heights = array(
	'default'   => 'h-[76px]',
	'condensed' => 'h-[58px]',
);

$lp_logo_widths = array(
	'default'   => 124,
	'condensed' => 96,
);

// The panel is a [popover] in the top layer, so its containing block for
// `absolute` is the viewport — a fixed per-variant offset pins it under the bar.
$lp_panel_positions = array(
	'default'   => 'absolute top-[76px]',
	'condensed' => 'absolute top-[58px]',
);

$lp_glyph_base   = 'w-[13px] h-[13px] flex-none';
$lp_glyph_states = array(
	'active'   => 'text-primary',
	'inactive' => 'text-neutral-content group-hover:text-primary',
);

$lp_default_links = array(
	array(
		'label'     => 'Classes',
		'href'      => '/classes',
		'active'    => true,
		'has_panel' => true,
	),
	array(
		'label' => 'Tutorials',
		'href'  => '/tutorials',
	),
	array(
		'label' => 'Docs',
		'href'  => '/docs',
	),
	array(
		'label' => 'Contact',
		'href'  => '/contact',
	),
);

$lp_default_panel = array(
	'levels_title'     => 'By Level',
	'levels_note'      => '5',
	'levels'           => array(
		array(
			'name' => 'Beginners',
			'meta' => 'Level 1',
		),
		array(
			'name' => 'Improvers',
			'meta' => 'Level 2',
		),
		array(
			'name' => 'Advanced',
			'meta' => 'Level 3',
		),
		array(
			'name' => 'Open Gym',
			'meta' => 'All',
		),
		array(
			'name' => "Women's Session",
			'meta' => 'All',
		),
	),
	'sites_title'      => 'By Site',
	'sites_note'       => '6 sites',
	'sites'            => array(
		array(
			'name' => 'Vauxhall',
			'meta' => 'SW8',
		),
		array(
			'name' => 'Peckham Rye',
			'meta' => 'SE15',
		),
		array(
			'name' => 'Hackney Marshes',
			'meta' => 'E9',
		),
		array(
			'name' => 'Stratford East',
			'meta' => 'E15',
		),
		array(
			'name' => 'Southbank',
			'meta' => 'SE1',
		),
		array(
			'name' => 'Wembley Park',
			'meta' => 'HA9',
		),
	),
	'departures_title' => 'Next Departures',
	'departures_note'  => 'Live',
	'departures'       => array(
		array(
			'time'   => '18:30',
			'name'   => 'Beginners Parkour',
			'site'   => 'Vauxhall',
			'spaces' => '4 left',
		),
		array(
			'time'   => '19:45',
			'name'   => 'Open Gym',
			'site'   => 'Stratford East',
			'spaces' => '11 left',
		),
		array(
			'time'   => '07:00',
			'name'   => 'Sunrise Session',
			'site'   => 'Peckham Rye',
			'spaces' => 'Full',
		),
		array(
			'time'   => '10:00',
			'name'   => 'Kids 5–11',
			'site'   => 'Hackney Marshes',
			'spaces' => '2 left',
		),
	),
	'promo'            => array(
		'title' => 'New here?',
		'body'  => 'Beginners sessions run every day. £15, all kit provided, no experience needed.',
		'cta'   => 'Book first class',
		'href'  => '/classes/beginners',
		'aside' => "Not sure which class? Call 020 7946 0112 — we'll put you in the right session.",
	),
	'all_label'        => 'All 24 classes across six sites →',
	'all_href'         => '/classes',
	'download_label'   => 'Download the timetable ↗',
	'download_href'    => '/timetable.pdf',
);

$lp_brand      = (string) ( $args['brand'] ?? 'London Parkour' );
$lp_home_href  = (string) ( $args['home_href'] ?? '/' );
$lp_variant    = isset( $lp_bar_heights[ $args['variant'] ?? '' ] ) ? (string) $args['variant'] : 'default';
$lp_over_hero  = ! empty( $args['over_hero'] );
$lp_site_label = (string) ( $args['find_site_label'] ?? 'Find a site' );
$lp_site_href  = (string) ( $args['find_site_href'] ?? '/classes/map' );
$lp_cta_label  = (string) ( $args['cta_label'] ?? 'Find a class' );
$lp_cta_href   = (string) ( $args['cta_href'] ?? '/classes' );
$lp_menu_id    = (string) ( $args['mobile_menu_id'] ?? 'site-nav-mobile-menu' );
$lp_panel_id   = (string) ( $args['panel_id'] ?? 'site-nav-classes-panel' );

$lp_links = array();

foreach ( is_array( $args['links'] ?? null ) ? $args['links'] : array() as $lp_row ) {
	if ( ! empty( $lp_row['label'] ) ) {
		$lp_links[] = $lp_row;
	}
}

if ( ! $lp_links ) {
	$lp_links = $lp_default_links;
}

$lp_panel = is_array( $args['classes_panel'] ?? null ) && $args['classes_panel']
	? array_merge( $lp_default_panel, $args['classes_panel'] )
	: $lp_default_panel;

$lp_promo = is_array( $lp_panel['promo'] ?? null ) ? $lp_panel['promo'] : array();

$lp_is_condensed = 'condensed' === $lp_variant;
$lp_bar_height   = $lp_bar_heights[ $lp_variant ];
$lp_logo_width   = $lp_logo_widths[ $lp_variant ];
$lp_panel_pos    = $lp_panel_positions[ $lp_variant ];
// Whole literals — Tailwind v4 scans source text.
// Homepage: absolute + transparent so the hero photo starts at y=0 under the
// bar. Other pages keep the opaque in-flow band.
$lp_header_ground = $lp_over_hero
	? 'bg-transparent absolute inset-x-0 top-0 z-20'
	: 'bg-neutral relative';

// The first link flagged has_panel is the popover trigger; every other is a
// plain nav-link.
$lp_panel_index = -1;
foreach ( $lp_links as $lp_i => $lp_link ) {
	if ( ! empty( $lp_link['has_panel'] ) ) {
		$lp_panel_index = $lp_i;
		break;
	}
}
?>
<header data-component="site-nav" data-variant="<?php echo esc_attr( $lp_variant ); ?>" data-over-hero="<?php echo $lp_over_hero ? 'true' : 'false'; ?>" class="<?php echo esc_attr( $lp_header_ground ); ?>">
	<nav aria-label="Primary">
		<div class="<?php echo lp_classes( 'hidden lg:flex items-stretch justify-between border-b border-neutral-content/10 pl-[64px]', $lp_bar_height ); ?>">
			<a href="<?php echo esc_url( $lp_home_href ); ?>" aria-label="<?php echo esc_attr( $lp_brand ); ?>" class="flex items-center text-neutral-content">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => $lp_logo_width,
						'color_class' => 'text-neutral-content',
						'label'       => $lp_brand,
					)
				);
				?>
			</a>
			<div class="flex items-stretch gap-[32px]">
				<el-popover-group class="flex items-stretch">
					<?php
					foreach ( $lp_links as $lp_i => $lp_link ) :
						$lp_active     = ! empty( $lp_link['active'] );
						$lp_glyph_cls  = $lp_active ? $lp_glyph_states['active'] : $lp_glyph_states['inactive'];
						$lp_border_cls = $lp_active ? 'border-primary' : 'border-transparent';
						?>
						<?php if ( $lp_i > 0 ) : ?>
							<span class="<?php echo lp_classes( 'w-px', $lp_bar_height, 'bg-neutral-content/10' ); ?>" aria-hidden="true"></span>
						<?php endif; ?>
						<?php if ( $lp_i === $lp_panel_index ) : ?>
							<button type="button" popovertarget="<?php echo esc_attr( $lp_panel_id ); ?>" aria-controls="<?php echo esc_attr( $lp_panel_id ); ?>" aria-haspopup="true"<?php echo $lp_active ? ' aria-current="page"' : ''; ?>
								class="<?php echo lp_classes( 'group', $lp_bar_height, 'inline-flex items-center justify-center gap-[9px] px-[20px] border-b-[3px]', $lp_border_cls, $lp_focus_inset ); ?>">
								<?php lp_icon( 'icon-sparkles', lp_classes( $lp_glyph_base, $lp_glyph_cls ) ); ?>
								<span class="<?php echo lp_classes( 'font-label uppercase text-[12px] font-semibold tracking-[1.1px] transition-colors duration-150', $lp_active ? 'text-primary' : 'text-neutral-content group-hover:text-primary' ); ?>"><?php echo esc_html( (string) $lp_link['label'] ); ?></span>
							</button>
						<?php else : ?>
							<span class="<?php echo lp_classes( 'group', $lp_bar_height, 'inline-flex items-center justify-center gap-[9px] px-[20px] border-b-[3px]', $lp_border_cls ); ?>">
								<?php
								lp_icon( 'icon-sparkles', lp_classes( $lp_glyph_base, $lp_glyph_cls ) );
								lp_part(
									'elements/nav-link',
									array(
										'label'  => (string) $lp_link['label'],
										'href'   => (string) ( $lp_link['href'] ?? '#' ),
										'active' => $lp_active,
									)
								);
								?>
							</span>
						<?php endif; ?>
					<?php endforeach; ?>
				</el-popover-group>
				<?php if ( $lp_is_condensed ) : ?>
					<span class="w-px h-[22px] bg-neutral-content/15" aria-hidden="true"></span>
					<a href="<?php echo esc_url( $lp_site_href ); ?>" class="<?php echo lp_classes( 'inline-flex items-center gap-[8px] font-label text-[12px] font-normal uppercase tracking-[1px] text-neutral-content/70 hover:text-primary transition-colors duration-150', $lp_focus ); ?>">
						<?php lp_icon( 'icon-map-pin', 'w-[13px] h-[13px]' ); ?>
						<?php echo esc_html( $lp_site_label ); ?>
					</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( $lp_cta_href ); ?>"
					class="<?php echo lp_classes( $lp_bar_height, 'inline-flex items-center gap-[12px] px-[30px] bg-primary text-primary-content font-label text-[12px] font-semibold uppercase tracking-[1px] hover:bg-primary/85 transition-colors duration-150', $lp_focus_inset ); ?>">
					<?php echo esc_html( $lp_cta_label ); ?>
					<?php lp_icon( 'icon-arrow-right', 'w-[14px] h-[14px]' ); ?>
				</a>
			</div>
		</div>

		<div class="flex lg:hidden items-stretch justify-between h-[60px] border-b border-neutral-content/10 pl-[20px]">
			<a href="<?php echo esc_url( $lp_home_href ); ?>" aria-label="<?php echo esc_attr( $lp_brand ); ?>" class="flex items-center text-neutral-content">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => 88,
						'color_class' => 'text-neutral-content',
						'label'       => $lp_brand,
					)
				);
				?>
			</a>
			<div class="flex items-stretch">
				<span class="flex items-center">
					<?php
					lp_part(
						'elements/button',
						array(
							'variant'          => 'primary',
							'label'            => $lp_cta_label,
							'href'             => $lp_cta_href,
							'trailing_icon_id' => 'icon-arrow-right',
						)
					);
					?>
				</span>
				<button type="button" command="show-modal" commandfor="<?php echo esc_attr( $lp_menu_id ); ?>" aria-haspopup="dialog" aria-label="<?php esc_attr_e( 'Open menu', 'londonparkour_v8' ); ?>"
					class="<?php echo lp_classes( 'inline-flex items-center justify-center w-[60px] border-l border-neutral-content/15 text-neutral-content hover:bg-primary hover:text-neutral transition-colors duration-150', $lp_focus_inset ); ?>">
					<?php lp_icon( 'icon-bars-3', 'w-[20px] h-[20px]' ); ?>
				</button>
			</div>
		</div>

		<el-popover id="<?php echo esc_attr( $lp_panel_id ); ?>" popover
			class="<?php echo lp_classes( $lp_panel_pos, 'inset-x-0 w-full m-0 bg-neutral/95 border-b border-neutral-content/15 backdrop:bg-transparent open:block transition [transition-behavior:allow-discrete] data-[closed]:opacity-0 data-[closed]:-translate-y-1 data-[enter]:duration-200 data-[leave]:duration-150 data-[enter]:ease-out data-[leave]:ease-in' ); ?>">
			<div class="flex flex-col lg:flex-row flex-wrap gap-[32px] lg:gap-[44px] px-[20px] py-[32px] lg:px-[64px] lg:pt-[38px] lg:pb-[34px]">
				<?php
				$lp_columns = array(
					array(
						'title' => (string) $lp_panel['levels_title'],
						'note'  => (string) $lp_panel['levels_note'],
						'rows'  => is_array( $lp_panel['levels'] ) ? $lp_panel['levels'] : array(),
					),
					array(
						'title' => (string) $lp_panel['sites_title'],
						'note'  => (string) $lp_panel['sites_note'],
						'rows'  => is_array( $lp_panel['sites'] ) ? $lp_panel['sites'] : array(),
					),
				);
				?>
				<?php foreach ( $lp_columns as $lp_column ) : ?>
					<div class="lg:max-w-[250px] flex-1 min-w-[220px]">
						<div class="flex items-center justify-between pb-[12px] border-b border-neutral-content/10">
							<span class="font-label text-[10px] font-semibold uppercase tracking-[1.2px] text-primary"><?php echo esc_html( $lp_column['title'] ); ?></span>
							<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( $lp_column['note'] ); ?></span>
						</div>
						<div class="divide-y divide-neutral-content/10">
							<?php foreach ( $lp_column['rows'] as $lp_row ) : ?>
								<div class="flex items-center justify-between py-[13px]">
									<span class="font-body text-[15px] font-medium tracking-[-0.1px] text-neutral-content"><?php echo esc_html( (string) ( $lp_row['name'] ?? '' ) ); ?></span>
									<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( (string) ( $lp_row['meta'] ?? '' ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="flex-[2] min-w-[280px]">
					<div class="flex items-center justify-between pb-[12px] border-b border-neutral-content/10">
						<span class="font-label text-[10px] font-semibold uppercase tracking-[1.2px] text-primary"><?php echo esc_html( (string) $lp_panel['departures_title'] ); ?></span>
						<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( (string) $lp_panel['departures_note'] ); ?></span>
					</div>
					<div class="divide-y divide-neutral-content/10">
						<?php
						foreach ( is_array( $lp_panel['departures'] ) ? $lp_panel['departures'] : array() as $lp_dep ) :
							$lp_spaces    = (string) ( $lp_dep['spaces'] ?? '' );
							$lp_is_full   = 1 === preg_match( '/^full$/i', $lp_spaces );
							$lp_space_cls = $lp_is_full ? 'text-neutral-content/50' : 'text-primary';
							?>
							<div class="flex items-center gap-[16px] py-[12px]">
								<span class="font-body text-[16px] font-semibold tracking-[-0.3px] text-neutral-content w-[41px] flex-none"><?php echo esc_html( (string) ( $lp_dep['time'] ?? '' ) ); ?></span>
								<span class="flex flex-col gap-[3px] flex-1 min-w-0">
									<span class="font-body text-[14px] font-medium text-neutral-content truncate"><?php echo esc_html( (string) ( $lp_dep['name'] ?? '' ) ); ?></span>
									<span class="font-label text-[10px] font-normal uppercase tracking-[0.5px] text-neutral-content/50"><?php echo esc_html( (string) ( $lp_dep['site'] ?? '' ) ); ?></span>
								</span>
								<span class="<?php echo lp_classes( 'font-label text-[10px] font-semibold uppercase tracking-[0.8px] flex-none', $lp_space_cls ); ?>"><?php echo esc_html( $lp_spaces ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="w-full lg:w-[280px] flex-none flex flex-col gap-[16px]">
					<div class="bg-primary p-[22px] flex flex-col gap-[12px]">
						<p class="font-body text-[22px] font-semibold tracking-[-0.4px] text-primary-content"><?php echo esc_html( (string) ( $lp_promo['title'] ?? '' ) ); ?></p>
						<p class="font-body text-[11px] font-normal text-primary-content/80"><?php echo esc_html( (string) ( $lp_promo['body'] ?? '' ) ); ?></p>
						<a href="<?php echo esc_url( (string) ( $lp_promo['href'] ?? '' ) ); ?>" class="<?php echo lp_classes( 'inline-flex items-center gap-[9px] pt-[8px] font-label text-[11px] font-semibold uppercase tracking-[1px] text-primary-content', $lp_focus_on_primary ); ?>">
							<?php echo esc_html( (string) ( $lp_promo['cta'] ?? '' ) ); ?>
							<?php lp_icon( 'icon-arrow-right', 'w-[13px] h-[13px] text-primary-content' ); ?>
						</a>
					</div>
					<p class="font-body text-[11px] font-normal text-neutral-content/50"><?php echo esc_html( (string) ( $lp_promo['aside'] ?? '' ) ); ?></p>
				</div>
			</div>
			<div class="flex flex-col gap-[8px] sm:flex-row items-start sm:items-center justify-between gap-x-4 border-t border-neutral-content/10 px-[20px] py-[15px] lg:px-[64px]">
				<a href="<?php echo esc_url( (string) $lp_panel['all_href'] ); ?>" class="<?php echo lp_classes( 'font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-primary hover:text-neutral-content transition-colors duration-150', $lp_focus ); ?>"><?php echo esc_html( (string) $lp_panel['all_label'] ); ?></a>
				<a href="<?php echo esc_url( (string) $lp_panel['download_href'] ); ?>" class="<?php echo lp_classes( 'font-label text-[11px] font-normal uppercase tracking-[0.9px] text-neutral-content/50 hover:text-neutral-content transition-colors duration-150', $lp_focus ); ?>"><?php echo esc_html( (string) $lp_panel['download_label'] ); ?></a>
			</div>
		</el-popover>
	</nav>
</header>

<el-dialog>
	<dialog id="<?php echo esc_attr( $lp_menu_id ); ?>" aria-label="<?php esc_attr_e( 'Menu', 'londonparkour_v8' ); ?>" class="m-0 p-0 backdrop:bg-neutral/60 lg:hidden">
		<div tabindex="0" class="fixed inset-0 focus:outline-0">
			<el-dialog-panel class="fixed inset-y-0 right-0 z-50 flex h-full w-full max-w-sm flex-col overflow-y-auto bg-neutral p-[24px]">
				<div class="flex items-center justify-between">
					<span class="flex items-center text-neutral-content">
						<?php
						lp_part(
							'brand/logo',
							array(
								'width'       => 96,
								'color_class' => 'text-neutral-content',
								'label'       => $lp_brand,
							)
						);
						?>
					</span>
					<button type="button" command="close" commandfor="<?php echo esc_attr( $lp_menu_id ); ?>" aria-label="<?php esc_attr_e( 'Close menu', 'londonparkour_v8' ); ?>"
						class="<?php echo lp_classes( 'inline-flex items-center justify-center w-[40px] h-[40px] text-neutral-content hover:bg-primary hover:text-neutral transition-colors duration-150', $lp_focus ); ?>">
						<?php lp_icon( 'icon-x-mark', 'w-[20px] h-[20px]' ); ?>
					</button>
				</div>
				<nav aria-label="<?php esc_attr_e( 'Primary', 'londonparkour_v8' ); ?>" class="mt-[32px] flex flex-col divide-y divide-neutral-content/10">
					<?php
					foreach ( $lp_links as $lp_link ) :
						$lp_active    = ! empty( $lp_link['active'] );
						$lp_glyph_cls = $lp_active ? $lp_glyph_states['active'] : $lp_glyph_states['inactive'];
						?>
						<span class="group flex items-center gap-[12px] py-[16px]">
							<?php lp_icon( 'icon-sparkles', lp_classes( $lp_glyph_base, $lp_glyph_cls ) ); ?>
							<span class="flex-1">
								<?php
								lp_part(
									'elements/nav-link',
									array(
										'label'  => (string) $lp_link['label'],
										'href'   => (string) ( $lp_link['href'] ?? '#' ),
										'active' => $lp_active,
									)
								);
								?>
							</span>
						</span>
					<?php endforeach; ?>
				</nav>
				<a href="<?php echo esc_url( $lp_site_href ); ?>" class="<?php echo lp_classes( 'mt-[16px] inline-flex items-center gap-[8px] font-label text-[12px] font-normal uppercase tracking-[1px] text-neutral-content/70 hover:text-primary transition-colors duration-150', $lp_focus ); ?>">
					<?php lp_icon( 'icon-map-pin', 'w-[13px] h-[13px]' ); ?>
					<?php echo esc_html( $lp_site_label ); ?>
				</a>
				<span class="mt-[24px]">
					<?php
					lp_part(
						'elements/button',
						array(
							'variant'          => 'primary',
							'label'            => $lp_cta_label,
							'href'             => $lp_cta_href,
							'trailing_icon_id' => 'icon-arrow-right',
						)
					);
					?>
				</span>
			</el-dialog-panel>
		</div>
	</dialog>
</el-dialog>
