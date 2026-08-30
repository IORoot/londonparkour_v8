<?php
/**
 * FAQ default copy — shared by the FAQ block and JSON-LD.
 *
 * These strings are the same arrays the FAQ block used to keep inline. Moving
 * them here does not change what the page renders; it lets schema emit the
 * questions a visitor actually sees when ACF rows are empty.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contact (flat) FAQ defaults.
 *
 * @return array<int, array<string, mixed>>
 */
function lp_faq_default_items(): array {
	return array(
		array(
			'question'     => 'Do I need any experience?',
			'answer'       => 'None at all. Beginners sessions assume you have never done this before — we start on the floor, at ground level, and nothing is compulsory.',
			'default_open' => true,
		),
		array(
			'question' => 'How much is a first class?',
			'answer'   => "First class is £15 for 90 minutes. There's no contract and no membership. If your first session isn't for you, we refund it — nobody has asked yet.",
		),
		array(
			'question' => 'What should I bring?',
			'answer'   => "Just trainers. Every session is coach-led, and it's free to cancel up to 12 hours before.",
		),
		array(
			'question' => 'Where exactly do you train?',
			'answer'   => function_exists( 'lp_where_we_train_answer' )
				? lp_where_we_train_answer()
				: 'Three sites across London — Vauxhall, Old Street and Kilburn Park. Every one is next to a tube or overground station.',
		),
	);
}

/**
 * Docs (grouped) FAQ defaults.
 *
 * @return array<int, array<string, mixed>>
 */
function lp_faq_default_groups(): array {
	return array(
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
					'answer'   => sprintf(
						"Your credit goes straight back onto your account, usually within the hour, and it spends on any session at any of the %s sites. If you'd rather have the money back, reply to the cancellation email and we'll refund the card you paid with.",
						function_exists( 'lp_sites_word' ) ? lp_sites_word() : 'three'
					),
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
					'answer'   => sprintf(
						'Private tuition starts at £65 for one hour, one-to-one, with a Level 2 coach. Two people sharing pay £40 each, and groups of three to six work out cheaper again. Sessions run at any of our %s sites, or somewhere you choose inside London.',
						function_exists( 'lp_sites_word' ) ? lp_sites_word() : 'three'
					),
				),
				array(
					'question' => 'Can I book a party?',
					'answer'   => "We do parties. Up to fifteen children with two coaches, ninety minutes. Most are booked at Vauxhall or Wembley Park because they're indoors — tell us the date and the age range and we'll come back with a plan.",
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
					'answer'   => 'Buy a coupon on the coupons page — drop-in, 5-pack or 10-pack. Pay with Stripe and the code arrives in your inbox straight away. Forward that email if it is a present. Coupons cover standard classes only, not workshops or private 1:1.',
				),
		array(
			'question' => 'How do I use my gift card code?',
			'answer'   => 'When you book a class, look for “Have a coupon?”, enter the code and press Apply. Choose “Use coupon (1 seat)” and finish the booking. One class comes off the pack each time.',
		),
			),
		),
	);
}

/**
 * Class-detail Common Questions — shown on every clasbpro class page.
 *
 * @return array<int, array{question:string,answer:string,default_open?:bool}>
 */
