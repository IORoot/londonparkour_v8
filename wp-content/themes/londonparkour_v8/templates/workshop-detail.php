<?php
/**
 * Workshop detail — included from single-clasbpro_class.php for one-off posts.
 *
 * Ported from src/stories/Pages/WorkshopDetail/WorkshopDetail.js (`M4pN1`).
 * Header/footer stay on the caller. This file owns <main>.
 *
 * No View Rail. Booking aside is omitted after the sitting’s end. Empty
 * gallery / film / who / coaches / about hide their bands.
 *
 * Meeting-point markup is duplicated from single-clasbpro_class.php — reported,
 * not promoted.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_post_id = get_the_ID();
$lp_classes = lp_classes_page_url( 'classes' );
$lp_workshops = lp_workshops_url();

$lp_about = trim( (string) get_the_content() );

$lp_subtitle = lp_class_composed_subtitle( $lp_post_id );

$lp_location_id    = lp_class_location_id( $lp_post_id );
$lp_location_title = $lp_location_id ? get_the_title( $lp_location_id ) : '';
$lp_meeting_point  = $lp_location_id ? (string) get_field( 'meeting_point', $lp_location_id ) : '';
$lp_lat            = $lp_location_id ? trim( (string) get_field( 'latitude', $lp_location_id ) ) : '';
$lp_lon            = $lp_location_id ? trim( (string) get_field( 'longitude', $lp_location_id ) ) : '';
$lp_transport_rail = $lp_location_id ? (string) get_field( 'transport_rail', $lp_location_id ) : '';
$lp_transport_bus  = $lp_location_id ? (string) get_field( 'transport_bus', $lp_location_id ) : '';
$lp_location_meta  = $lp_location_id ? (string) get_field( 'meta', $lp_location_id ) : '';
$lp_streetview     = $lp_location_id ? lp_location_streetview_url( (int) $lp_location_id ) : '';
$lp_osm_maps       = lp_osm_maps_url( $lp_lat, $lp_lon );
$lp_coords_label   = ( '' !== $lp_lat && '' !== $lp_lon ) ? sprintf( '%s / %s', $lp_lat, $lp_lon ) : '';
$lp_foot_parts     = array_filter(
	array(
		( '' !== $lp_lat && '' !== $lp_lon ) ? sprintf( '%s°N %s°W', $lp_lat, ltrim( $lp_lon, '-' ) ) : '',
		$lp_location_meta,
	)
);
$lp_meeting_foot   = implode( ' · ', $lp_foot_parts );
$lp_meeting_kicker = implode(
	' · ',
	array_filter(
		array(
			'WORKSHOP',
			$lp_location_title ? strtoupper( $lp_location_title ) : '',
		)
	)
);
$lp_site_heading = $lp_location_title ? ( rtrim( $lp_location_title, '.' ) . '.' ) : '';
$lp_show_meeting = $lp_location_id && (
	'' !== $lp_meeting_point
	|| '' !== $lp_transport_rail
	|| '' !== $lp_transport_bus
	|| ( '' !== $lp_lat && '' !== $lp_lon )
	|| '' !== $lp_meeting_foot
);

$lp_level_terms = get_the_terms( $lp_post_id, 'lp_level' );
$lp_level_name  = ( is_array( $lp_level_terms ) && $lp_level_terms ) ? $lp_level_terms[0]->name : '';

$lp_price       = lp_class_price_display( $lp_post_id );
$lp_raw         = lp_clasbpro_raw( $lp_post_id );
$lp_when_date   = '';
if ( $lp_raw && ! empty( $lp_raw['start_date'] ) ) {
	$lp_when_dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', (string) $lp_raw['start_date'] );
	$lp_when_date = $lp_when_dt ? $lp_when_dt->format( 'D j M' ) : '';
}
$lp_when        = implode( ' · ', array_filter( array( $lp_when_date, strtolower( lp_class_workshop_duration_label( $lp_post_id ) ) ) ) );
$lp_who         = function_exists( 'get_field' ) ? (string) get_field( 'acf_who', $lp_post_id ) : '';
$lp_show_book   = lp_class_one_off_is_upcoming( $lp_post_id );

$lp_coach_ids = lp_class_coach_ids( $lp_post_id );

$lp_expect = function_exists( 'get_field' ) ? get_field( 'acf_what_to_expect', $lp_post_id ) : null;
$lp_expect = is_array( $lp_expect ) ? $lp_expect : array();

$lp_gallery = function_exists( 'get_field' ) ? get_field( 'acf_gallery', $lp_post_id ) : array();
$lp_gallery = is_array( $lp_gallery ) ? array_values( array_filter( array_map( 'intval', $lp_gallery ) ) ) : array();

$lp_image_id  = lp_class_image_id( $lp_post_id );
$lp_video_url = function_exists( 'get_field' ) ? (string) get_field( 'video_url', $lp_post_id ) : '';
$lp_video_id  = lp_youtube_id_from_url( $lp_video_url );
$lp_video_dlg = 'class-video-' . $lp_post_id;

$lp_this_date = '';
if ( $lp_raw && ! empty( $lp_raw['start_date'] ) ) {
	$lp_dt        = DateTimeImmutable::createFromFormat( 'Y-m-d', (string) $lp_raw['start_date'] );
	$lp_this_date = $lp_dt
		? trim( $lp_dt->format( 'D j M' ) . ' · ' . strtolower( lp_class_workshop_duration_label( $lp_post_id ) ) )
		: '';
}

$lp_book = lp_class_book_button_args(
	$lp_post_id,
	(string) ( $lp_raw['start_date'] ?? '' ),
	'BOOK THIS DATE',
	'band'
);

$lp_upcoming = lp_class_upcoming_sessions( $lp_post_id, 1 );
$lp_next     = $lp_upcoming[0] ?? null;

$lp_aside_rows = array();
if ( '' !== $lp_this_date ) {
	$lp_aside_rows[] = array(
		'label' => 'THIS DATE',
		'value' => $lp_this_date,
	);
}
if ( '' !== $lp_location_title ) {
	$lp_aside_rows[] = array(
		'label' => 'SITE',
		'value' => $lp_location_title,
	);
}
if ( '' !== $lp_level_name ) {
	$lp_aside_rows[] = array(
		'label' => 'LEVEL',
		'value' => $lp_level_name,
	);
}
if ( '' !== $lp_price ) {
	$lp_aside_rows[] = array(
		'label' => 'PRICE',
		'value' => $lp_price,
	);
}

$lp_facts = array();
if ( '' !== $lp_when ) {
	$lp_facts[] = array(
		'icon'  => 'icon-clock',
		'label' => 'WHEN',
		'value' => $lp_when,
	);
}
if ( '' !== $lp_location_title ) {
	$lp_facts[] = array(
		'icon'  => 'icon-map-pin',
		'label' => 'SITE',
		'value' => $lp_location_title,
	);
}
if ( '' !== $lp_who ) {
	$lp_facts[] = array(
		'icon'  => 'icon-user',
		'label' => 'WHO',
		'value' => $lp_who,
	);
}
if ( '' !== $lp_level_name ) {
	$lp_facts[] = array(
		'icon'  => 'icon-academic-cap',
		'label' => 'LEVEL',
		'value' => $lp_level_name,
	);
}
if ( '' !== $lp_price ) {
	$lp_facts[] = array(
		'icon'  => 'icon-currency-pound',
		'label' => 'PRICE',
		'value' => $lp_price,
	);
}

$lp_caption_bits = array_filter(
	array(
		$lp_location_title,
		strtolower( lp_class_workshop_duration_label( $lp_post_id ) ),
	)
);
$lp_caption = implode( ' · ', $lp_caption_bits );

$lp_private = home_url( '/#private-coaching' );
foreach ( array( 'private-coaching', 'private-tuition' ) as $lp_slug ) {
	$lp_page = get_page_by_path( $lp_slug );
	if ( $lp_page instanceof WP_Post ) {
		$lp_private = (string) get_permalink( $lp_page );
		break;
	}
}

$lp_grid = $lp_show_book
	? 'px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-x-10 lg:gap-x-16 gap-y-16 items-start'
	: 'px-6 lg:px-16 py-scale-2xl grid grid-cols-1 gap-y-16 items-start';
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'CLASSES',
					'href'  => $lp_classes,
				),
				array(
					'label' => 'WORKSHOPS',
					'href'  => $lp_workshops,
				),
				array( 'label' => strtoupper( get_the_title( $lp_post_id ) ) ),
			),
			'action' => array(
				'label' => 'ALL WORKSHOPS ↗',
				'href'  => $lp_workshops,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => get_the_title( $lp_post_id ),
			'note'  => $lp_about ? lp_first_sentences( wp_strip_all_tags( $lp_about ), 2 ) : $lp_subtitle,
		)
	);
	?>

	<?php if ( $lp_facts ) : ?>
		<div class="w-full bg-neutral" data-component="class-detail-fact-rail">
			<div class="px-6 lg:px-16 py-scale-s grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-x-[40px] gap-y-6" data-mount="rail">
				<?php foreach ( $lp_facts as $lp_fact ) : ?>
					<?php lp_part( 'components/fact-row', array_merge( $lp_fact, array( 'surface' => 'board' ) ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="w-full bg-base-100" data-component="class-detail-body">
		<div class="<?php echo esc_attr( $lp_grid ); ?>">
			<div class="relative w-full bg-base-300 overflow-hidden order-2 lg:col-start-1 lg:row-start-1 lg:order-none">
				<?php if ( $lp_image_id ) : ?>
					<?php
					lp_part(
						'components/media-photo',
						array(
							'image_id' => $lp_image_id,
							'layout'   => 'plain',
							'size'     => 'lp_wide',
							'sizes'    => '(min-width: 1024px) 50vw, 100vw',
						)
					);
					?>
				<?php endif; ?>
				<?php if ( '' !== $lp_video_id ) : ?>
					<span class="absolute top-[16px] left-[16px]">
						<?php
						lp_part(
							'elements/button',
							array(
								'variant'          => 'primary',
								'label'            => 'WATCH THE FILM',
								'trailing_icon_id' => 'icon-play',
								'command'          => 'show-modal',
								'command_for'      => $lp_video_dlg,
								'data_attrs'       => array(
									'data-video-type' => 'youtube',
									'data-video-id'   => $lp_video_id,
									'data-autoplay'   => 'true',
								),
							)
						);
						?>
					</span>
				<?php endif; ?>
				<?php if ( '' !== $lp_caption ) : ?>
					<span class="absolute bottom-[16px] left-[16px]">
						<?php
						lp_part(
							'elements/badge',
							array(
								'variant' => 'category',
								'label'   => $lp_caption,
							)
						);
						?>
					</span>
				<?php endif; ?>
			</div>
			<?php if ( $lp_about ) : ?>
				<div class="flex flex-col gap-[22px] border-t border-base-content pt-[22px] order-3 lg:col-start-1 lg:order-none">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">ABOUT THIS WORKSHOP</span>
					<div class="m-0 font-label text-[15px] font-normal leading-[1.75] tracking-[0.1px] text-base-content/80"><?php echo wp_kses_post( $lp_about ); ?></div>
				</div>
			<?php endif; ?>
			<?php if ( $lp_expect ) : ?>
				<div class="flex flex-col border-t border-base-content pt-[22px] order-4 lg:col-start-1 lg:order-none">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">WHAT TO EXPECT</span>
					<ol class="flex flex-col m-0 p-0 list-none [&>li:last-child_[data-variant=expect]]:border-b-0">
						<?php foreach ( $lp_expect as $lp_i => $lp_step ) : ?>
							<li>
								<?php
								lp_part(
									'components/checklist-item',
									array(
										'text'  => (string) ( $lp_step['text'] ?? '' ),
										'index' => str_pad( (string) ( $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
			<?php if ( $lp_show_book ) : ?>
				<div class="w-full order-1 lg:col-start-2 lg:row-start-1 lg:row-span-3 lg:order-none flex flex-col gap-6">
					<?php
					lp_part(
						'components/aside-panel',
						array(
							'title'       => 'BOOK THIS WORKSHOP',
							'spots_left'  => $lp_next ? (string) ( $lp_next['spaces'] ?? '' ) : '',
							'rows'        => $lp_aside_rows,
							'cta_label'   => $lp_book['label'],
							'command'     => $lp_book['command'] ?? '',
							'command_for' => $lp_book['command_for'] ?? '',
							'data_attrs'  => $lp_book['data_attrs'] ?? array(),
							'note'        => 'Free cancellation up to 24 hours before the day. All kit provided.',
							'surface'     => 'page',
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $lp_show_meeting ) : ?>
		<div class="w-full bg-base-200" data-component="class-detail-meeting-point">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
				<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-[16px]">
					<div class="flex flex-col gap-[14px] min-w-0">
						<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">MEETING POINT</span>
						<?php if ( '' !== $lp_site_heading ) : ?>
							<h2 class="font-heading text-[36px] lg:text-[42px] font-bold leading-none tracking-[-1.6px] text-base-content m-0"><?php echo esc_html( $lp_site_heading ); ?></h2>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $lp_streetview ) : ?>
						<a href="<?php echo esc_url( $lp_streetview ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[11px] font-semibold uppercase tracking-[1px] text-accent shrink-0">STREETVIEW ↗</a>
					<?php endif; ?>
				</div>

				<div class="flex flex-col lg:flex-row gap-12 items-start">
					<div class="w-full lg:w-1/2 min-w-0 flex flex-col gap-[28px] border-t border-base-content pt-[22px]">
						<?php if ( '' !== $lp_meeting_kicker ) : ?>
							<div class="flex items-center gap-[8px]">
								<span class="text-base-content" aria-hidden="true"><?php lp_icon( 'icon-map-pin', 'w-[12px] h-[12px]' ); ?></span>
								<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65"><?php echo esc_html( $lp_meeting_kicker ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $lp_meeting_point ) : ?>
							<div class="flex flex-col gap-[10px]">
								<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">MEETING POINT</span>
								<p class="font-label text-[14px] font-normal leading-[1.7] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp_meeting_point ); ?></p>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $lp_transport_rail || '' !== $lp_transport_bus ) : ?>
							<div class="flex flex-col gap-[10px]">
								<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">TRANSPORT</span>
								<?php if ( '' !== $lp_transport_rail ) : ?>
									<p class="font-body text-[13px] font-medium leading-[1.6] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp_transport_rail ); ?></p>
								<?php endif; ?>
								<?php if ( '' !== $lp_transport_bus ) : ?>
									<p class="font-body text-[12px] font-normal leading-[1.6] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp_transport_bus ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<?php if ( '' !== $lp_meeting_foot || '' !== $lp_osm_maps ) : ?>
							<div class="flex flex-wrap items-center justify-between gap-[16px] border-t border-base-300 pt-[14px]">
								<?php if ( '' !== $lp_meeting_foot ) : ?>
									<span class="font-label text-[10px] font-medium uppercase tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_meeting_foot ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $lp_osm_maps ) : ?>
									<a href="<?php echo esc_url( $lp_osm_maps ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent">OPEN IN MAPS ↗</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( '' !== $lp_lat && '' !== $lp_lon && is_numeric( $lp_lat ) && is_numeric( $lp_lon ) ) : ?>
						<div class="w-full lg:w-1/2 flex flex-col gap-[12px]">
							<div
								class="relative isolate w-full h-[360px] bg-base-300 border border-base-300 overflow-hidden"
								data-component="class-detail-osm"
								data-lat="<?php echo esc_attr( $lp_lat ); ?>"
								data-lon="<?php echo esc_attr( $lp_lon ); ?>"
								data-name="<?php echo esc_attr( $lp_location_title ? $lp_location_title : 'Meeting point' ); ?>"
							>
								<div class="absolute inset-0 z-0 [&_.leaflet-container]:h-full [&_.leaflet-container]:w-full [&_.leaflet-container]:!z-0" data-mount="leaflet"></div>
								<div class="absolute inset-x-0 top-0 z-[500] flex items-center justify-between h-10 px-4 bg-base-100 border-b border-base-300 pointer-events-none">
									<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content">OPENSTREETMAP</span>
									<?php if ( '' !== $lp_coords_label ) : ?>
										<span class="font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_coords_label ); ?></span>
									<?php endif; ?>
								</div>
								<div class="absolute inset-x-0 bottom-0 z-[500] flex items-center justify-between h-11 px-4 bg-base-100 border-t border-base-300">
									<span class="font-label text-[9px] font-normal tracking-[0.7px] text-base-content/65">© OPENSTREETMAP CONTRIBUTORS</span>
									<?php if ( '' !== $lp_streetview ) : ?>
										<a href="<?php echo esc_url( $lp_streetview ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent pointer-events-auto">STREETVIEW ↗</a>
									<?php endif; ?>
								</div>
								<div class="hidden" data-meeting-pin aria-hidden="true">
									<?php
									lp_part(
										'components/map-pin',
										array(
											'name'     => $lp_location_title ? $lp_location_title : 'Meeting point',
											'variant'  => 'icon',
											'flagship' => true,
											'label'    => false,
										)
									);
									?>
								</div>
							</div>
							<p class="font-label text-[11px] font-normal leading-[1.5] tracking-[0.1px] text-base-content/65 m-0">Map centres on the meeting point.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $lp_gallery ) : ?>
		<section class="w-full bg-neutral" data-component="workshop-detail-gallery">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-9">
				<div>
					<div class="flex items-end justify-between gap-4 pb-[14px]">
						<div class="flex flex-col gap-2">
							<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-primary">FROM THE FLOOR</span>
							<h2 class="font-heading text-[32px] font-semibold tracking-[-0.8px] text-neutral-content m-0 [text-box:normal]">What the day looks like.</h2>
						</div>
						<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50">(<?php echo esc_html( str_pad( (string) count( $lp_gallery ), 2, '0', STR_PAD_LEFT ) ); ?>)</span>
					</div>
					<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
				</div>
				<div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
					<?php foreach ( $lp_gallery as $lp_still ) : ?>
						<?php
						lp_part(
							'elements/dialog-image',
							array(
								'image_id' => (int) $lp_still,
							)
						);
						?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $lp_video_id ) : ?>
		<section class="w-full bg-base-100" data-component="workshop-detail-film">
			<div class="px-6 lg:px-16 py-scale-2xl">
				<?php
					lp_part(
						'components/video-stage',
						array(
							'image_id'        => $lp_image_id,
							'status_label'    => 'NOW PLAYING · THE DAY',
							'quality_label'   => 'EN · HD',
							'badge_label'     => strtoupper( get_the_title( $lp_post_id ) ) . ' · THE DAY',
							'duration_label'  => lp_class_workshop_duration_label( $lp_post_id ),
							'title'           => get_the_title( $lp_post_id ),
							'stage_meta'      => implode(
								' · ',
								array_filter(
									array(
										'WORKSHOP',
										$lp_location_title ? strtoupper( $lp_location_title ) : '',
										$lp_level_name ? strtoupper( $lp_level_name ) : '',
									)
								)
							),
							'play_aria_label' => 'Play: ' . get_the_title( $lp_post_id ),
							'command'         => 'show-modal',
							'command_for'     => $lp_video_dlg,
							'data_attrs'      => array(
								'data-video-type' => 'youtube',
								'data-video-id'   => $lp_video_id,
								'data-autoplay'   => 'true',
							),
						)
					);
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $lp_coach_ids ) : ?>
		<section class="w-full bg-accent" data-component="workshop-detail-coaches">
			<div class="px-6 lg:px-16 py-scale-2xl">
				<div class="flex flex-col gap-10 border-t border-accent-content pt-[22px]">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-accent-content">THE COACHES</span>
					<div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
						<?php foreach ( $lp_coach_ids as $lp_coach_id ) : ?>
							<?php
							lp_part(
								'components/byline',
								array(
									'name'      => get_the_title( $lp_coach_id ),
									'secondary' => (string) get_field( 'role', $lp_coach_id ),
									'bio'       => lp_first_sentences( (string) get_field( 'bio', $lp_coach_id ), 2 ),
									'size'      => 'lg',
									'surface'   => 'accent',
									'photo_id'  => has_post_thumbnail( $lp_coach_id ) ? (int) get_post_thumbnail_id( $lp_coach_id ) : 0,
								)
							);
							?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← ALL WORKSHOPS',
				'label'   => 'The dates still to come',
				'href'    => $lp_workshops,
			),
			'next' => array(
				'keyword' => 'PRIVATE COACHING →',
				'label'   => 'One coach, any of six sites',
				'href'    => $lp_private,
			),
		)
	);

	if ( '' !== $lp_video_id ) {
		lp_part(
			'elements/dialog-video',
			array(
				'dialog_id'  => $lp_video_dlg,
				'video_type' => 'youtube',
			)
		);
	}
	?>
</main>
