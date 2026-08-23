<?php
/**
 * Passenger Enquiries — DocsFaq primary signal band.
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js (`data-component="docs-faq-passenger-enquiries"`).
 *
 * @param string $args['kicker']
 * @param string $args['live_label']
 * @param array  $args['facts']  Repeater rows of label/value.
 * @param string $args['note']
 * @param array  $args['cta']    ACF action group.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_locations = function_exists( 'lp_contact_locations_inline' ) ? lp_contact_locations_inline() : '';

$lp_default_facts = array(
	array(
		'label' => 'EMAIL',
		'value' => 'hello@londonparkour.com',
	),
	array(
		'label' => 'LOCATIONS',
		'value' => $lp_locations ? $lp_locations : 'Vauxhall · Old Street · Kilburn Park',
	),
	array(
		'label' => 'HOURS',
		'value' => 'Wed, Sat, Sun · 09:00–20:00',
	),
	array(
		'label' => 'REPLY TIME',
		'value' => 'Within 36H',
	),
);

$lp_kicker     = (string) ( $args['kicker'] ?? 'PASSENGER ENQUIRIES' );
$lp_live_label = (string) ( $args['live_label'] ?? 'OPEN NOW' );
$lp_note       = (string) ( $args['note'] ?? 'We reply within 36H. Email is the fastest way to reach us.' );
if ( false !== stripos( $lp_note, 'phone' ) || false !== stripos( $lp_note, 'working day' ) ) {
	$lp_note = 'We reply within 36H. Email is the fastest way to reach us.';
}

$lp_cta = lp_action( $args['cta'] ?? null );
if ( ! $lp_cta ) {
	$lp_cta = array(
		'label'  => 'SEND US A MESSAGE',
		'href'   => '/contact',
		'target' => '',
	);
}

$lp_facts = array();
foreach ( is_array( $args['facts'] ?? null ) ? $args['facts'] : array() as $lp_row ) {
	if ( is_array( $lp_row ) && ( ! empty( $lp_row['label'] ) || ! empty( $lp_row['value'] ) ) ) {
		$lp_facts[] = $lp_row;
	}
}
if ( ! $lp_facts ) {
	$lp_facts = $lp_default_facts;
} else {
	$lp_facts_blob = wp_json_encode( $lp_facts );
	if ( is_string( $lp_facts_blob ) && ( false !== stripos( $lp_facts_blob, 'phone' ) || false !== stripos( $lp_facts_blob, '020' ) || false !== stripos( $lp_facts_blob, 'working day' ) || false !== stripos( $lp_facts_blob, 'tue' ) ) ) {
		$lp_facts = $lp_default_facts;
	}
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full bg-primary', $lp_spacing ); ?>" data-component="docs-faq-passenger-enquiries"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[28px]">
		<div class="flex flex-wrap items-center justify-between gap-4">
			<?php
			lp_part(
				'elements/glyph-label',
				array(
					'label'   => $lp_kicker,
					'surface' => 'fill',
				)
			);
			lp_part(
				'elements/status',
				array(
					'variant' => 'live',
					'label'   => $lp_live_label,
					'surface' => 'fill',
				)
			);
			?>
		</div>
		<div class="flex flex-wrap gap-x-[56px] gap-y-[24px]">
			<?php foreach ( $lp_facts as $lp_fact ) : ?>
				<?php
				lp_part(
					'components/fact-row',
					array(
						'label'   => (string) ( $lp_fact['label'] ?? '' ),
						'value'   => (string) ( $lp_fact['value'] ?? '' ),
						'surface' => 'fill',
					)
				);
				?>
			<?php endforeach; ?>
		</div>
		<p class="max-w-[520px] font-body text-[13px] leading-[1.6] tracking-[0.1px] text-primary-content/70 m-0"><?php echo esc_html( $lp_note ); ?></p>
		<?php
		lp_part(
			'elements/button',
			array(
				'variant' => 'inverse',
				'label'   => $lp_cta['label'],
				'href'    => $lp_cta['href'],
				'target'  => $lp_cta['target'],
			)
		);
		?>
	</div>
</section>
