<?php
/**
 * CTA — "09 — START": the closing signal band with the next-session panel.
 *
 * Ported from src/stories/Blocks/CTA/CTA.js.
 *
 * The next-session panel is the chronologically next upcoming class
 * (`lp_class_next_session()`), not the first row of the source query. The
 * whole panel is one link to the Classes page, unless `session_book` is set,
 * in which case the panel opens the booking drawer for that session.
 *
 * The primary CTA is elements/button.php variant `inverse` — the dark quiet
 * button, correct on this bg-primary band. The alt CTA is a plain underline
 * link with its own type, not one of text-link.php's four variants, so it stays
 * here rather than being forced into that atom.
 *
 * @param string $args['kicker']
 * @param string $args['coordinates']
 * @param string $args['headline']
 * @param string $args['subhead']
 * @param array  $args['primary_action']
 * @param array  $args['alt_action']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_kicker      = lp_section_label( (string) ( $args['kicker'] ?? '09 — START' ), $args['_section_number'] ?? null );
$lp_coordinates = (string) ( $args['coordinates'] ?? 'N 51.5074° / W 0.1278°' );
$lp_headline    = (string) ( $args['headline'] ?? 'Walk through the door.' );
$lp_subhead     = (string) ( $args['subhead'] ?? 'Beginners sessions run Saturdays and Sundays in Old Street, Kilburn and Vauxhall. Fifteen pounds and no prior experience of any kind.' );

$lp_primary = lp_action( $args['primary_action'] ?? null );
$lp_alt     = lp_action( $args['alt_action'] ?? null );

$lp_session      = function_exists( 'lp_cta_session_panel' ) ? lp_cta_session_panel() : array();
$lp_session_href = (string) ( $lp_session['href'] ?? '' );
$lp_session_link = '' !== $lp_session_href;
$lp_session_button = false;
$lp_session_attrs  = '';
$lp_session_command = 'show-modal';
$lp_session_for     = 'lp-booking-drawer';

if ( ! empty( $args['session_book'] ) && function_exists( 'lp_class_book_button_args' ) ) {
	$lp_session_id = (int) ( $lp_session['class_id'] ?? 0 );
	if ( $lp_session_id > 0 ) {
		$lp_book = lp_class_book_button_args( $lp_session_id, (string) ( $lp_session['date'] ?? '' ), 'Book' );
		if ( ! empty( $lp_book['command'] ) ) {
			$lp_session_button = true;
			$lp_session_link   = false;
			$lp_session_command = (string) ( $lp_book['command'] ?? 'show-modal' );
			$lp_session_for     = (string) ( $lp_book['command_for'] ?? 'lp-booking-drawer' );
			foreach ( (array) ( $lp_book['data_attrs'] ?? array() ) as $lp_bk => $lp_bv ) {
				$lp_session_attrs .= sprintf( ' %s="%s"', esc_attr( (string) $lp_bk ), esc_attr( (string) $lp_bv ) );
			}
		}
	}
}

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_panel_base = 'group w-full lg:w-[450px] bg-neutral flex flex-col gap-[26px] p-[28px]';
$lp_panel_link = 'no-underline cursor-pointer transition-colors duration-150 hover:bg-neutral-content focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';
$lp_panel_button = 'text-left border-0';

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'bg-primary px-6 md:px-16 pt-[116px] pb-[120px]', $lp_spacing ); ?>" data-component="cta"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col gap-[60px]">
		<div class="flex items-center justify-between gap-4">
			<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary-content"><?php echo esc_html( $lp_kicker ); ?></span>
			<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary-content/70"><?php echo esc_html( $lp_coordinates ); ?></span>
		</div>

		<div class="grid lg:grid-cols-[1fr_auto] gap-[72px] items-start">
			<div class="flex flex-col gap-[40px] max-w-[790px]">
				<h2 class="font-heading text-step-5 font-semibold tracking-[-2px] leading-[0.95] text-primary-content"><?php echo esc_html( $lp_headline ); ?></h2>
				<?php if ( '' !== $lp_subhead ) : ?>
					<p class="font-body text-step--1 leading-[1.6] text-primary-content/70 max-w-[560px]"><?php echo esc_html( $lp_subhead ); ?></p>
				<?php endif; ?>
				<div class="flex flex-wrap items-center gap-[28px]">
					<?php
					if ( $lp_primary ) {
						$lp_primary_btn = array(
							'variant' => 'inverse',
							'label'   => $lp_primary['label'],
							'href'    => $lp_primary['href'],
						);
						if ( ! empty( $args['book'] ) && function_exists( 'lp_hero_first_class_book_args' ) ) {
							$lp_book = lp_hero_first_class_book_args( (string) $lp_primary['label'], 'inverse' );
							if ( is_array( $lp_book ) ) {
								$lp_primary_btn = $lp_book;
							}
						}
						lp_part( 'elements/button', $lp_primary_btn );
					}
					?>
					<?php if ( $lp_alt ) : ?>
						<a href="<?php echo esc_url( $lp_alt['href'] ); ?>" class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary-content hover:underline"><?php echo esc_html( $lp_alt['label'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $lp_session_button ) : ?>
			<button type="button" class="<?php echo lp_classes( $lp_panel_base, $lp_panel_link, $lp_panel_button ); ?>" data-component="cta-session-panel" command="<?php echo esc_attr( $lp_session_command ); ?>" commandfor="<?php echo esc_attr( $lp_session_for ); ?>"<?php echo $lp_session_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per attr above. ?>>
			<?php elseif ( $lp_session_link ) : ?>
			<a class="<?php echo lp_classes( $lp_panel_base, $lp_panel_link ); ?>" href="<?php echo esc_url( $lp_session_href ); ?>" data-component="cta-session-panel">
			<?php else : ?>
			<div class="<?php echo esc_attr( $lp_panel_base ); ?>" data-component="cta-session-panel">
			<?php endif; ?>
				<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( $lp_session['kicker'] ); ?></span>
				<p class="font-heading text-[36px] font-semibold tracking-[-1px] text-neutral-content group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( $lp_session['when'] ); ?></p>
				<p class="font-label text-[11px] font-normal tracking-[0.3px] text-neutral-content/60 group-hover:text-neutral/60 transition-colors duration-150"><?php echo esc_html( $lp_session['meta'] ); ?></p>
				<div class="h-px bg-neutral-content/15 group-hover:bg-neutral/15 transition-colors duration-150" aria-hidden="true"></div>
				<div class="flex items-center justify-between">
					<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-neutral-content/60 group-hover:text-neutral/60 transition-colors duration-150"><?php echo esc_html( $lp_session['foot_label'] ); ?></span>
					<span class="font-heading text-[20px] font-semibold text-primary group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( $lp_session['foot_value'] ); ?></span>
				</div>
			<?php
			if ( $lp_session_button ) {
				echo '</button>';
			} elseif ( $lp_session_link ) {
				echo '</a>';
			} else {
				echo '</div>';
			}
			?>
		</div>
	</div>
</section>
