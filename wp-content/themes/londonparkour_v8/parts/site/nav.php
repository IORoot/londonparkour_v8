<?php
/**
 * SiteNav — the fixed dark bar: primary nav, Classes / Tutorials / Docs drop
 * panels, mobile bar and drawer.
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
 * Classes, Tutorials and Docs are real `<a href>` items (never `role="button"`).
 * Hover / focus-within on the wrapping `group/{panel}` reveals the matching
 * drop panel. Clicking the label goes to that section. Contact has no panel.
 * The Classes item and the CTA both go to the class agenda (`/classes/`).
 * Tutorials goes to the series listing (`/tutorials/series/`).
 * The Classes drop panel's first column is Agenda, Map and Private 1:1.
 * Column two lists every class type under ALL CLASSES.
 * ALL CLASSES in the panel foot goes to the listings archive (`/all-classes/`).
 * See PORT-FINDINGS §21.
 *
 * `el-dialog` / `command` / `commandfor` are Tailwind Plus Elements, already
 * imported in assets/js/app.js. They carry over verbatim.
 *
 * @param string $args['brand']
 * @param string $args['home_href']
 * @param string $args['variant']           default|condensed.
 * @param bool   $args['over_hero']         Transparent bar over homepage Hero.
 * @param string $args['find_site_label']
 * @param string $args['find_site_href']
 * @param array  $args['links']             Rows of label/href/active/panel/icon_id.
 * @param string $args['cta_label']
 * @param string $args['cta_href']
 * @param array  $args['classes_panel']
 * @param array  $args['tutorials_panel']
 * @param array  $args['docs_panel']
 * @param string $args['mobile_menu_id']
 * @param string $args['open_panel']        classes|tutorials|docs — force a panel open.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_focus       = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$lp_focus_inset = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';

$lp_bar_heights = array(
	'default'   => 'h-[76px]',
	'condensed' => 'h-[58px]',
);

$lp_logo_widths = array(
	'default'   => 124,
	'condensed' => 96,
);

$lp_panel_positions = array(
	'default'   => 'absolute top-[76px]',
	'condensed' => 'absolute top-[58px]',
);

$lp_glyph_base    = 'w-4 h-4 flex-none';
$lp_row_glyph_cls = 'w-4 h-4 flex-none text-neutral-content group-hover/row:text-primary transition-colors duration-150';
$lp_signal_row    = 'mt-[12px] flex items-center justify-between gap-[12px] px-[16px] py-[14px] bg-primary text-primary-content hover:bg-primary/85 transition-colors duration-150';
$lp_signal_name   = 'font-label text-[12px] font-semibold uppercase tracking-[1px]';
$lp_signal_meta   = 'font-label text-[10px] font-semibold uppercase tracking-[0.8px]';
$lp_glyph_states = array(
	'active'   => 'text-primary',
	'inactive' => 'text-neutral-content group-hover:text-primary',
);
$lp_item_borders = array(
	'active'   => 'border-primary',
	'inactive' => 'border-transparent group-hover:border-primary',
);

$lp_icon_from_label = array(
	'classes'   => 'glyph-vaulting',
	'tutorials' => 'glyph-jumping',
	'docs'      => 'glyph-rolling',
	'contact'   => 'glyph-plyometrics',
);

$lp_panel_from_label = array(
	'classes'   => 'classes',
	'tutorials' => 'tutorials',
	'docs'      => 'docs',
);

$lp_group_classes = array(
	'classes'   => 'group group/classes',
	'tutorials' => 'group group/tutorials',
	'docs'      => 'group group/docs',
);

$lp_panel_hover = array(
	'classes'   => 'hidden group-hover/classes:block group-focus-within/classes:block',
	'tutorials' => 'hidden group-hover/tutorials:block group-focus-within/tutorials:block',
	'docs'      => 'hidden group-hover/docs:block group-focus-within/docs:block',
);

$lp_panel_open = array(
	'classes'   => 'block',
	'tutorials' => 'block',
	'docs'      => 'block',
);

$lp_live_panels = function_exists( 'lp_nav_drop_panels' ) ? lp_nav_drop_panels() : array();

$lp_default_class_type_rows = array(
	array(
		'name' => 'Beginners Parkour',
		'meta' => 'VAUXHALL',
		'href' => '/classes/beginners-parkour',
	),
	array(
		'name' => 'Outdoor Class — Vauxhall',
		'meta' => 'VAUXHALL',
		'href' => '/classes/outdoor-class-vauxhall',
	),
	array(
		'name' => 'Evening Outdoor Class',
		'meta' => 'SOUTHBANK',
		'href' => '/classes/evening-outdoor-class',
	),
	array(
		'name' => 'Outdoor Class — Southbank',
		'meta' => 'SOUTHBANK',
		'href' => '/classes/outdoor-class-southbank',
	),
	array(
		'name' => 'Outdoor Class North',
		'meta' => 'WEMBLEY PARK',
		'href' => '/classes/outdoor-class-north',
	),
	array(
		'name' => 'Kids Class West (6–9s)',
		'meta' => 'VAUXHALL',
		'href' => '/classes/kids-class-west-6-9s',
	),
	array(
		'name' => 'Teens Class West (10–14s)',
		'meta' => 'VAUXHALL',
		'href' => '/classes/teens-class-west-10-14s',
	),
	array(
		'name' => 'Sunrise Session',
		'meta' => 'PECKHAM RYE',
		'href' => '/classes/sunrise-session',
	),
	array(
		'name' => 'Kids Parkour 5–11',
		'meta' => 'HACKNEY MARSHES',
		'href' => '/classes/kids-parkour-5-11',
	),
	array(
		'name' => 'Open Gym',
		'meta' => 'STRATFORD EAST',
		'href' => '/classes/open-gym',
	),
	array(
		'name' => "Women's Session",
		'meta' => 'SOUTHBANK',
		'href' => '/classes/womens-session',
	),
	array(
		'name' => 'Advanced Movement',
		'meta' => 'VAUXHALL',
		'href' => '/classes/advanced-movement',
	),
	array(
		'name' => 'Family Session',
		'meta' => 'WEMBLEY PARK',
		'href' => '/classes/family-session',
	),
);
$lp_default_classes_panel   = array(
	'columns'   => array(
		array(
			'title' => 'FIND',
			'note'  => '5',
			'rows'  => array(
				array(
					'name' => 'Agenda',
					'meta' => '18 SESSIONS',
					'href' => '/classes',
				),
				array(
					'name' => 'Map',
					'meta' => sprintf(
						'%d SITES',
						function_exists( 'lp_published_site_count' ) ? ( lp_published_site_count() ?: 3 ) : 3
					),
					'href' => '/classes-map',
				),
				array(
					'name' => 'Private 1:1',
					'meta' => 'ANY SITE',
					'href' => '/private-coaching',
				),
				array(
					'name' => 'Workshops',
					'meta' => '6 DATES',
					'href' => '/workshops/',
				),
				array(
					'name' => 'Coupons',
					'meta' => 'FROM £15',
					'href' => '/coupons/',
					'tone' => 'signal',
				),
			),
		),
		array(
			'title' => 'ALL CLASSES',
			'note'  => '01–13',
			'rows'  => $lp_default_class_type_rows,
		),
	),
	'all_label' => 'ALL CLASSES →',
	'all_href'  => '/all-classes',
	'alt_label' => 'OPEN THE MAP ↗',
	'alt_href'  => '/classes-map',
);

$lp_default_tutorials_panel = array(
	'columns'   => array(
		array(
			'title' => 'BROWSE',
			'note'  => '3',
			'rows'  => array(
				array(
					'name' => 'By series',
					'meta' => '12 SERIES',
					'href' => '/tutorials/series',
				),
				array(
					'name' => 'By category',
					'meta' => '11 CATEGORIES',
					'href' => '/tutorials/category',
				),
				array(
					'name' => 'By tutorial',
					'meta' => '840 VIDEOS',
					'href' => '/tutorials',
				),
			),
		),
		array(
			'title' => 'NEWEST SERIES',
			'note'  => '3',
			'rows'  => array(
				array(
					'name' => 'Flow Combinations',
					'meta' => '9 EPISODES',
					'href' => '/tutorials/flow',
				),
				array(
					'name' => 'Strength Conditioning',
					'meta' => '6 EPISODES',
					'href' => '/tutorials/strength',
				),
				array(
					'name' => 'Kids Curriculum',
					'meta' => '3 EPISODES',
					'href' => '/tutorials/kids',
				),
			),
		),
		array(
			'title' => 'NEWEST TUTORIALS',
			'note'  => '3',
			'rows'  => array(
				array(
					'name' => 'Slow and High',
					'meta' => 'VAULTING',
					'href' => '/tutorials/slow-and-high',
				),
				array(
					'name' => 'How to Cat Leap',
					'meta' => 'CLIMBING',
					'href' => '/tutorials/how-to-cat-leap',
				),
				array(
					'name' => 'Two Hands, One-foot.',
					'meta' => 'VAULTING',
					'href' => '/tutorials/two-hands-one-foot',
				),
			),
		),
	),
	'all_label' => 'ALL TUTORIALS →',
	'all_href'  => '/tutorials',
	'alt_label' => 'ALL SERIES ↗',
	'alt_href'  => '/tutorials/series',
);

$lp_default_docs_panel = array(
	'columns'   => array(
		array(
			'title' => 'WIKI',
			'note'  => '15 PAGES',
			'rows'  => array(
				array(
					'name' => 'Wiki',
					'meta' => '15 PAGES',
					'href' => '/docs',
				),
			),
		),
		array(
			'title' => 'BLOG',
			'note'  => '12 STORIES',
			'rows'  => array(
				array(
					'name' => 'Blog',
					'meta' => '12 STORIES',
					'href' => '/blog',
				),
			),
		),
	),
	'all_label' => 'WIKI →',
	'all_href'  => '/docs',
	'alt_label' => 'BLOG ↗',
	'alt_href'  => '/blog',
);

$lp_brand      = (string) ( $args['brand'] ?? 'London Parkour' );
$lp_home_href  = (string) ( $args['home_href'] ?? '/' );
$lp_variant    = isset( $lp_bar_heights[ $args['variant'] ?? '' ] ) ? (string) $args['variant'] : 'default';
$lp_over_hero  = ! empty( $args['over_hero'] );
$lp_site_label = (string) ( $args['find_site_label'] ?? 'Find a site' );
$lp_site_href  = (string) ( $args['find_site_href'] ?? ( function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes-map' ) : '/classes-map' ) );
$lp_cta_label  = (string) ( $args['cta_label'] ?? 'Find a class' );
$lp_cta_href   = (string) ( $args['cta_href'] ?? ( function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : '/classes' ) );
$lp_menu_id    = (string) ( $args['mobile_menu_id'] ?? 'site-nav-mobile-menu' );
$lp_open_panel = (string) ( $args['open_panel'] ?? '' );

$lp_links = array();

foreach ( is_array( $args['links'] ?? null ) ? $args['links'] : array() as $lp_row ) {
	if ( ! empty( $lp_row['label'] ) ) {
		$lp_links[] = $lp_row;
	}
}

if ( ! $lp_links ) {
	$lp_links = function_exists( 'lp_nav_default_links' ) ? lp_nav_default_links() : array(
		array(
			'label'   => 'Classes',
			'href'    => '/classes',
			'active'  => true,
			'panel'   => 'classes',
			'icon_id' => 'glyph-vaulting',
		),
		array(
			'label'   => 'Tutorials',
			'href'    => '/tutorials/series',
			'panel'   => 'tutorials',
			'icon_id' => 'glyph-jumping',
		),
		array(
			'label'   => 'Docs',
			'href'    => '/docs',
			'panel'   => 'docs',
			'icon_id' => 'glyph-rolling',
		),
		array(
			'label'   => 'Contact',
			'href'    => '/contact',
			'icon_id' => 'glyph-plyometrics',
		),
	);
}

$lp_classes_panel   = is_array( $args['classes_panel'] ?? null ) && $args['classes_panel'] ? $args['classes_panel'] : ( $lp_live_panels['classes'] ?? $lp_default_classes_panel );
$lp_tutorials_panel = is_array( $args['tutorials_panel'] ?? null ) && $args['tutorials_panel'] ? $args['tutorials_panel'] : ( $lp_live_panels['tutorials'] ?? $lp_default_tutorials_panel );
$lp_docs_panel      = is_array( $args['docs_panel'] ?? null ) && $args['docs_panel'] ? $args['docs_panel'] : ( $lp_live_panels['docs'] ?? $lp_default_docs_panel );

$lp_panel_data = array(
	'classes'   => $lp_classes_panel,
	'tutorials' => $lp_tutorials_panel,
	'docs'      => $lp_docs_panel,
);

$lp_resolved = array();

foreach ( $lp_links as $lp_link ) {
	$lp_label = (string) ( $lp_link['label'] ?? '' );
	$lp_key   = strtolower( $lp_label );
	$lp_panel = (string) ( $lp_link['panel'] ?? '' );
	if ( '' === $lp_panel && ! empty( $lp_link['has_panel'] ) ) {
		$lp_panel = 'classes';
	}
	if ( '' === $lp_panel ) {
		$lp_panel = $lp_panel_from_label[ $lp_key ] ?? '';
	}
	$lp_icon = (string) ( $lp_link['icon_id'] ?? '' );
	if ( '' === $lp_icon ) {
		$lp_icon = $lp_icon_from_label[ $lp_key ] ?? '';
	}
	$lp_resolved[] = array(
		'label'   => $lp_label,
		'href'    => (string) ( $lp_link['href'] ?? '#' ),
		'active'  => ! empty( $lp_link['active'] ),
		'panel'   => $lp_panel,
		'icon_id' => $lp_icon,
	);
}

$lp_is_condensed = 'condensed' === $lp_variant;
$lp_bar_height   = $lp_bar_heights[ $lp_variant ];
$lp_logo_width   = $lp_logo_widths[ $lp_variant ];
$lp_panel_pos    = $lp_panel_positions[ $lp_variant ];
$lp_header_ground = $lp_over_hero
	? 'bg-transparent absolute inset-x-0 top-0 z-20'
	: 'bg-neutral relative';
?>
<header data-component="site-nav" data-variant="<?php echo esc_attr( $lp_variant ); ?>" data-over-hero="<?php echo $lp_over_hero ? 'true' : 'false'; ?>" class="<?php echo esc_attr( $lp_header_ground ); ?>">
	<nav aria-label="Primary">
		<div class="<?php echo lp_classes( 'hidden lg:flex items-stretch justify-between border-b border-neutral-content/10 pl-[64px]', $lp_bar_height ); ?>">
			<a href="<?php echo esc_url( $lp_home_href ); ?>" aria-label="<?php echo esc_attr( $lp_brand ); ?>" class="flex items-center text-neutral-content hover:text-primary transition-colors duration-150">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => $lp_logo_width,
						'color_class' => 'text-current',
						'label'       => $lp_brand,
					)
				);
				?>
			</a>
			<div class="flex items-stretch gap-[32px]">
				<div class="flex items-stretch">
					<?php
					foreach ( $lp_resolved as $lp_i => $lp_link ) :
						$lp_active     = ! empty( $lp_link['active'] );
						$lp_glyph_cls  = $lp_active ? $lp_glyph_states['active'] : $lp_glyph_states['inactive'];
						$lp_border_cls = $lp_active ? $lp_item_borders['active'] : $lp_item_borders['inactive'];
						$lp_panel_key  = (string) $lp_link['panel'];
						$lp_group_cls  = $lp_group_classes[ $lp_panel_key ] ?? 'group';
						?>
						<?php if ( $lp_i > 0 ) : ?>
							<span class="<?php echo lp_classes( 'w-px', $lp_bar_height, 'bg-neutral-content/10' ); ?>" aria-hidden="true"></span>
						<?php endif; ?>
						<span class="<?php echo lp_classes( $lp_group_cls, $lp_bar_height, 'inline-flex items-center justify-center gap-[9px] px-[20px] border-b-[3px]', $lp_border_cls ); ?>">
							<?php
							if ( '' !== $lp_link['icon_id'] ) {
								lp_icon( $lp_link['icon_id'], lp_classes( $lp_glyph_base, $lp_glyph_cls ) );
							}
							lp_part(
								'elements/nav-link',
								array(
									'label'  => (string) $lp_link['label'],
									'href'   => (string) $lp_link['href'],
									'active' => $lp_active,
								)
							);
							if ( $lp_panel_key && isset( $lp_panel_data[ $lp_panel_key ] ) ) :
								$lp_panel      = $lp_panel_data[ $lp_panel_key ];
								$lp_panel_vis  = $lp_open_panel === $lp_panel_key
									? ( $lp_panel_open[ $lp_panel_key ] ?? 'block' )
									: ( $lp_panel_hover[ $lp_panel_key ] ?? 'hidden' );
								?>
								<div data-nav-panel="<?php echo esc_attr( $lp_panel_key ); ?>" class="<?php echo lp_classes( $lp_panel_vis, $lp_panel_pos, 'inset-x-0 w-full z-30 bg-neutral/95 border-b border-neutral-content/15' ); ?>">
									<div class="flex flex-col lg:flex-row flex-wrap gap-[32px] lg:gap-[44px] px-[20px] py-[32px] lg:px-[64px] lg:pt-[38px] lg:pb-[34px]">
										<?php foreach ( (array) ( $lp_panel['columns'] ?? array() ) as $lp_column ) : ?>
											<div class="flex-1 min-w-[220px]">
												<div class="flex items-center justify-between pb-[12px] border-b border-neutral-content/10">
													<span class="font-label text-[10px] font-semibold uppercase tracking-[1.2px] text-primary"><?php echo esc_html( (string) ( $lp_column['title'] ?? '' ) ); ?></span>
													<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( (string) ( $lp_column['note'] ?? '' ) ); ?></span>
												</div>
												<div class="divide-y divide-neutral-content/10">
													<?php
													$lp_regular_rows = array();
													$lp_signal_rows  = array();
													foreach ( (array) ( $lp_column['rows'] ?? array() ) as $lp_row ) {
														if ( 'signal' === ( $lp_row['tone'] ?? '' ) ) {
															$lp_signal_rows[] = $lp_row;
														} else {
															$lp_regular_rows[] = $lp_row;
														}
													}
													foreach ( $lp_regular_rows as $lp_row_i => $lp_row ) :
														$lp_row_name  = (string) ( $lp_row['name'] ?? '' );
														$lp_row_meta  = (string) ( $lp_row['meta'] ?? '' );
														$lp_row_href  = (string) ( $lp_row['href'] ?? '' );
														$lp_row_glyph = (string) ( $lp_row['glyph_id'] ?? '' );
														if ( '' === $lp_row_glyph && function_exists( 'lp_nav_row_glyph' ) ) {
															$lp_row_glyph = lp_nav_row_glyph( $lp_row_name, $lp_row_meta, (int) $lp_row_i );
														}
														if ( '' === $lp_row_glyph ) {
															$lp_row_glyph = 'glyph-vaulting';
														}
														?>
														<?php if ( '' !== $lp_row_href ) : ?>
															<a href="<?php echo esc_url( $lp_row_href ); ?>" class="<?php echo lp_classes( 'group/row block transition-colors duration-150', $lp_focus ); ?>">
																<span class="flex items-center justify-between py-[13px] gap-[16px]">
																	<span class="flex items-center gap-[10px] min-w-0">
																		<?php lp_icon( $lp_row_glyph, $lp_row_glyph_cls ); ?>
																		<span class="font-body text-[15px] font-medium tracking-[-0.1px] text-neutral-content group-hover/row:text-primary transition-colors duration-150"><?php echo esc_html( $lp_row_name ); ?></span>
																	</span>
																	<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50 group-hover/row:text-neutral-content transition-colors duration-150"><?php echo esc_html( $lp_row_meta ); ?></span>
																</span>
															</a>
														<?php else : ?>
															<span class="flex items-center justify-between py-[13px] gap-[16px]">
																<span class="flex items-center gap-[10px] min-w-0">
																	<?php lp_icon( $lp_row_glyph, $lp_row_glyph_cls ); ?>
																	<span class="font-body text-[15px] font-medium tracking-[-0.1px] text-neutral-content"><?php echo esc_html( $lp_row_name ); ?></span>
																</span>
																<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( $lp_row_meta ); ?></span>
															</span>
														<?php endif; ?>
													<?php endforeach; ?>
												</div>
												<?php foreach ( $lp_signal_rows as $lp_row ) : ?>
													<?php
													$lp_row_name = (string) ( $lp_row['name'] ?? '' );
													$lp_row_meta = (string) ( $lp_row['meta'] ?? '' );
													$lp_row_href = (string) ( $lp_row['href'] ?? '' );
													?>
													<?php if ( '' !== $lp_row_href ) : ?>
														<a href="<?php echo esc_url( $lp_row_href ); ?>" class="<?php echo lp_classes( 'group/row', $lp_signal_row, $lp_focus ); ?>">
															<span class="<?php echo esc_attr( $lp_signal_name ); ?>"><?php echo esc_html( $lp_row_name ); ?></span>
															<span class="flex items-center gap-[8px]">
																<span class="<?php echo esc_attr( $lp_signal_meta ); ?>"><?php echo esc_html( $lp_row_meta ); ?></span>
																<?php lp_icon( 'icon-arrow-right', 'w-[14px] h-[14px] flex-none' ); ?>
															</span>
														</a>
													<?php else : ?>
														<span class="<?php echo esc_attr( $lp_signal_row ); ?>">
															<span class="<?php echo esc_attr( $lp_signal_name ); ?>"><?php echo esc_html( $lp_row_name ); ?></span>
															<span class="flex items-center gap-[8px]">
																<span class="<?php echo esc_attr( $lp_signal_meta ); ?>"><?php echo esc_html( $lp_row_meta ); ?></span>
																<?php lp_icon( 'icon-arrow-right', 'w-[14px] h-[14px] flex-none' ); ?>
															</span>
														</span>
													<?php endif; ?>
												<?php endforeach; ?>
											</div>
										<?php endforeach; ?>
									</div>
									<div class="flex flex-col gap-[8px] sm:flex-row items-start sm:items-center justify-between gap-x-4 border-t border-neutral-content/10 px-[20px] py-[15px] lg:px-[64px]">
										<a href="<?php echo esc_url( (string) ( $lp_panel['all_href'] ?? '' ) ); ?>" class="<?php echo lp_classes( 'font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-primary hover:text-neutral-content transition-colors duration-150', $lp_focus ); ?>"><?php echo esc_html( (string) ( $lp_panel['all_label'] ?? '' ) ); ?></a>
										<a href="<?php echo esc_url( (string) ( $lp_panel['alt_href'] ?? '' ) ); ?>" class="<?php echo lp_classes( 'font-label text-[11px] font-normal uppercase tracking-[0.9px] text-neutral-content/50 hover:text-neutral-content transition-colors duration-150', $lp_focus ); ?>"><?php echo esc_html( (string) ( $lp_panel['alt_label'] ?? '' ) ); ?></a>
									</div>
								</div>
							<?php endif; ?>
						</span>
					<?php endforeach; ?>
				</div>
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
			<a href="<?php echo esc_url( $lp_home_href ); ?>" aria-label="<?php echo esc_attr( $lp_brand ); ?>" class="flex items-center text-neutral-content hover:text-primary transition-colors duration-150">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => 88,
						'color_class' => 'text-current',
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
					foreach ( $lp_resolved as $lp_link ) :
						$lp_active    = ! empty( $lp_link['active'] );
						$lp_glyph_cls = $lp_active ? $lp_glyph_states['active'] : $lp_glyph_states['inactive'];
						?>
						<span class="group flex items-center gap-[12px] py-[16px]">
							<?php
							if ( '' !== $lp_link['icon_id'] ) {
								lp_icon( $lp_link['icon_id'], lp_classes( $lp_glyph_base, $lp_glyph_cls ) );
							}
							?>
							<span class="flex-1">
								<?php
								lp_part(
									'elements/nav-link',
									array(
										'label'  => (string) $lp_link['label'],
										'href'   => (string) $lp_link['href'],
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
