<?php
/**
 * Student waiver defaults — feeds templates/legal.php when the support post
 * is the waiver. Clause text is the live document at
 * https://londonparkour.com/waiver/, not rewritten.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Masthead title. Concourse legal pages end the H1 with a full stop.
 */
function lp_waiver_masthead_title(): string {
	return 'Student waiver.';
}

/**
 * Masthead note. The live page's "Practical. Personal. Professional." is the
 * old brand line, not Concourse; this is a factual standfirst for the clauses.
 */
function lp_waiver_masthead_note(): string {
	return 'The agreement you accept when you take part in a London Parkour activity. It covers risk, filming, minors, group bookings, and how you contact a coach.';
}

/**
 * @return list<array{label: string, value: string}>
 */
function lp_waiver_default_doc_facts(): array {
	return array();
}

/**
 * Same chrome as Terms. Destinations are not wired (href stays #).
 *
 * @return list<array{label: string}>
 */
function lp_waiver_default_doc_actions(): array {
	return array(
		array( 'label' => 'DOWNLOAD PDF ↓' ),
		array( 'label' => 'PRINT THIS PAGE' ),
	);
}

/**
 * Twenty clauses from the live waiver, in page order.
 *
 * @return list<array{n: string, title: string, paras: list<string>}>
 */
