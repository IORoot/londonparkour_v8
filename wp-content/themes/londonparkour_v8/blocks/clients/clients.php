<?php
/**
 * Clients — "06 — CLIENTS / TRUSTED BY": accent logo grid of client GIFs.
 *
 * Ported from src/stories/Blocks/Clients/Clients.js.
 *
 * Cells are linked white transparent GIF wordmarks (uploads/Logos), falling back to a text
 * wordmark when no image is given. Ground is `bg-accent`; ink / muted /
 * hairline follow the accent column of docs/phase7/surface-axis.md
 * (`accent-content`). The 1px channel is `gap-px` on an `accent-content/15`
 * track — same as the Storybook source.
 *
 * Below `lg` the logos are a `data-motion-marquee` ticker so all twelve
 * cycle continuously (same motion layer as the yellow Marquee band).
 * `data-motion-marquee-mq` keeps the loop off the desktop grid. From `lg`
 * it is the 6-col grid. Reduced motion skips the loop; the wrapper
 * becomes swipeable.
 *
 * @param string $args['eyebrow']
 * @param string $args['meta']
 * @param array  $args['logos'] Rows of array( 'label', 'href', 'image' ).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_cell      = 'flex items-center justify-center min-h-[112px] min-w-0 px-3 bg-accent';
$lp_cell_link = 'flex items-center justify-center min-h-[112px] min-w-0 px-3 bg-accent no-underline hover:opacity-80 transition-opacity duration-150';
$lp_logo_img  = 'min-w-0 max-h-[120px] max-w-full w-auto h-auto object-contain';

$lp_default_logos = array(
	array(
		'label' => 'The Ned',
		'href'  => 'https://www.thened.com/',
		'file'  => 'transparent_ned_white.gif',
	),
	array(
		'label' => 'Vivobarefoot',
		'href'  => 'https://www.vivobarefoot.com',
		'file'  => 'transparent_vivo_white.gif',
	),
	array(
		'label' => 'Imperial',
		'href'  => 'https://www.instagram.com/reel/DBjrtI6vbMy/?utm_source=ig_web_copy_link&igsh=MzRlODBiNWFlZA==',
		'file'  => 'transparent_imperial_white.gif',
	),
	array(
		'label' => 'Sky',
		'href'  => '/blog/sky/',
		'file'  => 'transparent_sky_white.gif',
	),
	array(
		'label' => 'Sandringham',
		'href'  => '/blog/sandringham-flower-show',
		'file'  => 'transparent_sandringham_white.gif',
	),
	array(
		'label' => '2012 Olympics',
		'href'  => 'https://www.youtube.com/watch?v=KTZbSYxg5Pk',
		'file'  => 'transparent_olympics_white.gif',
	),
	array(
		'label' => 'The Guardian',
		'href'  => '/blog/the-guardian/',
		'file'  => 'transparent_guardian_white.gif',
	),
	array(
		'label' => 'The Stranglers',
		'href'  => '/blog/stranglers/',
		'file'  => 'transparent_stranglers_white.gif',
	),
	array(
		'label' => 'Universal',
		'href'  => 'https://www.youtube.com/watch?v=Ab6rslFsr_M',
		'file'  => 'transparent_universal_white.gif',
	),
	array(
		'label' => 'Ministry of Defence',
		'href'  => 'https://www.gov.uk/government/organisations/ministry-of-defence',
		'file'  => 'transparent_mod_white.gif',
	),
	array(
		'label' => 'Soho House',
		'href'  => 'https://www.sohohouse.com/',
		'file'  => 'transparent_soho_house_white.gif',
	),
	array(
		'label' => 'Army',
		'href'  => '/blog/the-army/',
		'file'  => 'transparent_army_white.gif',
	),
);

$lp_defaults_by_label = array();
foreach ( $lp_default_logos as $lp_default ) {
	$lp_defaults_by_label[ strtoupper( $lp_default['label'] ) ] = $lp_default;
}

$lp_logo_file_url = static function ( string $lp_file ): string {
	$lp_file = basename( $lp_file );
	if ( '' === $lp_file ) {
		return '';
	}
	return content_url( '/uploads/Logos/' . $lp_file );
};

$lp_logo_href = static function ( string $lp_href ): string {
	$lp_href = trim( $lp_href );
	if ( '' === $lp_href ) {
		return '';
	}
	if ( str_starts_with( $lp_href, '/' ) ) {
		return home_url( $lp_href );
	}
	return $lp_href;
};

$lp_eyebrow = lp_section_label( (string) ( $args['eyebrow'] ?? '06 — CLIENTS / TRUSTED BY' ), $args['_section_number'] ?? null );
$lp_meta    = (string) ( $args['meta'] ?? '(12)' );

$lp_logos = array();
foreach ( is_array( $args['logos'] ?? null ) ? $args['logos'] : array() as $lp_row ) {
	if ( ! is_array( $lp_row ) ) {
		$lp_label = trim( (string) $lp_row );
		if ( '' === $lp_label ) {
			continue;
		}
		$lp_row = array( 'label' => $lp_label );
	}

	$lp_label = trim( (string) ( $lp_row['label'] ?? '' ) );
	if ( '' === $lp_label ) {
		continue;
	}

	$lp_known = $lp_defaults_by_label[ strtoupper( $lp_label ) ] ?? array();
	$lp_href  = (string) ( $lp_row['href'] ?? '' );
	if ( '' === $lp_href ) {
		$lp_href = (string) ( $lp_known['href'] ?? '' );
	}

	$lp_image_id  = ! empty( $lp_row['image'] ) ? (int) $lp_row['image'] : 0;
	$lp_image_url = $lp_image_id ? (string) wp_get_attachment_url( $lp_image_id ) : '';
	if ( '' === $lp_image_url ) {
		$lp_file = (string) ( $lp_row['file'] ?? ( $lp_known['file'] ?? '' ) );
		$lp_image_url = $lp_logo_file_url( $lp_file );
	}

	$lp_logos[] = array(
		'label'     => $lp_label,
		'href'      => $lp_logo_href( $lp_href ),
		'image_url' => $lp_image_url,
	);
}
if ( ! $lp_logos ) {
	foreach ( $lp_default_logos as $lp_default ) {
		$lp_logos[] = array(
			'label'     => $lp_default['label'],
			'href'      => $lp_logo_href( $lp_default['href'] ),
			'image_url' => $lp_logo_file_url( $lp_default['file'] ),
		);
	}
}

$lp_spacing = lp_section_spacing( $args );

$lp_item_classes = array(
	'marquee' => 'min-w-0 w-[160px] shrink-0',
	'grid'    => 'min-w-0',
);

$lp_emit_logo_items = static function ( string $lp_item_class ) use ( $lp_logos, $lp_cell, $lp_cell_link, $lp_logo_img ): void {
	foreach ( $lp_logos as $lp_logo ) :
		?>
		<div role="listitem" class="<?php echo esc_attr( $lp_item_class ); ?>">
			<?php if ( '' !== $lp_logo['href'] ) : ?>
				<a
					href="<?php echo esc_url( $lp_logo['href'] ); ?>"
					target="_blank"
					rel="noopener noreferrer"
					class="<?php echo esc_attr( $lp_cell_link ); ?>"
					data-component="clients-logo"
				>
			<?php else : ?>
				<div
					class="<?php echo esc_attr( $lp_cell ); ?>"
					data-component="clients-logo"
				>
			<?php endif; ?>
				<?php if ( '' !== $lp_logo['image_url'] ) : ?>
					<?php
					lp_part(
						'components/media-photo',
						array(
							'image_url' => $lp_logo['image_url'],
							'alt'       => $lp_logo['label'],
							'layout'    => 'none',
							'class'     => $lp_logo_img,
						)
					);
					?>
				<?php else : ?>
					<span class="font-label text-[14px] sm:text-[16px] font-semibold tracking-[1.2px] uppercase text-accent-content text-center leading-none"><?php echo esc_html( $lp_logo['label'] ); ?></span>
				<?php endif; ?>
			<?php echo '' !== $lp_logo['href'] ? '</a>' : '</div>'; ?>
		</div>
		<?php
	endforeach;
};
?>
<section
	class="<?php echo lp_classes( 'w-full bg-accent px-6 py-[120px] lg:px-16', $lp_spacing ); ?>"
	data-component="clients"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<div class="flex flex-col gap-[36px]">
		<header class="flex flex-col gap-[18px]">
			<div class="flex items-baseline justify-between gap-4">
				<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-accent-content/70"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<?php if ( '' !== $lp_meta ) : ?>
					<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-accent-content/70"><?php echo esc_html( $lp_meta ); ?></span>
				<?php endif; ?>
			</div>
			<div class="h-px w-full bg-accent-content/15" aria-hidden="true"></div>
		</header>

		<div
			class="overflow-hidden min-w-0 w-full lg:hidden motion-reduce:overflow-x-auto"
			data-clients-track="marquee"
		>
			<div
				class="flex w-max gap-px bg-accent-content/15"
				role="list"
				aria-label="<?php echo esc_attr__( 'Trusted by', 'londonparkour_v8' ); ?>"
				data-motion-marquee
				data-motion-marquee-direction="left"
				data-motion-marquee-speed="50"
				data-motion-marquee-mq="(max-width: 1023px)"
			>
				<?php $lp_emit_logo_items( $lp_item_classes['marquee'] ); ?>
			</div>
		</div>
		<div
			class="hidden lg:grid lg:grid-cols-6 gap-px bg-accent-content/15"
			role="list"
			aria-label="<?php echo esc_attr__( 'Trusted by', 'londonparkour_v8' ); ?>"
			data-clients-track="grid"
		>
			<?php $lp_emit_logo_items( $lp_item_classes['grid'] ); ?>
		</div>
	</div>
</section>