function lp_class_faq_items(): array {
	return array(
		array(
			'question'     => 'Beginners Welcome',
			'answer'       => "Our classes are scaled to you.\n\nWhich means that all abilities are catered for and that the challenges are specifically built for beginners to participate.",
			'default_open' => true,
		),
		array(
			'question' => 'Class Contents',
			'answer'   => "Classes will always be different. There will always be different environments, themes, techniques, attributes to focus on and ideas to approach.\n\nThe single continuous thread linking them all will be challenge. You will always be challenged, within your ability, but in a way that improves you as a human being. Which, at its core, is what parkour is all about.",
		),
		array(
			'question' => 'Weather',
			'answer'   => 'Parkour embraces the spirit of self-improvement through facing challenges. Many practitioners extend their training to different weather conditions, enhancing their adaptability in various environments. This is why classes always run in all weather conditions.',
		),
		array(
			'question' => 'Age limits',
			'answer'   => 'We have a number of different classes for different age-ranges. We have a 6 to 9 year olds class, older 10 to 14s, a 6 to 14s general youth class and 15+ adult classes. Please check the specific class details for more information.',
		),
		array(
			'question' => 'Clothing',
			'answer'   => "Anything that allows for free un-restricted movement. Tracksuit bottoms, tee-shirt and trainers is your best bet. Anything heavy-duty (boots) or restrictive (jeans) will make life a little harder for you, but it's totally your call.\n\nAll jewellery, watches, phones, wallets, etc… must be left out of the class. This is so it doesn't get caught or landed on. Safety is paramount in these classes.",
		),
		array(
			'question' => 'Water',
			'answer'   => "Even though the classes are always different, we will always be moving a lot. You'll sweat a lot and will need to keep hydrated, so it's wise to bring water along with you to class.",
		),
		array(
			'question' => 'Prejudice',
			'answer'   => 'In our classes, we promote an inclusive and welcoming environment, free from any form of prejudice. Everyone, regardless of their ethnicity, religious background, sexual orientation, or legal and political ideology, is encouraged to learn. Our commitment is to a discrimination-free space, and anyone found bringing prejudice into the class will be kindly asked to leave permanently.',
		),
		array(
			'question' => 'Lateness',
			'answer'   => 'As classes begin, coaches will turn off their phones and stow them in their bags, making them temporarily unreachable. If you anticipate being late, please message the coach via WhatsApp or Slack before the class starts. They will respond with the map location of the class meeting point. You can find the WhatsApp/Slack details in your confirmation booking email.',
		),
		array(
			'question' => 'Non-Competitive',
			'answer'   => 'Parkour is all about challenging yourself. It\'s about making you a better person and individual. Our classes push towards cooperation and collaboration and helping each other become better, rather than place a single person above others. Competition can motivate some people, but not all. We want to help all.',
		),
		array(
			'question' => 'Outdoors',
			'answer'   => 'Parkour is primarily an outdoor discipline. Incorporating indoor training can serve as a valuable supplement, allowing you to refine techniques, practice movements, and engage in strength training or conditioning. Starting your training outdoors and using indoor classes to complement your skills can be beneficial. However, it\'s encouraged to train outdoors whenever possible, as it contributes to a more well-rounded skill set.',
		),
		array(
			'question' => 'Secure',
			'answer'   => 'The new standard in safe, online payments. Stripe is the best software platform for running an internet business. LondonParkour uses the most secure payment gateway on the internet.',
		),
		array(
			'question' => 'Roots.',
			'answer'   => 'LondonParkour tries to remain as close to the original ethos of parkour. We teach practical and useful movement that makes you a more versatile human. To remain safe, we also focus on strength and resilience to build a better structure.',
		),
		array(
			'question' => 'Community.',
			'answer'   => 'With a large number of female practitioners, different ethnic minorities, pensioners and the disabled, parkour is a discipline that is open to anyone and everyone.',
		),
	);
}

/**
 * Youth-class notes shown above Common Questions for 6 to 9s and 10 to 14s.
 *
 * Not an accordion — a primary (yellow/lime) fill band so it cannot be
 * mistaken for FAQ copy.
 *
 * @return array<int, array{title:string, body:string[]}>
 */
