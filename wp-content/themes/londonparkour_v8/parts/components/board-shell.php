<?php
/**
 * BoardShell — the frame a run of board rows sits in: head, column strip,
 * the row list, and a foot.
 *
 * Ported from src/stories/Components/BoardShell/BoardShell.js.
 *
 * The live stamp is elements/status.php (`live`/`board`) — this is the one
 * status instance the source already routes correctly. The foot CTA is
 * elements/text-link.php variant `board`, byte-identical per
 * docs/CONSOLIDATION.md §4a.
 *
 * DELIBERATE DEPARTURE: the source takes `rows` as live DOM elements (or a
 * mount callback) and appends them. Server-side that becomes a declarative
 * list — each row names a partial and its args, and this file wraps each in
 * the <li>. Passing pre-rendered HTML was rejected: it would put an unescaped
 * echo in the hot path of every board on the site.
 *
 * `role="list"` is deliberate — a list-style:none <ul> loses its list
 * semantics in Safari without it (Port Brief rule 7).
 *
 * @param string $args['board_title'] Omit to render no head.
 * @param string $args['live_label']  Renders the live stamp when set.
 * @param array  $args['columns']     Strings, or array( 'label' => …, 'cls' => … ).
 * @param array  $args['rows']        array of array( 'part' => slug, 'args' => array() ).
 * @param string $args['foot_left']   CTA label when foot_href is set, else a note.
 * @param string $args['foot_href']
 * @param string $args['foot_right']  Right-hand note.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_col_default = 'flex-1 min-w-0';
$lp_col_type    = 'font-label text-[10px] font-semibold uppercase tracking-[1.1px] text-neutral-content/50';
$lp_foot_note   = 'font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50';

$lp_board_title = (string) ( $args['board_title'] ?? '' );
$lp_live_label  = (string) ( $args['live_label'] ?? '' );
$lp_columns     = is_array( $args['columns'] ?? null ) ? $args['columns'] : array();
$lp_rows        = is_array( $args['rows'] ?? null ) ? $args['rows'] : array();
$lp_foot_left   = (string) ( $args['foot_left'] ?? '' );
$lp_foot_href   = (string) ( $args['foot_href'] ?? '' );
$lp_foot_right  = (string) ( $args['foot_right'] ?? '' );

$lp_has_foot = '' !== $lp_foot_left || '' !== $lp_foot_right;
?>
<div class="w-full flex flex-col" data-component="board-shell">
	<?php if ( '' !== $lp_board_title ) : ?>
		<div class="flex items-center justify-between gap-3 pb-[13px] border-b border-neutral-content/20">
			<h3 class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary"><?php echo esc_html( $lp_board_title ); ?></h3>
			<?php
			if ( '' !== $lp_live_label ) {
				lp_part(
					'elements/status',
					array(
						'variant' => 'live',
						'surface' => 'board',
						'label'   => $lp_live_label,
					)
				);
			}
			?>
		</div>
	<?php endif; ?>
	<?php if ( $lp_columns ) : ?>
		<div class="hidden sm:flex items-center gap-[24px] py-[13px] border-b border-neutral-content/10" aria-hidden="true">
			<?php
			foreach ( $lp_columns as $lp_col ) :
				$lp_col_label = is_array( $lp_col ) ? (string) ( $lp_col['label'] ?? '' ) : (string) $lp_col;
				$lp_col_cls   = is_array( $lp_col ) && ! empty( $lp_col['cls'] ) ? (string) $lp_col['cls'] : $lp_col_default;
				?>
				<span class="<?php echo lp_classes( $lp_col_cls, $lp_col_type ); ?>"><?php echo esc_html( $lp_col_label ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<ul role="list" class="flex flex-col w-full m-0 p-0 list-none">
		<?php foreach ( $lp_rows as $lp_row ) : ?>
			<li>
				<?php
				if ( ! empty( $lp_row['part'] ) ) {
					lp_part( (string) $lp_row['part'], is_array( $lp_row['args'] ?? null ) ? $lp_row['args'] : array() );
				}
				?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $lp_has_foot ) : ?>
		<div class="flex items-center justify-between gap-4 flex-wrap py-[17px] border-t border-neutral-content/10">
			<?php if ( '' !== $lp_foot_href ) : ?>
				<?php
				lp_part(
					'elements/text-link',
					array(
						'label'   => $lp_foot_left,
						'href'    => $lp_foot_href,
						'variant' => 'board',
					)
				);
				?>
			<?php elseif ( '' !== $lp_foot_left ) : ?>
				<span class="<?php echo esc_attr( $lp_foot_note ); ?>"><?php echo esc_html( $lp_foot_left ); ?></span>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<?php if ( '' !== $lp_foot_right ) : ?>
				<span class="<?php echo esc_attr( $lp_foot_note ); ?>"><?php echo esc_html( $lp_foot_right ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