function lp_waiver_default_clauses(): array {
	return array(
		array(
			'n'     => '01',
			'title' => 'General',
			'paras' => array(
				'I agree that any claims of disability (permanent or otherwise), injury to oneself, liabilities from death or loss of any kind are waived when participating in a London Parkour activity. I agree to full liability of any kind and free any London Parkour staff member of any claims.',
				'I accept and understand that training parkour has risks and dangers associated with it. This includes any participation of any London Parkour classes or taking instruction from London Parkour coaches.',
				'I waive my rights to hold any London Parkour coach, instructor or teacher liable for any level of injury that may occur while engaging in a class or parkour activity under London Parkour.',
			),
		),
		array(
			'n'     => '02',
			'title' => 'Assumption of Risk',
			'paras' => array(
				'I acknowledge and understand that parkour is a physically demanding activity that involves risks, including but not limited to injury, permanent disability, and even death. By participating in any class, session, or event organized by London Parkour, I voluntarily assume full responsibility for all risks, whether known or unknown, associated with parkour training and agree to participate at my own risk.',
			),
		),
		array(
			'n'     => '03',
			'title' => 'Release of Liability',
			'paras' => array(
				'I agree to waive, release, and discharge London Parkour, its coaches, staff, agents, and representatives from any and all claims, liabilities, or causes of action that may arise from injury, illness, disability, death, or loss or damage to property as a result of participating in London Parkour activities. This release covers any such claims regardless of whether they result from my own actions, the actions of others, or the conditions of the premises or equipment.',
			),
		),
		array(
			'n'     => '04',
			'title' => 'Responsibility for Equipment Use',
			'paras' => array(
				'I accept full responsibility for any injuries or damages that may occur as a result of my use of any equipment, apparatus, structures, or facilities provided by London Parkour. I understand that any use of such equipment is at my own risk, and I am solely responsible for assessing its safety and suitability for my use.',
			),
		),
		array(
			'n'     => '05',
			'title' => 'Filming & Photography Consent',
			'paras' => array(
				'I consent to London Parkour capturing photos and videos of classes and events for marketing, promotional, and archival purposes. I understand that training in public spaces may involve incidental third-party photography or videography beyond London Parkour\'s control. For any pre-planned third-party filming, London Parkour will seek students\' consent before participation. I waive any right to inspect or approve the finished content or any material in which my likeness appears.',
			),
		),
		array(
			'n'     => '06',
			'title' => 'Medical Disclosure',
			'paras' => array(
				'I confirm that I am in good health and have no medical conditions or injuries that may be aggravated by or interfere with my ability to safely participate in parkour activities. I agree to inform London Parkour coaches immediately if I experience any injury, discomfort, or medical condition that may affect my participation. I am aware that I am responsible for any medical expenses that may arise from injuries incurred during parkour activities.',
			),
		),
		array(
			'n'     => '07',
			'title' => 'Emergency Consent',
			'paras' => array(
				'In the event of an emergency where I am unable to respond, I give permission for London Parkour staff to arrange emergency medical treatment as deemed necessary, at my own expense.',
			),
		),
		array(
			'n'     => '08',
			'title' => 'Acknowledgment of Understanding',
			'paras' => array(
				'By signing this waiver, I confirm that I have read and fully understand the terms outlined above. I agree that this waiver is binding upon me, my family, heirs, assigns, and personal representatives.',
			),
		),
		array(
			'n'     => '09',
			'title' => 'Indemnification Clause',
			'paras' => array(
				'I agree to indemnify, defend, and hold harmless London Parkour, its employees, coaches, agents, and affiliates from any and all claims, demands, or actions brought by any third party as a result of my actions or participation in parkour activities. This includes all related costs, including legal fees, that London Parkour may incur as a result of such claims.',
			),
		),
		array(
			'n'     => '10',
			'title' => 'Fitness to Participate',
			'paras' => array(
				'I affirm that I am physically fit, sufficiently prepared, and capable of engaging in parkour training. I have not been advised by a healthcare professional to avoid physically strenuous activities, and I am not aware of any health conditions that would prevent my safe participation.',
			),
		),
		array(
			'n'     => '11',
			'title' => 'Code of Conduct',
			'paras' => array(
				'I agree to adhere to all rules, regulations, and instructions provided by London Parkour staff. I understand that failure to comply may result in my removal from the activity without any refund or reimbursement. This includes, but is not limited to, respecting other participants, staff, equipment, and facilities.',
			),
		),
		array(
			'n'     => '12',
			'title' => 'No Guarantee of Supervision',
			'paras' => array(
				'I understand that certain portions of parkour training may occur in environments where direct supervision may be limited. I am responsible for exercising reasonable judgment and care in these instances, and I assume all associated risks.',
			),
		),
		array(
			'n'     => '13',
			'title' => 'Parental Consent for Minor Participation',
			'paras' => array(
				'If I am signing on behalf of a minor (under the age of 18), I certify that I am the minor\'s parent or legal guardian and have the authority to enter into this waiver on their behalf. I acknowledge and understand the risks associated with parkour training, as detailed above, and consent to the minor\'s participation in London Parkour activities.',
				'I agree to assume full responsibility for the minor\'s safety, including the risks of injury, illness, or loss, and release London Parkour, its coaches, staff, agents, and representatives from any liability related to the minor\'s participation. I also agree to ensure the minor’s compliance with all rules and regulations set forth by London Parkour.',
			),
		),
		array(
			'n'     => '14',
			'title' => 'Medical Authorization for Minors',
			'paras' => array(
				'In the event of a medical emergency involving the minor, I authorize London Parkour staff to seek and obtain medical treatment as deemed necessary at my own expense. I understand that I am responsible for any medical expenses that arise as a result of injuries sustained during parkour activities.',
			),
		),
		array(
			'n'     => '15',
			'title' => 'Photography and Video Consent for Minors',
			'paras' => array(
				'I grant permission for London Parkour to photograph and/or film the minor during classes or events for marketing and promotional purposes. I waive any right to inspect or approve the use of such media and release London Parkour from any claims arising from its use.',
			),
		),
		array(
			'n'     => '16',
			'title' => 'Acknowledgment of Understanding',
			'paras' => array(
				'By signing this waiver, I confirm that I have read and fully understand the terms outlined above. I agree that this waiver is binding upon myself, the minor, our family, heirs, assigns, and personal representatives.',
			),
		),
		array(
			'n'     => '17',
			'title' => 'Severability Clause',
			'paras' => array(
				'If any portion of this waiver is found to be invalid or unenforceable by a court of law, the remaining provisions will continue to be valid and enforceable to the fullest extent permitted by law.',
			),
		),
		array(
			'n'     => '18',
			'title' => 'Jurisdiction and Governing Law',
			'paras' => array(
				'This agreement shall be governed by the laws of England, and any disputes arising from this waiver or parkour activities shall be resolved exclusively within the courts of the England.',
			),
		),
		array(
			'n'     => '19',
			'title' => 'Group Booking Responsibility',
			'paras' => array(
				'If I am booking on behalf of a group, I confirm that I have the authority to sign on behalf of all participants and agree to share the contents of this waiver with each member of my group. By booking, I accept responsibility for ensuring that each participant has read, understands, and agrees to all terms outlined in this waiver. I understand that each group member participates in London Parkour activities under the same assumption of risk and waiver of liability as outlined above.',
			),
		),
		array(
			'n'     => '20',
			'title' => 'Neurodivergent Participation',
			'paras' => array(
				'London Parkour welcomes participants of all backgrounds; however, I understand that the instructors and staff at London Parkour are not specifically trained to accommodate neurodivergent individuals or to provide specialised support for conditions such as ADHD, Autism Spectrum Disorder, or other cognitive or developmental differences.',
				'By signing this waiver, I acknowledge that participation in parkour activities is at my own risk (or the risk of the minor participant, if signing as a parent or guardian). I agree that I am solely responsible for determining whether participation in parkour is suitable for myself or the minor. I understand that London Parkour does not provide specialised behavioural or therapeutic services and cannot be held liable for any incidents arising due to a lack of specific accommodations or training.',
				'If I am signing on behalf of a minor or individual with unique support needs, I take full responsibility for their participation and agree to communicate any special requirements to London Parkour staff before attending a session. I acknowledge that I may need to provide additional support as necessary to ensure their safe and enjoyable experience.',
			),
		),
	);
}