function lp_class_youth_notes_items(): array {
	return array(
		array(
			'title' => 'Our ethos',
			'body'  => array(
				"In today's society, we believe that our youth need an honest, enjoyable, professional and disciplined way of learning how to move. We do not promote ‘organised play’. This is a fancy way of letting kids play and hope they pick something useful up themselves without the teacher doing anything other than supervision. And we don't promote healthy-and-safety cotton-wooling. Which is another way of saying that their parents/government/society can project their fears onto the kids.",
				'No. Our youth need to be able to learn about risk (Which is different from danger). They should understand personal fear and how to overcome it. Get the fundamental facts and experience of how to be healthy, mobile and self-reliable. They should be challenged and have an honest feedback loop to which they can adjust and course-correct.',
			),
		),
		array(
			'title' => 'Age limit',
			'body'  => array(
				'Currently, we take any children from the age of six to fourteen (6 to 14). At age fifteen they are able to join the adult classes.',
			),
		),
		array(
			'title' => 'Class bookings & location',
			'body'  => array(
				'See the classes page for details of when the youth class takes place and to make a booking. For locations, each class page has a map show the meeting points and transport links.',
			),
		),
		array(
			'title' => 'Water',
			'body'  => array(
				"Even though the classes are always different, we will always be moving a lot. You'll sweat a lot and will need to keep hydrated, so it's wise to bring water along with you to class.",
			),
		),
		array(
			'title' => 'Spectators',
			'body'  => array(
				"Sorry to say, parents and spectators will be asked to leave any class and will not be allowed to watch at any time. Why?!? I hear you ask. The reason is that we believe the child's focus should be firmly on what they are doing, rather than submitting to the temptation to impress their family or others. It's much better to have a learning environment without any risk of judgement or approval seeking. Therefore, a complete blanket-ban on all spectators of a class will be made by the coach.",
			),
		),
	);
}

/**
 * Render the youth-class notes band. No-ops on adult (15+) classes.
 *
 * @param int $class_id clasbpro_class post ID.
 */
function lp_render_class_youth_notes( int $class_id ): void {
	if ( ! function_exists( 'lp_class_is_youth' ) || ! lp_class_is_youth( $class_id ) ) {
		return;
	}

	$lp_items = lp_class_youth_notes_items();
	$lp_right = function_exists( 'lp_class_age_range_label' )
		? lp_class_age_range_label( $class_id )
		: '';
	?>
	<div class="w-full bg-primary" data-component="class-detail-youth-notes">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
			<?php
			lp_part(
				'components/section-head',
				array(
					'surface' => 'fill',
					'eyebrow' => 'YOUTH CLASS',
					'heading' => 'Youth class.',
					'note'    => $lp_right,
				)
			);
			?>
			<div class="flex flex-col gap-10">
				<?php foreach ( $lp_items as $lp_item ) : ?>
					<div class="flex flex-col gap-[18px]">
						<h3 class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-primary-content m-0"><?php echo esc_html( (string) ( $lp_item['title'] ?? '' ) ); ?></h3>
						<?php foreach ( (array) ( $lp_item['body'] ?? array() ) as $lp_para ) : ?>
							<p class="font-body text-[16px] font-normal leading-[1.55] text-primary-content/70 m-0"><?php echo esc_html( (string) $lp_para ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the class-detail Common Questions band.
 *
 * Chrome matches Contact FAQ (`contact-faq`) minus the "still stuck" aside:
 * MetaRow + hairline + FaqItem accordion on the page ground.
 */
function lp_render_class_faq(): void {
	$lp_items = lp_class_faq_items();
	$lp_right = sprintf( '%02d ENTRIES', count( $lp_items ) );
	?>
	<div class="w-full bg-base-100" data-component="class-detail-common-questions">
		<div class="px-6 lg:px-16 pt-scale-xl pb-scale-2xl">
			<div class="flex flex-col">
				<?php
				lp_part(
					'components/meta-row',
					array(
						'left'  => 'COMMON QUESTIONS',
						'right' => $lp_right,
					)
				);
				lp_part( 'elements/rule', array( 'tone' => 'hairline' ) );
				?>
				<div class="mt-[52px] flex flex-col divide-y divide-base-300">
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
			</div>
		</div>
	</div>
	<?php
}
