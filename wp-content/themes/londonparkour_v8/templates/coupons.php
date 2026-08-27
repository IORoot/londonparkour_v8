<?php
/**
 * Template Name: Coupons
 *
 * Coupon sales landing (pen `YAPJ8` "Coupons (Concourse)" under `SXvw6`).
 * Ported from src/stories/Pages/Coupons/Coupons.js.
 *
 * Sells three pack tiers — DROP-IN (1 class), 5-PACK, 10-PACK — via a
 * dark hero fare board, a comparison table, three full-bleed image sections
 * and a coupon-details row. Each BUY button opens the shared coupon
 * purchase drawer (`lp-booking-drawer`) via `lp_pack_buy_button_args()`.
 *
 * Pack IDs are set in the ACF group `group_lp_coupons` (drop_in_pack,
 * five_pack, ten_pack) on the Coupons page in the WordPress admin. When a
 * pack field is empty the button falls back to an anchor href of `#`.
 *
 * Section images (drop_in_image, five_pack_image, ten_pack_image) are ACF
 * image fields (return_format: id). Unset images are skipped gracefully.
 *
 * Landmark contract: nav and footer outside the one <main>, H1 inside it.
 *
 * Seeded at slug `coupons` (`/coupons/`).
 *
 * DELIBERATE DEPARTURES from the Storybook source:
 * - Images go through parts/components/media-photo.php (responsive srcset).
 * - The gift-card upsell band uses the existing `blocks/pricing/pricing.php`
 *   gift-card SVG helper rather than re-porting GiftCardUpsell, which is out
 *   of scope for the current port plan. Reported — not promoted.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_page_id = (int) get_the_ID();

/* ── Pack IDs (from ACF) ─────────────────────────────────────────────
 * Each field holds a clasbpro_pack post ID. Absent → 0 → no drawer wiring.
 */
$lp_drop_in_id  = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'drop_in_pack', $lp_page_id ) ) : 0;
$lp_five_pack_id = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'five_pack', $lp_page_id ) ) : 0;
$lp_ten_pack_id  = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'ten_pack', $lp_page_id ) ) : 0;

/* ── Section images (from ACF) ───────────────────────────────────────*/
$lp_drop_in_img  = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'drop_in_image', $lp_page_id ) ) : 0;
$lp_five_img     = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'five_pack_image', $lp_page_id ) ) : 0;
$lp_ten_img      = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'ten_pack_image', $lp_page_id ) ) : 0;

/* ── Adjacent page URLs ──────────────────────────────────────────────*/
$lp_classes  = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' );
$lp_coaching = home_url( '/private-coaching/' );
foreach ( array( 'private-coaching', 'private-tuition' ) as $lp_slug ) {
	$lp_page_obj = get_page_by_path( $lp_slug );
	if ( $lp_page_obj instanceof WP_Post ) {
		$lp_coaching = (string) get_permalink( $lp_page_obj );
		break;
	}
}

/* ── Shared buy-button closure ───────────────────────────────────────
 * Returns the args array for elements/button.php. When the pack post
 * exists the drawer is wired; otherwise falls back to a plain link
 * anchored to #buy so the page is still navigable without the plugin.
 */
