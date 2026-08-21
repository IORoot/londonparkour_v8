<?php
/**
 * FAQ — shared Contact flat FAQ + DocsFaq grouped body.
 *
 * Ported from:
 *   - src/stories/Pages/Contact/Contact.js (`data-component="contact-faq"`, mode flat)
 *   - src/stories/Pages/DocsFaq/DocsFaq.js (`data-component="docs-faq-body"`, mode groups)
 *
 * @param string $args['mode']          flat|groups. Default flat.
 * @param string $args['meta_left']
 * @param string $args['meta_right']
 * @param array  $args['items']         Flat-mode repeater rows.
 * @param array  $args['still_stuck']    Flat-mode aside group.
 * @param array  $args['groups']         Groups-mode repeater rows.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_items  = lp_faq_default_items();
$lp_default_groups = lp_faq_default_groups();

$lp_mode = ( 'groups' === ( $args['mode'] ?? 'flat' ) ) ? 'groups' : 'flat';

$lp_spacing = lp_section_spacing( $args );

/**
 * Render one DocsFaq group block.
 *
 * @param array $lp_group   Group row.
 * @param int   $lp_ordinal Running item index (by reference).
 */
$lp_render_group = static function ( array $lp_group, int &$lp_ordinal ) {
	$lp_id      = sanitize_title( (string) ( $lp_group['id'] ?? '' ) );
	$lp_label   = (string) ( $lp_group['label'] ?? '' );
	$lp_entries = (string) ( $lp_group['entries'] ?? '' );
	$lp_icon    = (string) ( $lp_group['icon'] ?? '' );
	$lp_items   = is_array( $lp_group['items'] ?? null ) ? $lp_group['items'] : array();
	?>
	<div<?php echo $lp_id ? ' id="' . esc_attr( $lp_id ) . '"' : ''; ?> class="flex flex-col">
		<div class="w-full flex items-center justify-between gap-4 pb-[17px]" data-component="docs-faq-group-head">
			<span class="inline-flex items-center gap-3 font-label text-[11px] font-semibold tracking-[1px] uppercase text-base-content">
				<?php if ( $lp_icon ) : ?>
					<span class="shrink-0" aria-hidden="true"><?php lp_icon( $lp_icon, 'w-[14px] h-[14px]' ); ?></span>
				<?php endif; ?>
				<?php echo esc_html( $lp_label ); ?>
			</span>
			<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-base-content/65"><?php echo esc_html( $lp_entries ); ?></span>
		</div>
		<div class="h-px w-full bg-base-content" aria-hidden="true"></div>
		<div class="divide-y divide-base-300">
			<?php foreach ( $lp_items as $lp_item ) : ?>
				<?php
				if ( ! is_array( $lp_item ) ) {
					continue;
				}
				$lp_ordinal++;
				lp_part(
					'components/faq-item',
					array(
						'index'        => str_pad( (string) $lp_ordinal, 2, '0', STR_PAD_LEFT ),
						'question'     => (string) ( $lp_item['question'] ?? '' ),
						'answer'       => (string) ( $lp_item['answer'] ?? '' ),
						'surface'      => 'page',
						'collapsible'  => false,
					)
				);
				?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
};

if ( 'groups' === $lp_mode ) :
	$lp_groups = array();
	foreach ( is_array( $args['groups'] ?? null ) ? $args['groups'] : array() as $lp_row ) {
		if ( is_array( $lp_row ) ) {
			$lp_groups[] = $lp_row;
		}
	}
	if ( ! $lp_groups ) {
		$lp_groups = $lp_default_groups;
	}

	$lp_group_answers = array();
	foreach ( $lp_default_groups as $lp_default_group ) {
		foreach ( (array) ( $lp_default_group['items'] ?? array() ) as $lp_default_item ) {
			if ( ! empty( $lp_default_item['question'] ) ) {
				$lp_group_answers[ $lp_default_item['question'] ] = $lp_default_item['answer'];
			}
		}
	}
	foreach ( $lp_groups as &$lp_group ) {
		if ( empty( $lp_group['items'] ) || ! is_array( $lp_group['items'] ) ) {
			continue;
		}
		foreach ( $lp_group['items'] as &$lp_group_item ) {
			$lp_q = (string) ( $lp_group_item['question'] ?? '' );
			if ( isset( $lp_group_answers[ $lp_q ] ) ) {
				$lp_group_item['answer'] = $lp_group_answers[ $lp_q ];
			}
		}
		unset( $lp_group_item );
	}
	unset( $lp_group );
	?>
