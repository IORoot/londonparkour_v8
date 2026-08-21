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
					'answer'   => 'Private tuition starts at £65 for one hour, one-to-one, with a Level 2 coach. Two people sharing pay £40 each, and groups of three to six work out cheaper again. Sessions run at any of our six sites, or somewhere you choose inside London.',
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
}
