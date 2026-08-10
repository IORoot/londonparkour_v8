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

$lp_default_items = array(
	array(
		'question'      => 'Do I need any experience?',
		'answer'        => 'None at all. Beginners sessions assume you have never done this before — we start on the floor, at ground level, and nothing is compulsory.',
		'default_open'  => true,
	),
	array(
		'question' => 'How much is a first class?',
		'answer'   => "First class is £15 for 60 minutes. There's no contract and no membership. If your first session isn't for you, we refund it — nobody has asked yet.",
	),
	array(
		'question' => 'What should I bring?',
		'answer'   => "Just trainers. All kit is provided, every session is coach-led and capped at twelve, and it's free to cancel up to 12 hours before.",
	),
	array(
		'question' => 'Where exactly do you train?',
		'answer'   => 'Six sites across London — Vauxhall, Peckham Rye, Southbank, Stratford East, Hackney Marshes and Wembley Park. Every one is a ten-minute walk from a tube or overground station.',
	),
);

$lp_default_groups = array(
	array(
		'id'      => 'classes',
		'label'   => 'A — CLASSES',
		'entries' => '05 ENTRIES',
		'icon'    => 'icon-academic-cap',
		'items'   => array(
			array(
				'question' => 'Is class running?',
				'answer'   => "The classes page is the source of truth on whether a class is running. If it's not on the page, or a 'cancelled' banner is on the image, the class isn't running — so please check before you travel. We try to contact everyone who's paid and booked if there's a last-minute cancellation, but mistakes happen: the class page is always the first thing to change.",
			),
			array(
				'question' => "I've booked a class, where do I go?",
				'answer'   => 'See the details on the class map on the map page.',
			),
			array(
				'question' => "I'm going to be late, what should I do?",
				'answer'   => "Come anyway. Every session warms up for the first ten minutes, so slipping in late is normal — find the coach, say hello, and they'll fold you into whatever the group is doing. More than twenty minutes behind, message us so we know not to hold the spot.",
			),
			array(
				'question' => "Do you still run class when it's raining?",
				'answer'   => "Yes. Outdoor sessions run in rain — we change the surfaces we use and keep the grip work low. We only stand a session down for ice, lightning or high wind. If we do, the classes page changes first and everyone booked gets a message.",
			),
			array(
				'question' => 'Class was cancelled, what happens to my booking?',
				'answer'   => "Your credit goes straight back onto your account, usually within the hour, and it spends on any session at any of the six sites. If you'd rather have the money back, reply to the cancellation email and we'll refund the card you paid with.",
			),
		),
	),
	array(
		'id'      => 'private-sessions',
		'label'   => 'B — PRIVATE SESSIONS',
		'entries' => '03 ENTRIES',
		'icon'    => 'icon-user-group',
		'items'   => array(
			array(
				'question' => 'How much are private sessions?',
				'answer'   => 'Private tuition starts at £70 for one hour, one-to-one, with a Level 2 coach. Two people sharing pay £45 each, and groups of three to six work out cheaper again. Sessions run at any of our six sites, or somewhere you choose inside London.',
			),
			array(
				'question' => 'Can I book a party?',
				'answer'   => "We do parties. Up to fifteen children with two coaches, ninety minutes, all kit and mats provided. Most are booked at Vauxhall or Wembley Park because they're indoors — tell us the date and the age range and we'll come back with a plan.",
			),
			array(
				'question' => 'What do we do in a private session?',
				'answer'   => 'Whatever you need. Most people come to work one specific thing — a vault they can\'t commit to, landing mechanics, or confidence at height. The coach builds the hour around that, and you leave with two or three things to drill on your own.',
			),
		),
	),
	array(
		'id'      => 'gift-cards',
		'label'   => 'C — GIFT CARDS',
		'entries' => '02 ENTRIES',
		'icon'    => 'icon-tag',
		'items'   => array(
			array(
				'question' => 'I want to book something for a present, how can I do that?',
				'answer'   => "A gift card is the easy answer: choose any amount and it arrives in your inbox as a code to print or forward. It spends on classes, courses and private sessions, and it doesn't expire.",
			),
			array(
				'question' => 'How do I use my gift card code?',
				'answer'   => "Enter the code at checkout when you book any session and the balance comes off the total. Anything left over stays on the code for next time — you don't have to spend it in one go.",
			),
		),
	),
);

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
	<div<?php echo $lp_id ? ' id="' . esc_attr( $lp_id ) . '"' : ''; ?> class="flex flex-col gap-[18px]">
		<?php
		lp_part(
			'components/meta-row',
			array(
				'left'    => $lp_label,
				'right'   => $lp_entries,
				'icon'    => $lp_icon,
				'surface' => 'page',
			)
		);
		?>
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
						'index'    => str_pad( (string) $lp_ordinal, 2, '0', STR_PAD_LEFT ),
						'question' => (string) ( $lp_item['question'] ?? '' ),
						'answer'   => (string) ( $lp_item['answer'] ?? '' ),
						'surface'  => 'page',
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
	?>
<section class="<?php echo lp_classes( 'w-full bg-base-200', $lp_spacing ); ?>" data-component="docs-faq-body"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
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

$lp_still         = is_array( $args['still_stuck'] ?? null ) ? $args['still_stuck'] : array();
$lp_still_title   = (string) ( $lp_still['title'] ?? 'STILL STUCK?' );
$lp_still_body    = (string) ( $lp_still['body'] ?? 'Send the form above, or email us direct. A coach reads every message — usually the same working day.' );
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
