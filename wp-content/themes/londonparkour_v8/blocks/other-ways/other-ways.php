<?php
/**
 * Other Ways — Contact page 03 Other Ways: meta row + four glyph columns.
 *
 * Ported from src/stories/Pages/Contact/Contact.js (`data-component="contact-other-ways"`).
 *
 * @param string $args['meta_left']
 * @param string $args['meta_right']
 * @param array  $args['columns'] Rows of icon_id, label, value, link_label, link_href, note.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_columns = array(
	array(
		'icon_id'    => 'icon-map-pin',
		'label'      => 'STUDIO ADDRESS',
		'value'      => "24 Britannia Walk\nHoxton, London N1 7JR",
		'link_label' => 'STREETVIEW ↗',
		'link_href'  => '#',
	),
	array(
		'icon_id' => 'icon-phone',
		'label'   => 'PHONE',
		'value'   => '+44 20 7946 0958',
		'note'    => 'MON–SAT, 9AM–6PM',
	),
	array(
		'icon_id' => 'icon-envelope',
		'label'   => 'EMAIL',
		'value'   => 'hello@londonparkour.com',
		'note'    => 'REPLIES WITHIN 24H',
	),
	array(
		'icon_id' => 'icon-clock',
		'label'   => 'OPENING HOURS',
		'value'   => "Mon–Fri — 07:00 to 21:00\nSat–Sun — 09:00 to 18:00",
	),
);

$lp_meta_left  = (string) ( $args['meta_left'] ?? 'OTHER WAYS TO REACH US' );
$lp_meta_right = (string) ( $args['meta_right'] ?? 'MON–SAT · 09:00–18:00' );

$lp_columns = array();
foreach ( is_array( $args['columns'] ?? null ) ? $args['columns'] : array() as $lp_row ) {
	if ( is_array( $lp_row ) && ( ! empty( $lp_row['label'] ) || ! empty( $lp_row['value'] ) ) ) {
		$lp_columns[] = $lp_row;
	}
}
if ( ! $lp_columns ) {
	$lp_columns = $lp_default_columns;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full bg-base-200', $lp_spacing ); ?>" data-component="contact-other-ways" data-surface="page"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
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
			<div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-14">
				<?php foreach ( $lp_columns as $lp_col ) : ?>
					<?php
					$lp_value      = (string) ( $lp_col['value'] ?? '' );
					$lp_link_label = (string) ( $lp_col['link_label'] ?? '' );
					$lp_link_href  = (string) ( $lp_col['link_href'] ?? '' );
					$lp_col_note   = (string) ( $lp_col['note'] ?? '' );
					?>
					<div class="flex flex-col gap-4" data-column>
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => (string) ( $lp_col['label'] ?? '' ),
								'icon_id' => (string) ( $lp_col['icon_id'] ?? '' ),
								'surface' => 'page',
								'tone'    => 'ink',
							)
						);
						?>
						<p class="font-body text-[17px] leading-[1.4] tracking-[-0.1px] text-base-content whitespace-pre-line m-0"><?php echo esc_html( $lp_value ); ?></p>
						<?php if ( '' !== $lp_link_label ) : ?>
							<a href="<?php echo esc_url( $lp_link_href ?: '#' ); ?>" class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-base-content/65 hover:text-base-content transition-colors duration-150"><?php echo esc_html( $lp_link_label ); ?></a>
						<?php elseif ( '' !== $lp_col_note ) : ?>
							<span class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_col_note ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