$lp_buy_btn = static function ( int $pack_id, string $label, string $variant ) use ( $lp_page_id ): void {
	if ( $pack_id > 0 && function_exists( 'lp_pack_buy_button_args' ) ) {
		lp_part(
			'elements/button',
			array_merge(
				lp_pack_buy_button_args( $pack_id, $label, $variant ),
				array( 'trailing_icon_id' => 'icon-arrow-right' )
			)
		);
		return;
	}
	lp_part(
		'elements/button',
		array(
			'variant'          => $variant,
			'label'            => $label,
			'href'             => '#buy',
			'trailing_icon_id' => 'icon-arrow-right',
		)
	);
};

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array( 'label' => 'HOME', 'href' => home_url( '/' ) ),
				array( 'label' => 'CLASSES', 'href' => $lp_classes ),
				array( 'label' => 'COUPONS' ),
			),
			'action' => array(
				'label' => 'TIMETABLE ↗',
				'href'  => $lp_classes,
			),
		)
	);
	?>

	<!-- Hero ──────────────────────────────────────────────────────── -->
	<section class="w-full bg-neutral border-b border-neutral-content/10" data-component="coupons-hero">
		<div class="flex flex-col lg:flex-row lg:items-stretch">
			<div class="w-full lg:w-1/2 flex flex-col gap-6 px-6 py-scale-2xl lg:px-16 lg:py-[72px]">
				<span class="font-label text-[11px] font-semibold tracking-[1.5px] uppercase text-neutral-content/50">08 — COUPON SALE</span>
				<h1 class="font-display text-step-5 font-bold leading-[0.92] tracking-[-2.4px] text-neutral-content m-0">No contract.<br />Ever.</h1>
				<div class="flex-1"></div>
				<p class="font-body text-[13px] font-normal leading-[1.65] tracking-[0.1px] text-neutral-content/50 m-0">Buy a class, a pack of five, or ten. Use them at any site — Vauxhall, Old Street or Kilburn Park. No membership. No lock-in.</p>
			</div>
			<div class="w-full lg:w-1/2 flex flex-col justify-end px-6 py-scale-xl lg:px-16 lg:pb-[20px] border-t lg:border-t-0 lg:border-l border-neutral-content/10">
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50">FARES</span>
				<ul class="list-none m-0 p-0 mt-3" role="list">
					<?php
					$lp_fares = array(
						array( 'name' => 'DROP-IN',  'price' => '£15',  'unit' => 'per session',    'hot' => false ),
						array( 'name' => '5-PACK',   'price' => '£65',  'unit' => 'for 5 classes',  'hot' => true ),
						array( 'name' => '10-PACK',  'price' => '£120', 'unit' => 'for 10 classes', 'hot' => false ),
					);
					foreach ( $lp_fares as $lp_fare ) :
						$lp_name_cls  = $lp_fare['hot'] ? 'text-primary' : 'text-neutral-content';
						$lp_price_cls = $lp_fare['hot'] ? 'text-primary' : 'text-neutral-content';
					?>
					<li class="flex items-center justify-between py-[18px] border-t border-neutral-content/10">
						<div class="flex items-center gap-[14px]">
							<span class="font-label text-[12px] font-semibold tracking-[1.2px] uppercase <?php echo esc_attr( $lp_name_cls ); ?>"><?php echo esc_html( (string) $lp_fare['name'] ); ?></span>
							<span class="font-label text-[11px] font-normal tracking-[0.3px] text-neutral-content/50"><?php echo esc_html( (string) $lp_fare['unit'] ); ?></span>
						</div>
						<span class="font-heading text-[36px] font-bold leading-[0.9] tracking-[-1.5px] <?php echo esc_attr( $lp_price_cls ); ?>"><?php echo esc_html( (string) $lp_fare['price'] ); ?></span>
					</li>
					<?php endforeach; ?>
				</ul>
				<div class="flex items-center gap-2 py-[14px] border-t border-neutral-content/10">
					<div class="w-[2px] h-[28px] bg-primary shrink-0" aria-hidden="true"></div>
					<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50">PRICES HELD UNTIL 1 APRIL 2027</span>
				</div>
			</div>
		</div>
	</section>

	<!-- Summary comparison table ───────────────────────────────────── -->
	<section class="w-full bg-base-200 border-b border-base-300/60" data-component="coupons-table">
		<div class="px-6 lg:px-24 pt-[80px] pb-12 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
			<div class="flex flex-col gap-4">
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">COUPON SALE</span>
				<h2 class="font-heading text-[43px] font-semibold leading-[1.05] tracking-[-1.6px] text-base-content m-0">No contract.<br />Three ways to pay.</h2>
			</div>
			<p class="font-label text-[11px] font-normal leading-[1.6] tracking-[0.2px] text-base-content/65 lg:text-right lg:max-w-[280px] m-0">Coupons work at Vauxhall, Old Street and Kilburn Park. Buy once, book when you want.</p>
		</div>
		<div class="h-10" aria-hidden="true"></div>
		<div class="overflow-x-auto px-6 lg:px-24 pb-[80px]">
			<?php
			$lp_table_tiers = array(
				array(
					'id'        => 'drop-in',
					'label'     => 'DROP-IN',
					'badge'     => '',
					'price'     => '£15',
					'unit'      => 'per session',
					'desc'      => 'Turn up when it suits. One session, paid at the door — no pack required.',
					'ppc'       => '£15.00',
					'sessions'  => 'One',
					'saving'    => '—',
					'cta'       => 'BUY 1 COUPON',
					'highlight' => false,
					'pack_id'   => $lp_drop_in_id,
					'variant'   => 'ghost',
				),
				array(
					'id'        => '5-pack',
					'label'     => '5-PACK',
					'badge'     => 'MOST POPULAR',
					'price'     => '£65',
					'unit'      => 'for 5 classes',
					'desc'      => 'Five classes, bought once. Use them when you want — no expiry.',
					'ppc'       => '£13.00',
					'sessions'  => '5 classes',
					'saving'    => '13% vs drop-in',
					'cta'       => 'BUY 5 CLASSES',
					'highlight' => true,
					'pack_id'   => $lp_five_pack_id,
					'variant'   => 'primary',
				),
				array(
					'id'        => '10-pack',
					'label'     => '10-PACK',
					'badge'     => 'BEST VALUE',
					'price'     => '£120',
					'unit'      => 'for 10 classes',
					'desc'      => 'Ten classes at the best rate we offer. Buy once, book when you want.',
					'ppc'       => '£12.00',
					'sessions'  => '10 classes',
					'saving'    => '20% vs drop-in',
					'cta'       => 'BUY 10 CLASSES',
					'highlight' => false,
					'pack_id'   => $lp_ten_pack_id,
					'variant'   => 'ghost',
				),
			);
			$lp_tier_count  = count( $lp_table_tiers );
			$lp_board_style = sprintf(
				'--pricing-tiers: %d; grid-template-rows: 3px minmax(193px, auto) 38px 38px 38px 80px',
				$lp_tier_count
			);
			?>
			<div
				class="grid min-w-[900px] sm:min-w-0 border border-base-300/60 grid-cols-[repeat(var(--pricing-tiers),minmax(280px,1fr))] sm:grid-cols-[196px_repeat(var(--pricing-tiers),minmax(0,1fr))]"
				style="<?php echo esc_attr( $lp_board_style ); ?>"
				data-slot="coupons-board"
			>
				<div class="hidden sm:grid sm:row-span-full sm:grid-rows-subgrid border-r border-base-300/60" data-slot="coupons-rail">
					<div class="bg-base-300" aria-hidden="true"></div>
					<div class="flex flex-col pt-6 pb-5 pr-7">
						<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">COUPON SALE</span>
						<div class="flex-1"></div>
						<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">WHAT YOU GET</span>
					</div>
					<div class="pr-7 border-t border-base-300/60 h-[38px] flex items-center">
						<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">PRICE PER CLASS</span>
					</div>
					<div class="pr-7 border-t border-base-300/60 h-[38px] flex items-center">
						<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">SESSIONS</span>
					</div>
					<div class="pr-7 border-t border-base-300/60 h-[38px] flex items-center">
						<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">SAVING</span>
					</div>
					<div class="pr-7 border-t border-base-300/60 h-[80px] flex items-center">
						<span class="font-label text-[10px] font-normal tracking-[0.9px] leading-[1.6] uppercase text-base-content/65">PRICES HELD UNTIL 1 APRIL 2027</span>
					</div>
				</div>
				<?php foreach ( $lp_table_tiers as $lp_tier ) :
					$lp_bar  = $lp_tier['highlight'] ? 'bg-primary' : 'bg-base-300/60';
					$lp_wash = $lp_tier['highlight'] ? 'bg-primary/8' : '';
					$lp_val  = $lp_tier['highlight']
						? 'font-label text-[11px] font-normal tracking-[0.2px] text-base-content'
						: 'font-label text-[11px] font-normal tracking-[0.2px] text-base-content/65';
				?>
				<div class="<?php echo lp_classes( 'row-span-full grid grid-rows-subgrid w-[280px] sm:w-auto border-l border-base-300/60', $lp_wash ); ?>" data-component="coupon-tier" data-tier="<?php echo esc_attr( (string) $lp_tier['id'] ); ?>">
					<div class="<?php echo esc_attr( $lp_bar ); ?> h-[3px]" aria-hidden="true"></div>
					<div class="flex flex-col pt-[20px] pb-[16px] px-[28px] min-h-[193px] min-w-0">
						<div class="flex items-center gap-[10px] flex-wrap">
							<span class="font-label text-[11px] font-semibold tracking-[1.2px] uppercase text-base-content"><?php echo esc_html( (string) $lp_tier['label'] ); ?></span>
							<?php if ( '' !== $lp_tier['badge'] ) : ?>
								<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-accent">— <?php echo esc_html( (string) $lp_tier['badge'] ); ?></span>
							<?php endif; ?>
						</div>
						<div class="h-4" aria-hidden="true"></div>
						<div class="flex items-end gap-2 flex-wrap">
							<span class="font-heading text-[57px] font-bold leading-[0.9] tracking-[-2.6px] text-base-content"><?php echo esc_html( (string) $lp_tier['price'] ); ?></span>
							<span class="font-label text-[11px] font-normal tracking-[0.3px] text-base-content/65 pb-[6px]"><?php echo esc_html( (string) $lp_tier['unit'] ); ?></span>
						</div>
						<div class="h-2.5" aria-hidden="true"></div>
						<p class="font-label text-[11px] font-normal leading-[1.6] tracking-[0.2px] text-base-content/65 m-0"><?php echo esc_html( (string) $lp_tier['desc'] ); ?></p>
					</div>
					<div class="flex items-center gap-[7px] px-[28px] border-t border-base-300/60 h-[38px] min-w-0">
						<span class="font-heading text-[17px] font-semibold tracking-[-0.3px] text-base-content"><?php echo esc_html( (string) $lp_tier['ppc'] ); ?></span>
						<span class="font-label text-[11px] font-normal tracking-[0.2px] text-base-content/65">a class</span>
					</div>
					<div class="flex items-center px-[28px] border-t border-base-300/60 h-[38px] min-w-0">
						<span class="<?php echo esc_attr( $lp_val ); ?>"><?php echo esc_html( (string) $lp_tier['sessions'] ); ?></span>
					</div>
					<div class="flex items-center px-[28px] border-t border-base-300/60 h-[38px] min-w-0">
						<span class="<?php echo esc_attr( '—' !== $lp_tier['saving'] ? 'font-label text-[11px] font-normal tracking-[0.2px] text-base-content' : 'font-label text-[11px] font-normal tracking-[0.2px] text-base-content/65' ); ?>"><?php echo esc_html( (string) $lp_tier['saving'] ); ?></span>
					</div>
					<div class="flex items-center px-[28px] border-t border-base-300/60 h-[80px] min-w-0">
						<?php $lp_buy_btn( (int) $lp_tier['pack_id'], (string) $lp_tier['cta'], (string) $lp_tier['variant'] ); ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Gap ────────────────────────────────────────────────────────── -->
	<div class="h-2 w-full bg-base-100" aria-hidden="true"></div>

	<!-- DROP-IN section ────────────────────────────────────────────── -->
	<section class="w-full" data-component="coupons-drop-in">
		<div class="flex flex-col lg:flex-row lg:min-h-[660px]">
			<div class="w-full lg:w-[576px] shrink-0 flex flex-col px-6 py-scale-2xl lg:px-[80px] lg:py-[72px] bg-neutral">
				<div class="flex items-center justify-between">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content">01</span>
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content">DROP-IN</span>
				</div>
				<div class="h-12" aria-hidden="true"></div>
				<span class="font-heading text-[96px] font-bold leading-[0.9] tracking-[-4px] text-neutral-content">£15</span>
				<span class="font-label text-[13px] font-normal tracking-[0.3px] text-neutral-content/50">per session</span>
				<div class="flex-1 min-h-8"></div>
				<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
				<div class="h-6" aria-hidden="true"></div>
				<p class="font-label text-[13px] font-normal leading-[1.65] tracking-[0.1px] text-neutral-content/50 m-0">Turn up when it suits. One session at the door — no pack required.</p>
				<div class="h-8" aria-hidden="true"></div>
				<?php $lp_buy_btn( $lp_drop_in_id, 'BUY 1 COUPON', 'inverse' ); ?>
			</div>
			<div class="relative flex-1 min-h-[300px] overflow-hidden bg-neutral">
				<?php if ( $lp_drop_in_img ) : ?>
					<?php
					lp_part(
						'components/media-photo',
						array(
							'image_id' => $lp_drop_in_img,
							'layout'   => 'fill',
							'size'     => 'lp_wide',
							'sizes'    => '(min-width: 1024px) calc(100vw - 576px), 100vw',
							'class'    => 'absolute inset-0 h-full w-full object-cover',
							'loading'  => 'lazy',
						)
					);
					?>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- Gap ────────────────────────────────────────────────────────── -->
	<div class="h-2 w-full bg-base-100" aria-hidden="true"></div>

	<!-- 5-PACK section ─────────────────────────────────────────────── -->
	<section class="w-full relative min-h-[700px] overflow-hidden bg-neutral flex flex-col justify-end" data-component="coupons-five-pack">
		<?php if ( $lp_five_img ) : ?>
			<?php
			lp_part(
				'components/media-photo',
				array(
					'image_id' => $lp_five_img,
					'layout'   => 'fill',
					'size'     => 'lp_wide_lg',
					'sizes'    => '100vw',
					'class'    => 'absolute inset-0 h-full w-full object-cover',
					'loading'  => 'lazy',
				)
			);
			?>
		<?php endif; ?>
		<div class="absolute inset-0 bg-gradient-to-t from-neutral/90 via-neutral/70 to-transparent" aria-hidden="true"></div>
		<div class="relative z-10 px-6 py-[80px] lg:px-24 flex flex-col gap-8">
			<div class="flex items-center gap-3">
				<span class="w-2 h-2 bg-primary shrink-0" aria-hidden="true"></span>
				<span class="font-label text-[11px] font-semibold tracking-[1.2px] uppercase text-primary">MOST POPULAR — 5-PACK</span>
			</div>
			<div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
				<div class="flex flex-col gap-4">
					<span class="font-heading text-[96px] font-bold leading-[0.9] tracking-[-4px] text-neutral-content">£65</span>
					<div class="flex items-center gap-2.5 flex-wrap">
						<span class="font-heading text-[20px] font-semibold tracking-[-0.4px] text-primary">£13.00 a class</span>
						<span class="font-label text-[13px] font-normal tracking-[0.1px] text-neutral-content/50">— saves 13% vs drop-in</span>
					</div>
				</div>
				<div class="flex flex-col gap-6 lg:items-end lg:max-w-[420px]">
					<p class="font-label text-[13px] font-normal leading-[1.65] tracking-[0.1px] text-neutral-content m-0 lg:text-right">Five classes, bought once. Use them when you want — any site, any coach. No membership, no expiry pressure.</p>
					<?php $lp_buy_btn( $lp_five_pack_id, 'BUY 5 CLASSES', 'primary' ); ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Gap ────────────────────────────────────────────────────────── -->
	<div class="h-2 w-full bg-base-100" aria-hidden="true"></div>

	<!-- 10-PACK section ────────────────────────────────────────────── -->
	<section class="w-full" data-component="coupons-ten-pack">
		<div class="flex flex-col lg:flex-row lg:min-h-[660px]">
			<div class="relative flex-1 min-h-[300px] overflow-hidden bg-neutral order-last lg:order-first">
				<?php if ( $lp_ten_img ) : ?>
					<?php
					lp_part(
						'components/media-photo',
						array(
							'image_id' => $lp_ten_img,
							'layout'   => 'fill',
							'size'     => 'lp_wide',
							'sizes'    => '(min-width: 1024px) calc(100vw - 580px), 100vw',
							'class'    => 'absolute inset-0 h-full w-full object-cover',
							'loading'  => 'lazy',
						)
					);
					?>
				<?php endif; ?>
			</div>
			<div class="w-full lg:w-[580px] shrink-0 flex flex-col px-6 py-scale-2xl lg:px-[80px] lg:py-[72px] bg-neutral lg:border-l lg:border-neutral-content/20 order-first lg:order-last">
				<div class="flex items-center justify-between">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content">03</span>
					<span class="inline-flex items-center px-2.5 py-[5px] bg-primary">
						<span class="font-label text-[10px] font-bold tracking-[1.1px] uppercase text-primary-content">BEST VALUE</span>
					</span>
				</div>
				<div class="h-12" aria-hidden="true"></div>
				<span class="font-heading text-[96px] font-bold leading-[0.9] tracking-[-4px] text-neutral-content">£120</span>
				<span class="font-label text-[13px] font-normal tracking-[0.3px] text-neutral-content/50">for 10 classes</span>
				<div class="h-5" aria-hidden="true"></div>
				<div class="flex items-start gap-8">
					<div class="flex flex-col gap-1">
						<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-neutral-content/50">PER CLASS</span>
						<span class="font-heading text-[22px] font-semibold tracking-[-0.5px] text-neutral-content">£12.00</span>
					</div>
					<div class="flex flex-col gap-1">
						<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-neutral-content/50">YOU SAVE</span>
						<span class="font-heading text-[22px] font-semibold tracking-[-0.5px] text-neutral-content">20%</span>
					</div>
				</div>
				<div class="flex-1 min-h-8"></div>
				<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
				<div class="h-6" aria-hidden="true"></div>
				<p class="font-label text-[13px] font-normal leading-[1.65] tracking-[0.1px] text-neutral-content/50 m-0">Ten classes at the best rate we offer. Buy once, book when you want — no membership required.</p>
				<div class="h-8" aria-hidden="true"></div>
				<?php $lp_buy_btn( $lp_ten_pack_id, 'BUY 10 CLASSES', 'primary' ); ?>
			</div>
		</div>
	</section>

	<!-- Coupon details ─────────────────────────────────────────────── -->
	<section class="w-full bg-base-100 border-b border-base-300" data-component="coupons-details">
		<div class="px-6 lg:px-16 pt-[80px] pb-[48px] flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
			<div class="flex flex-col gap-4">
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65">TERMS + HOW IT WORKS</span>
				<h2 class="font-heading text-step-3 font-semibold tracking-[-1px] text-base-content m-0">Everything you need to know.</h2>
			</div>
			<p class="font-body text-[11px] leading-[1.6] tracking-[0.2px] text-base-content/65 lg:text-right lg:max-w-[300px] m-0">Keep this to hand before you buy. Most questions have a straightforward answer.</p>
		</div>

		<!-- Validity strip -->
		<div class="flex flex-col lg:flex-row border-t border-base-300">
			<?php
			$lp_validity = array(
				array( 'pack' => '1 COUPON', 'period' => '3 MONTHS' ),
				array( 'pack' => '5-PACK',   'period' => '6 MONTHS' ),
				array( 'pack' => '10-PACK',  'period' => '12 MONTHS' ),
			);
			foreach ( $lp_validity as $lp_vi => $lp_v ) :
				$lp_border = $lp_vi < 2 ? 'border-b lg:border-b-0 lg:border-r border-base-300' : '';
			?>
			<div class="flex-1 flex flex-col gap-2 py-6 px-6 lg:px-16 <?php echo esc_attr( $lp_border ); ?>">
				<div class="flex items-center gap-2">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content/65"><?php echo esc_html( (string) $lp_v['pack'] ); ?></span>
					<?php lp_icon( 'icon-arrow-right', 'w-3 h-3 text-base-content/40' ); ?>
				</div>
				<span class="font-heading text-[24px] font-semibold tracking-[-0.8px] text-base-content"><?php echo esc_html( (string) $lp_v['period'] ); ?></span>
				<span class="font-label text-[10px] font-normal tracking-[0.3px] text-base-content/65">from date of purchase</span>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Detail rows -->
		<ul class="list-none m-0 p-0 px-6 lg:px-16 pb-[80px]" role="list">
			<?php
			$lp_detail_rows = array(
				array(
					'label' => 'VALIDITY',
					'text'  => 'Each coupon pack has a validity window from the date of purchase: single coupons are valid for 3 months, 5-packs for 6 months, and 10-packs for 12 months. Unused coupons expire at the end of this period.',
				),
				array(
					'label' => 'YOUR CODE',
					'text'  => 'On payment you land on a confirmation page showing your coupon code. The same code is emailed to you automatically. If you do not receive it, contact us and we will send it manually.',
				),
				array(
					'label' => 'AUTO-APPLY',
					'text'  => 'The site remembers your code. Once entered, it will be applied automatically at checkout on your next visit — you do not need to re-enter it each time.',
				),
				array(
					'label' => 'REFUNDS',
					'text'  => 'Refunds are available only if no classes have been redeemed against the coupon. Partial refunds are not available once any class has been used.',
				),
				array(
					'label' => 'ELIGIBILITY',
					'text'  => 'Coupons are valid for standard classes only. They cannot be used against workshops, private 1:1 sessions, or any other service.',
				),
				array(
					'label' => 'PAYMENT',
					'text'  => 'All payments are processed securely through Stripe. You will receive a Stripe receipt by email in addition to the coupon confirmation.',
				),
				array(
					'label' => 'TRACKING',
					'text'  => 'There is no built-in tracking for remaining uses. Please keep your own count of how many classes you have redeemed against your coupon.',
				),
			);
			foreach ( $lp_detail_rows as $lp_row ) :
			?>
			<li class="flex flex-col sm:flex-row gap-4 sm:gap-12 py-7 border-t border-base-300">
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/65 sm:w-[200px] shrink-0"><?php echo esc_html( (string) $lp_row['label'] ); ?></span>
				<p class="font-body text-[13px] font-normal leading-[1.65] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( (string) $lp_row['text'] ); ?></p>
			</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<!-- Notice strip ───────────────────────────────────────────────── -->
	<section class="w-full bg-neutral py-7 px-6 lg:px-16" data-component="coupons-notice">
		<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
			<div class="flex items-center gap-3 flex-wrap">
				<?php lp_icon( 'icon-map-pin', 'w-[14px] h-[14px] text-neutral-content/50 shrink-0' ); ?>
				<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50">EVERY COUPON WORKS AT ALL THREE SITES</span>
				<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary">VAUXHALL · OLD STREET · KILBURN PARK</span>
			</div>
			<div class="flex items-center gap-5">
				<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50">JUST TRAINERS — NO SPECIALIST KIT NEEDED</span>
				<span class="hidden sm:inline-block w-[3px] h-[3px] rounded-full bg-neutral-content/30" aria-hidden="true"></span>
				<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50">PRICES HELD UNTIL 1 APRIL 2027</span>
			</div>
		</div>
	</section>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CLASS TIMETABLE',
				'label'   => 'Book your next session',
				'href'    => $lp_classes,
			),
			'next' => array(
				'keyword' => 'PRIVATE COACHING →',
				'label'   => sprintf(
					'One coach, any of %s sites',
					function_exists( 'lp_sites_word' ) ? lp_sites_word() : 'three'
				),
				'href'    => $lp_coaching,
			),
		)
	);
	?>
</main>

<?php
get_footer();