<section class="<?php echo lp_classes( 'w-full bg-base-100', $lp_spacing ); ?>" data-component="docs-faq-body"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="px-6 lg:px-16 py-scale-xl flex flex-col gap-[48px]">
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16 gap-y-[66px]">
			<div class="flex flex-col gap-[66px]">
				<?php
				$lp_ordinal = 0;
				if ( isset( $lp_groups[0] ) ) {
					$lp_render_group( $lp_groups[0], $lp_ordinal );
				}
				?>
			</div>
			<div class="flex flex-col gap-[66px]">
				<?php
				for ( $lp_i = 1, $lp_n = count( $lp_groups ); $lp_i < $lp_n; $lp_i++ ) {
					$lp_render_group( $lp_groups[ $lp_i ], $lp_ordinal );
				}
				?>
			</div>
		</div>
	</div>
</section>
	<?php
	return;
endif;

// Flat mode — Contact FAQ.
$lp_meta_left  = (string) ( $args['meta_left'] ?? 'BEFORE YOU GET IN TOUCH' );
$lp_meta_right = (string) ( $args['meta_right'] ?? '' );

$lp_items = array();
foreach ( is_array( $args['items'] ?? null ) ? $args['items'] : array() as $lp_row ) {
	if ( is_array( $lp_row ) && ! empty( $lp_row['question'] ) ) {
		$lp_items[] = $lp_row;
	}
}
if ( ! $lp_items ) {
	$lp_items = $lp_default_items;
}

$lp_canonical_answers = array();
foreach ( $lp_default_items as $lp_default_item ) {
	$lp_canonical_answers[ $lp_default_item['question'] ] = $lp_default_item['answer'];
}
foreach ( $lp_items as &$lp_item ) {
	$lp_question = (string) ( $lp_item['question'] ?? '' );
	if ( isset( $lp_canonical_answers[ $lp_question ] ) ) {
		$lp_item['answer'] = $lp_canonical_answers[ $lp_question ];
	}
}
unset( $lp_item );

$lp_still         = is_array( $args['still_stuck'] ?? null ) ? $args['still_stuck'] : array();
$lp_still_title   = (string) ( $lp_still['title'] ?? 'STILL STUCK?' );
$lp_still_body    = (string) ( $lp_still['body'] ?? 'Send the form above, or email us direct. A coach reads every message — we reply within 36H.' );
if ( false !== stripos( $lp_still_body, 'working day' ) || false !== stripos( $lp_still_body, '24h' ) ) {
	$lp_still_body = 'Send the form above, or email us direct. A coach reads every message — we reply within 36H.';
}
$lp_still_email   = (string) ( $lp_still['email'] ?? 'hello@londonparkour.com' );
$lp_still_mailto  = 'mailto:' . $lp_still_email;
?>
<section class="<?php echo lp_classes( 'w-full bg-base-100', $lp_spacing ); ?>" data-component="contact-faq" data-surface="page"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="px-6 lg:px-16 pt-scale-xl pb-scale-2xl">
		<div class="flex flex-col">
			<?php
			lp_part(
				'components/meta-row',
				array(
					'left'  => $lp_meta_left,
					'right' => $lp_meta_right,
				)
			);
			lp_part( 'elements/rule', array( 'tone' => 'hairline' ) );
			?>
			<div class="mt-[52px] flex flex-col lg:flex-row gap-10 lg:gap-20 items-start">
				<div class="flex-1 min-w-0 flex flex-col divide-y divide-base-300">
					<?php foreach ( $lp_items as $lp_i => $lp_item ) : ?>
						<?php
						lp_part(
							'components/faq-item',
							array(
								'index'        => str_pad( (string) ( $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
								'question'     => (string) ( $lp_item['question'] ?? '' ),
								'answer'       => (string) ( $lp_item['answer'] ?? '' ),
								'default_open' => ! empty( $lp_item['default_open'] ),
								'surface'      => 'page',
							)
						);
						?>
					<?php endforeach; ?>
				</div>
				<aside class="w-full lg:w-[380px] lg:shrink-0 flex flex-col gap-4 border-t border-base-content pt-scale-s">
					<?php
					lp_part(
						'elements/glyph-label',
						array(
							'label'   => $lp_still_title,
							'surface' => 'page',
							'tone'    => 'ink',
						)
					);
					?>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp_still_body ); ?></p>
					<a href="<?php echo esc_url( $lp_still_mailto ); ?>" class="inline-flex items-center gap-[9px] font-body text-[16px] font-medium tracking-[0.1px] text-base-content hover:text-base-content/70 transition-colors duration-150">
						<?php echo esc_html( $lp_still_email ); ?>
						<?php lp_icon( 'icon-arrow-up-right', 'w-[12px] h-[12px]' ); ?>
					</a>
				</aside>
			</div>
		</div>
	</div>
</section>
