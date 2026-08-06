<?php
/**
 * ViewRail — the tablist that switches page view modes.
 *
 * Ported from src/stories/Components/ViewRail/ViewRail.js.
 *
 * Two contexts, one component: `concourse` is a light rail of plain
 * elements/view-tab.php tabs with an optional right-hand stamp; anything else
 * is the dark rail of `rich` two-line tabs. Both tab shapes live in
 * elements/view-tab.php — this file composes, it never retypes a <button>.
 *
 * The source binds click handlers here; server-side there is nothing to bind,
 * so each tab carries its data-tab-index and the page's own JS owns selection.
 *
 * When the tabs carry `href` they render as links and the inner div drops
 * role="tablist" — a set of links to three separate pages is navigation, not a
 * tablist, and claiming otherwise promises arrow-key behaviour that does not
 * exist. That is what the Classes view rail is: the source exposes cross-page
 * navigation as an `onTabSelect` callback precisely because it has no opinion
 * on routing, and on WordPress the answer is a URL.
 *
 * @param string $args['context']    concourse|classes|… Default 'classes'.
 * @param array  $args['tabs']       Ordered tabs: label, meta, icon_id, active, href.
 * @param string $args['stamp']      concourse only — right-hand stamp text.
 * @param string $args['aria_label'] Default 'View'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_context    = (string) ( $args['context'] ?? 'classes' );
$lp_tabs       = is_array( $args['tabs'] ?? null ) ? array_values( $args['tabs'] ) : array();
$lp_stamp      = (string) ( $args['stamp'] ?? '' );
$lp_aria_label = (string) ( $args['aria_label'] ?? 'View' );

// Links are navigation, not a tablist — see the docblock.
$lp_is_links = (bool) array_filter( $lp_tabs, static fn( $lp_t ) => ! empty( $lp_t['href'] ) );
?>
<?php if ( 'concourse' === $lp_context ) : ?>
	<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex flex-wrap items-center justify-between gap-4 bg-base-100 border-b border-base-300 px-6" data-component="view-rail" data-context="concourse">
		<div role="tablist" aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex items-center gap-[34px]">
			<?php foreach ( $lp_tabs as $lp_tab ) : ?>
				<span>
					<?php
					lp_part(
						'elements/view-tab',
						array(
							'label'  => $lp_tab['label'] ?? '',
							'active' => ! empty( $lp_tab['active'] ),
						)
					);
					?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php if ( '' !== $lp_stamp ) : ?>
			<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-base-content/65 whitespace-nowrap"><?php echo esc_html( $lp_stamp ); ?></span>
		<?php endif; ?>
	</nav>
<?php else : ?>
	<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="bg-neutral" data-component="view-rail" data-context="<?php echo esc_attr( $lp_context ); ?>">
		<div <?php echo $lp_is_links ? '' : 'role="tablist"'; ?> aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex flex-wrap">
			<?php foreach ( $lp_tabs as $lp_i => $lp_tab ) : ?>
				<?php
				lp_part(
					'elements/view-tab',
					array(
						'variant' => 'rich',
						'label'   => $lp_tab['label'] ?? '',
						'meta'    => $lp_tab['meta'] ?? '',
						'icon_id' => $lp_tab['icon_id'] ?? 'icon-squares-2x2',
						'active'  => ! empty( $lp_tab['active'] ),
						'href'    => $lp_tab['href'] ?? '',
						'index'   => $lp_i,
					)
				);
				?>
			<?php endforeach; ?>
		</div>
	</nav>
<?php endif; ?>
