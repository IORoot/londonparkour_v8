<?php
/**
 * Email merge-tag catalogue, substitution, and post/ACF lookup language.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Merge_Tags {

	private const LOOKUP_PATTERN = '/\{(?:post:(\d+)|cpt:([a-zA-Z0-9_-]+)\.(latest|oldest|random)|class)\.([a-zA-Z0-9_]+)\}/';

	private const RESERVED_ACCESSORS = [
		'title',
		'excerpt',
		'permalink',
		'featured_image',
	];

	private const COMPLEX_ACF_TYPES = [
		'repeater',
		'flexible_content',
		'group',
		'clone',
		'accordion',
		'tab',
		'message',
	];

	/**
	 * @return list<array{tag: string, description: string, example: string, group: string}>
	 */
	public static function catalogue(): array {
		$rows = array_merge(
			self::booking_catalogue_rows(),
			self::coupon_catalogue_rows(),
			self::lookup_catalogue_rows()
		);

		/**
		 * Full merge-tag catalogue for the settings accordion.
		 *
		 * @param list<array{tag: string, description: string, example: string, group: string}> $rows
		 */
		$filtered = apply_filters( 'clasbpro_email_merge_tag_catalogue', $rows );

		return is_array( $filtered ) ? $filtered : $rows;
	}

	/**
	 * Substitute named tags, then post/CPT/class lookup expressions.
	 *
	 * @param array<string, string> $tags
	 */
	public static function apply( string $template, array $tags ): string {
		$pairs = [];
		foreach ( $tags as $search => $replace ) {
			$search  = (string) $search;
			$replace = (string) $replace;
			if ( '' === $search ) {
				continue;
			}
			$pairs[ $search ] = $replace;
			if ( strlen( $search ) >= 3 && '{' === $search[0] && '}' === substr( $search, -1 ) ) {
				$pairs[ '&#123;' . substr( $search, 1, -1 ) . '&#125;' ] = $replace;
			}
		}
		uksort(
			$pairs,
			static function ( $a, $b ): int {
				return strlen( (string) $b ) <=> strlen( (string) $a );
			}
		);
		foreach ( $pairs as $search => $replace ) {
			$template = str_replace( $search, $replace, $template );
		}

		return self::apply_lookups( $template, $tags );
	}

	/**
	 * @param array<string, string> $tags
	 * @param array<string, mixed>  $context
	 * @return array<string, string>
	 */
	public static function filter_values( array $tags, array $context ): array {
		/**
		 * Merge-tag values for a send (booking or coupon purchase).
		 *
		 * @param array<string, string> $tags
		 * @param array<string, mixed>  $context {
		 *     @type string $kind        booking|coupon
		 *     @type int    $booking_id
		 *     @type int    $class_id
		 *     @type int    $purchase_id
		 *     @type bool   $sample
		 * }
		 */
		$filtered = apply_filters( 'clasbpro_email_merge_tag_values', $tags, $context );

		if ( ! is_array( $filtered ) ) {
			$filtered = $tags;
		}

		$out = [];
		foreach ( $filtered as $key => $value ) {
			$out[ (string) $key ] = (string) $value;
		}

		if ( 'coupon' === (string) ( $context['kind'] ?? '' ) ) {
			$out = self::fill_coupon_derived_tags( $out, $context );
		}

		return $out;
	}

	/**
	 * Labels and receipt HTML the coupon templates use but the purchase array does not.
	 *
	 * @param array<string, string> $tags
	 * @param array<string, mixed>  $context
	 * @return array<string, string>
	 */
	private static function fill_coupon_derived_tags( array $tags, array $context ): array {
		$sample      = ! empty( $context['sample'] );
		$purchase_id = (int) ( $context['purchase_id'] ?? 0 );

		$uses                      = (int) ( $tags['{pack_uses}'] ?? 0 );
		$tags['{pack_uses_label}'] = sprintf(
			/* translators: %d: number of class uses on the coupon */
			_n( '%d class', '%d classes', max( 0, $uses ), 'class-bookings-with-stripe-pro' ),
			max( 0, $uses )
		);

		$months  = 0;
		$pack_id = 0;
		if ( $purchase_id > 0 ) {
			$pack_id = (int) get_post_meta( $purchase_id, '_clasbpro_pack_id', true );
			if ( $pack_id > 0 ) {
				$pack = Packs::get_pack_data( $pack_id );
				if ( is_array( $pack ) ) {
					$months = (int) ( $pack['expiry_months'] ?? 0 );
				}
				if ( $months <= 0 ) {
					$months = (int) ( function_exists( 'get_field' ) ? get_field( 'pack_expiry_months', $pack_id ) : get_post_meta( $pack_id, 'pack_expiry_months', true ) );
				}
			}
		} elseif ( $sample ) {
			$months = 6;
		}

		if ( $months > 0 ) {
			$tags['{pack_expiry_label}'] = sprintf(
				/* translators: %d: months the coupon is valid after purchase */
				_n( '%d month from purchase', '%d months from purchase', $months, 'class-bookings-with-stripe-pro' ),
				$months
			);
		} else {
			$tags['{pack_expiry_label}'] = __( 'No expiry', 'class-bookings-with-stripe-pro' );
		}

		$receipt_url = trim( (string) ( $tags['{stripe_receipt_url}'] ?? '' ) );
		if ( '' !== $receipt_url ) {
			$tags['{receipt_link}'] = '<a href="' . esc_url( $receipt_url ) . '">' . esc_html__( 'View stripe receipt', 'class-bookings-with-stripe-pro' ) . '</a>';
		} else {
			$tags['{receipt_link}'] = __( 'No receipt', 'class-bookings-with-stripe-pro' );
		}

		return $tags;
	}

	public static function render_accordion(): string {
		$groups = [
			'booking' => __( 'Booking emails', 'class-bookings-with-stripe-pro' ),
			'coupon'  => __( 'Coupon emails', 'class-bookings-with-stripe-pro' ),
			'lookup'  => __( 'Post lookups', 'class-bookings-with-stripe-pro' ),
		];

		$by_group = [
			'booking' => [],
			'coupon'  => [],
			'lookup'  => [],
		];
		foreach ( self::catalogue() as $row ) {
			$group = (string) ( $row['group'] ?? '' );
			if ( ! isset( $by_group[ $group ] ) ) {
				continue;
			}
			$by_group[ $group ][] = $row;
		}

		ob_start();
		?>
		<details class="clasbpro-email-merge-tags-accordion">
			<summary class="clasbpro-email-merge-tags-accordion__summary"><?php esc_html_e( 'Available merge tags', 'class-bookings-with-stripe-pro' ); ?></summary>
			<div class="clasbpro-email-merge-tags-accordion__content">
				<?php foreach ( $groups as $group => $heading ) : ?>
					<?php if ( empty( $by_group[ $group ] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<h4 class="clasbpro-email-merge-tags-accordion__heading"><?php echo esc_html( $heading ); ?></h4>
					<table class="widefat striped clasbpro-email-merge-tags-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Tag', 'class-bookings-with-stripe-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Description', 'class-bookings-with-stripe-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Example', 'class-bookings-with-stripe-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $by_group[ $group ] as $row ) : ?>
							<tr>
								<td><code><?php echo esc_html( (string) ( $row['tag'] ?? '' ) ); ?></code></td>
								<td><?php echo esc_html( (string) ( $row['description'] ?? '' ) ); ?></td>
								<td><code><?php echo esc_html( (string) ( $row['example'] ?? '' ) ); ?></code></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>
				<p class="description clasbpro-email-merge-tags-accordion__note">
					<?php esc_html_e( 'For booking-form ACF extras, use {acf:FIELD_KEY} (or {FIELD_KEY}). Example: {acf:field_abc123}. Missing lookups become an empty string. {cpt:…random…} can differ on every send.', 'class-bookings-with-stripe-pro' ); ?>
				</p>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array<string, string>
	 */
	public static function sample_booking_tags(): array {
		$ymd = gmdate( 'Y-m-d', (int) strtotime( '+3 days' ) );

		$tags = [
			'{customer_name}'           => 'Alex Sample',
			'{customer_email}'          => 'alex@example.com',
			'{class_name}'              => 'Beginners Yoga',
			'{class_date}'              => Helpers::format_date( $ymd ),
			'{class_time}'              => Helpers::format_time( '10:00' ),
			'{location}'                => 'Main Studio',
			'{duration}'                => '60',
			'{price}'                   => Helpers::format_price( 15 ),
			'{slot_label}'              => '',
			'{seats}'                   => '1',
			'{quantity}'                => '1',
			'{amount_total}'            => Helpers::format_price( 15 ),
			'{booking_id}'              => '1234',
			'{class_id}'                => '101',
			'{class_day}'               => self::weekday_from_ymd( $ymd ),
			'{description}'             => '<p>A gentle introduction to yoga.</p>',
			'{stripe_receipt_url}'      => 'https://pay.stripe.com/receipts/example',
			'{coupon_used}'             => self::yes_no( false ),
			'{coupon_code}'             => '',
			'{coupon_uses_remaining}'   => '',
			'{coupon_reference}'        => '',
		];

		return self::filter_values(
			$tags,
			[
				'kind'        => 'booking',
				'booking_id'  => 0,
				'class_id'    => 0,
				'purchase_id' => 0,
				'sample'      => true,
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function sample_coupon_tags(): array {
		$tags = [
			'{customer_name}'      => 'Alex Example',
			'{customer_email}'     => 'alex@example.com',
			'{pack_name}'          => '10-class coupon',
			'{pack_code}'          => 'DEMO10CLASS',
			'{pack_uses}'          => '10',
			'{amount_total}'       => Helpers::format_stripe_amount( 15000 ),
			'{restore_url}'        => home_url( '/?clasbpro_pack_restore=sample' ),
			'{purchase_id}'        => '1001',
			'{stripe_receipt_url}' => 'https://pay.stripe.com/receipts/example',
		];

		return self::filter_values(
			$tags,
			[
				'kind'        => 'coupon',
				'booking_id'  => 0,
				'class_id'    => 0,
				'purchase_id' => 0,
				'sample'      => true,
			]
		);
	}

	public static function weekday_from_ymd( string $ymd ): string {
		$ymd = trim( $ymd );
		if ( '' === $ymd ) {
			return '';
		}
		try {
			$dt = new \DateTimeImmutable( $ymd, wp_timezone() );
			return (string) wp_date( 'l', $dt->getTimestamp() );
		} catch ( \Exception $e ) {
			return '';
		}
	}

	public static function yes_no( bool $yes ): string {
		return $yes
			? __( 'Yes', 'class-bookings-with-stripe-pro' )
			: __( 'No', 'class-bookings-with-stripe-pro' );
	}

	public static function persist_receipt_url( int $post_id, string $payment_intent_id ): void {
		if ( $post_id <= 0 ) {
			return;
		}
		$url = Stripe_Service::receipt_url_from_payment_intent( $payment_intent_id );
		if ( '' !== $url ) {
			update_post_meta( $post_id, '_clasbpro_stripe_receipt_url', esc_url_raw( $url ) );
		}
	}

	public static function persist_booking_coupon_snapshot( int $booking_id ): void {
		if ( $booking_id <= 0 ) {
			return;
		}

		$promo_id = (string) get_post_meta( $booking_id, '_clasbpro_pack_promo_id', true );
		if ( '' === $promo_id ) {
			update_post_meta( $booking_id, '_clasbpro_coupon_used', '0' );
			return;
		}

		update_post_meta( $booking_id, '_clasbpro_coupon_used', '1' );

		$code = Stripe_Service::retrieve_promotion_code_string( $promo_id );
		if ( '' !== $code ) {
			update_post_meta( $booking_id, '_clasbpro_coupon_code', sanitize_text_field( $code ) );
		}

		try {
			$promo = Stripe_Service::retrieve_promotion_code( $promo_id );
			if ( $promo ) {
				$state = Packs::promotion_state( $promo );
				update_post_meta( $booking_id, '_clasbpro_coupon_uses_remaining', (string) (int) $state['uses_remaining'] );
			}
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Coupon snapshot failed: ' . $e->getMessage() );
		}

		$purchase_id = Packs::find_purchase_by_promo_id( $promo_id );
		if ( $purchase_id > 0 ) {
			update_post_meta( $booking_id, '_clasbpro_coupon_purchase_id', $purchase_id );
		}
	}

	/**
	 * @return list<array{tag: string, description: string, example: string, group: string}>
	 */
	private static function booking_catalogue_rows(): array {
		return [
			self::row( '{customer_name}', __( 'Customer’s name.', 'class-bookings-with-stripe-pro' ), 'Alex Sample', 'booking' ),
			self::row( '{customer_email}', __( 'Customer’s email address.', 'class-bookings-with-stripe-pro' ), 'alex@example.com', 'booking' ),
			self::row( '{class_name}', __( 'Class title.', 'class-bookings-with-stripe-pro' ), 'Beginners Yoga', 'booking' ),
			self::row( '{class_date}', __( 'Booked session date.', 'class-bookings-with-stripe-pro' ), 'Thu 4 Sep 2026', 'booking' ),
			self::row( '{class_day}', __( 'Weekday of the booked date.', 'class-bookings-with-stripe-pro' ), 'Thursday', 'booking' ),
			self::row( '{class_time}', __( 'Session start time.', 'class-bookings-with-stripe-pro' ), '10:00 AM', 'booking' ),
			self::row( '{class_id}', __( 'Class post ID (raw, no #).', 'class-bookings-with-stripe-pro' ), '101', 'booking' ),
			self::row( '{location}', __( 'Class location.', 'class-bookings-with-stripe-pro' ), 'Main Studio', 'booking' ),
			self::row( '{slot_label}', __( 'Appointment slot label, if any.', 'class-bookings-with-stripe-pro' ), 'Morning', 'booking' ),
			self::row( '{duration}', __( 'Duration in minutes.', 'class-bookings-with-stripe-pro' ), '60', 'booking' ),
			self::row( '{price}', __( 'Unit price.', 'class-bookings-with-stripe-pro' ), '£15.00', 'booking' ),
			self::row( '{seats}', __( 'Seats booked.', 'class-bookings-with-stripe-pro' ), '1', 'booking' ),
			self::row( '{quantity}', __( 'Same as {seats}.', 'class-bookings-with-stripe-pro' ), '1', 'booking' ),
			self::row( '{amount_total}', __( 'Total paid.', 'class-bookings-with-stripe-pro' ), '£15.00', 'booking' ),
			self::row( '{booking_id}', __( 'Booking post ID (raw, no #).', 'class-bookings-with-stripe-pro' ), '1234', 'booking' ),
			self::row( '{description}', __( 'Class description (may include HTML).', 'class-bookings-with-stripe-pro' ), '<p>…</p>', 'booking' ),
			self::row( '{extra_fields}', __( 'All booking-form extra answers, one per line.', 'class-bookings-with-stripe-pro' ), 'Phone: 020 0000 0000', 'booking' ),
			self::row( '{acf:field_xxxxx}', __( 'One booking-form extra by ACF field key.', 'class-bookings-with-stripe-pro' ), '{acf:field_abc123}', 'booking' ),
			self::row( '{stripe_receipt_url}', __( 'Stripe hosted receipt URL. Empty when nothing was charged.', 'class-bookings-with-stripe-pro' ), 'https://pay.stripe.com/receipts/…', 'booking' ),
			self::row( '{coupon_used}', __( 'Whether this booking redeemed a coupon.', 'class-bookings-with-stripe-pro' ), 'Yes', 'booking' ),
			self::row( '{coupon_code}', __( 'Customer-facing coupon code. Empty if none.', 'class-bookings-with-stripe-pro' ), 'DEMO10CLASS', 'booking' ),
			self::row( '{coupon_uses_remaining}', __( 'Coupon uses left after this booking (snapshot).', 'class-bookings-with-stripe-pro' ), '7', 'booking' ),
			self::row( '{coupon_reference}', __( 'Pack purchase post ID (raw). Empty if none.', 'class-bookings-with-stripe-pro' ), '1001', 'booking' ),
		];
	}

	/**
	 * @return list<array{tag: string, description: string, example: string, group: string}>
	 */
	private static function coupon_catalogue_rows(): array {
		return [
			self::row( '{customer_name}', __( 'Customer’s name.', 'class-bookings-with-stripe-pro' ), 'Alex Example', 'coupon' ),
			self::row( '{customer_email}', __( 'Customer’s email address.', 'class-bookings-with-stripe-pro' ), 'alex@example.com', 'coupon' ),
			self::row( '{pack_name}', __( 'Coupon product name.', 'class-bookings-with-stripe-pro' ), '10-class coupon', 'coupon' ),
			self::row( '{pack_code}', __( 'Customer-facing coupon code.', 'class-bookings-with-stripe-pro' ), 'DEMO10CLASS', 'coupon' ),
			self::row( '{pack_uses}', __( 'Uses included at purchase.', 'class-bookings-with-stripe-pro' ), '10', 'coupon' ),
			self::row( '{pack_uses_label}', __( 'Uses included, labelled as classes.', 'class-bookings-with-stripe-pro' ), '10 classes', 'coupon' ),
			self::row( '{pack_expiry_label}', __( 'Coupon validity from purchase, or “No expiry”.', 'class-bookings-with-stripe-pro' ), '6 months from purchase', 'coupon' ),
			self::row( '{amount_total}', __( 'Total paid.', 'class-bookings-with-stripe-pro' ), '£150.00', 'coupon' ),
			self::row( '{restore_url}', __( 'Link to restore the coupon on another device.', 'class-bookings-with-stripe-pro' ), 'https://example.com/?clasbpro_pack_restore=…', 'coupon' ),
			self::row( '{purchase_id}', __( 'Coupon purchase post ID (raw, no #).', 'class-bookings-with-stripe-pro' ), '1001', 'coupon' ),
			self::row( '{stripe_receipt_url}', __( 'Stripe hosted receipt URL. Empty when nothing was charged.', 'class-bookings-with-stripe-pro' ), 'https://pay.stripe.com/receipts/…', 'coupon' ),
			self::row( '{receipt_link}', __( 'Stripe receipt anchor, or “No receipt” when nothing was charged.', 'class-bookings-with-stripe-pro' ), '<a href="https://pay.stripe.com/receipts/example">View stripe receipt</a>', 'coupon' ),
		];
	}

	/**
	 * @return list<array{tag: string, description: string, example: string, group: string}>
	 */
	private static function lookup_catalogue_rows(): array {
		return [
			self::row( '{post:123.importid}', __( 'ACF or reserved field on a specific published post.', 'class-bookings-with-stripe-pro' ), 'tut_catleap_intro', 'lookup' ),
			self::row( '{post:123.featured_image}', __( 'Featured image URL (large) for that post.', 'class-bookings-with-stripe-pro' ), 'https://example.com/wp-content/uploads/leap.jpg', 'lookup' ),
			self::row( '{post:123.title}', __( 'Post title.', 'class-bookings-with-stripe-pro' ), 'Cat leap introduction', 'lookup' ),
			self::row( '{post:123.excerpt}', __( 'Post excerpt.', 'class-bookings-with-stripe-pro' ), 'The cleanest way over a wall.', 'lookup' ),
			self::row( '{post:123.permalink}', __( 'Public permalink.', 'class-bookings-with-stripe-pro' ), 'https://example.com/tutorials/cat-leap', 'lookup' ),
			self::row( '{cpt:post.latest.title}', __( 'Field from the newest published post of that type (by publish date).', 'class-bookings-with-stripe-pro' ), 'Latest post title', 'lookup' ),
			self::row( '{cpt:post.oldest.title}', __( 'Field from the oldest published post of that type.', 'class-bookings-with-stripe-pro' ), 'Oldest post title', 'lookup' ),
			self::row( '{cpt:post.random.featured_image}', __( 'Field from a random published post. Differs every send.', 'class-bookings-with-stripe-pro' ), 'https://example.com/image.jpg', 'lookup' ),
			self::row( '{class.importid}', __( 'Same as {post:CLASS_ID.importid} for the booked class. Empty on coupon emails.', 'class-bookings-with-stripe-pro' ), 'class_import_key', 'lookup' ),
			self::row( '{class.featured_image}', __( 'Booked class featured image URL (large).', 'class-bookings-with-stripe-pro' ), 'https://example.com/class.jpg', 'lookup' ),
		];
	}

	/**
	 * @return array{tag: string, description: string, example: string, group: string}
	 */
	private static function row( string $tag, string $description, string $example, string $group ): array {
		return [
			'tag'         => $tag,
			'description' => $description,
			'example'     => $example,
			'group'       => $group,
		];
	}

	/**
	 * @param array<string, string> $tags
	 */
	private static function apply_lookups( string $template, array $tags ): string {
		if ( ! preg_match( self::LOOKUP_PATTERN, $template ) ) {
			return $template;
		}

		$class_id = (int) ( $tags['{class_id}'] ?? 0 );

		return (string) preg_replace_callback(
			self::LOOKUP_PATTERN,
			static function ( array $m ) use ( $class_id ): string {
				$field = (string) ( $m[4] ?? '' );
				$tag   = (string) ( $m[0] ?? '' );

				if ( '' !== ( $m[1] ?? '' ) ) {
					return self::lookup_post_field( (int) $m[1], $field, $tag );
				}
				if ( '' !== ( $m[2] ?? '' ) ) {
					$post_id = self::query_cpt_post_id( (string) $m[2], (string) $m[3] );
					if ( $post_id <= 0 ) {
						self::log_miss( $tag );
						return '';
					}
					return self::lookup_post_field( $post_id, $field, $tag );
				}

				if ( $class_id <= 0 ) {
					self::log_miss( $tag );
					return '';
				}

				return self::lookup_post_field( $class_id, $field, $tag, true );
			},
			$template
		);
	}

	private static function query_cpt_post_id( string $post_type, string $pick ): int {
		if ( ! post_type_exists( $post_type ) || ! self::is_allowed_post_type( $post_type ) ) {
			return 0;
		}

		$args = [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ( 'random' === $pick ) {
			$args['orderby'] = 'rand';
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'oldest' === $pick ? 'ASC' : 'DESC';
		}

		$q = new \WP_Query( $args );
		return ! empty( $q->posts[0] ) ? (int) $q->posts[0] : 0;
	}

	private static function lookup_post_field( int $post_id, string $field, string $tag, bool $skip_type_check = false ): string {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || ! self::is_addressable_post( $post, $skip_type_check ) ) {
			self::log_miss( $tag );
			return '';
		}

		if ( in_array( $field, self::RESERVED_ACCESSORS, true ) ) {
			return self::reserved_accessor( $post, $field );
		}

		return self::acf_field_value( $post_id, $field );
	}

	private static function reserved_accessor( \WP_Post $post, string $field ): string {
		$post_id = (int) $post->ID;

		switch ( $field ) {
			case 'title':
				return (string) get_the_title( $post_id );
			case 'excerpt':
				$excerpt = (string) $post->post_excerpt;
				if ( '' === trim( $excerpt ) ) {
					$excerpt = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 55, '' );
				}
				return trim( wp_strip_all_tags( $excerpt ) );
			case 'permalink':
				$url = get_permalink( $post_id );
				return $url ? (string) $url : '';
			case 'featured_image':
				$thumb_id = get_post_thumbnail_id( $post_id );
				if ( ! $thumb_id ) {
					return '';
				}
				return self::attachment_url( (int) $thumb_id );
		}

		return '';
	}

	private static function acf_field_value( int $post_id, string $name ): string {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$field = function_exists( 'get_field_object' ) ? get_field_object( $name, $post_id ) : null;
		$type  = is_array( $field ) ? (string) ( $field['type'] ?? '' ) : '';
		if ( in_array( $type, self::COMPLEX_ACF_TYPES, true ) ) {
			return '';
		}

		$value = get_field( $name, $post_id );
		return self::coerce_value( $value, is_array( $field ) ? $field : null );
	}

	/**
	 * @param mixed                     $value
	 * @param array<string, mixed>|null $field
	 */
	private static function coerce_value( $value, ?array $field ): string {
		$type = is_array( $field ) ? (string) ( $field['type'] ?? '' ) : '';

		if ( null === $value || false === $value || '' === $value ) {
			return '';
		}

		if ( in_array( $type, [ 'true_false' ], true ) || is_bool( $value ) ) {
			return self::yes_no( (bool) $value );
		}

		if ( in_array( $type, [ 'image', 'file' ], true ) ) {
			return self::media_url( $value );
		}

		if ( in_array( $type, [ 'select', 'radio', 'checkbox' ], true ) ) {
			return self::choice_labels( $value, $field );
		}

		if ( in_array( $type, [ 'relationship', 'post_object', 'page_link', 'user', 'taxonomy' ], true ) ) {
			return self::related_labels( $value, $type );
		}

		if ( is_object( $value ) && isset( $value->post_title ) ) {
			return (string) $value->post_title;
		}

		if ( is_array( $value ) ) {
			if ( isset( $value['url'] ) || isset( $value['ID'] ) || isset( $value['id'] ) ) {
				return self::media_url( $value );
			}
			if ( self::looks_like_repeater( $value ) ) {
				return '';
			}
			$parts = [];
			foreach ( $value as $item ) {
				$part = self::coerce_value( $item, null );
				if ( '' !== $part ) {
					$parts[] = $part;
				}
			}
			return implode( ', ', $parts );
		}

		if ( is_numeric( $value ) && in_array( $type, [ 'image', 'file' ], true ) ) {
			return self::attachment_url( (int) $value );
		}

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		return '';
	}

	/**
	 * @param mixed $value
	 */
	private static function media_url( $value ): string {
		if ( is_numeric( $value ) ) {
			return self::attachment_url( (int) $value );
		}
		if ( is_string( $value ) && preg_match( '#^https?://#i', $value ) ) {
			$id = attachment_url_to_postid( $value );
			if ( $id ) {
				$sized = self::attachment_url( $id );
				if ( '' !== $sized ) {
					return $sized;
				}
			}
			return esc_url_raw( $value );
		}
		if ( is_array( $value ) ) {
			$id = (int) ( $value['ID'] ?? $value['id'] ?? 0 );
			if ( $id > 0 ) {
				return self::attachment_url( $id );
			}
			$url = (string) ( $value['url'] ?? '' );
			return '' !== $url ? esc_url_raw( $url ) : '';
		}
		return '';
	}

	private static function attachment_url( int $attachment_id ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}
		$size = apply_filters( 'clasbpro_merge_tag_image_size', 'large' );
		if ( ! is_string( $size ) || '' === $size ) {
			$size = 'large';
		}
		$url = wp_get_attachment_image_url( $attachment_id, $size );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
		$file = wp_get_attachment_url( $attachment_id );
		return is_string( $file ) && '' !== $file ? $file : '';
	}

	/**
	 * @param mixed                    $value
	 * @param array<string, mixed>|null $field
	 */
	private static function choice_labels( $value, ?array $field ): string {
		$choices = is_array( $field ) && isset( $field['choices'] ) && is_array( $field['choices'] )
			? $field['choices']
			: [];
		$values  = is_array( $value ) ? $value : [ $value ];
		$labels  = [];
		foreach ( $values as $item ) {
			if ( is_array( $item ) ) {
				continue;
			}
			$key = (string) $item;
			$labels[] = isset( $choices[ $key ] ) ? (string) $choices[ $key ] : $key;
		}
		return implode( ', ', $labels );
	}

	/**
	 * @param mixed $value
	 */
	private static function related_labels( $value, string $type ): string {
		$items = is_array( $value ) ? $value : [ $value ];
		$out   = [];
		foreach ( $items as $item ) {
			if ( 'user' === $type ) {
				$user = $item;
				if ( is_numeric( $item ) ) {
					$user = get_user_by( 'id', (int) $item );
				} elseif ( is_array( $item ) ) {
					$user = get_user_by( 'id', (int) ( $item['ID'] ?? 0 ) );
				}
				if ( $user && isset( $user->display_name ) ) {
					$out[] = (string) $user->display_name;
				}
				continue;
			}
			if ( 'taxonomy' === $type ) {
				if ( is_object( $item ) && isset( $item->name ) ) {
					$out[] = (string) $item->name;
					continue;
				}
				if ( is_numeric( $item ) ) {
					$term = get_term( (int) $item );
					if ( $term && ! is_wp_error( $term ) ) {
						$out[] = (string) $term->name;
					}
				}
				continue;
			}
			$post_id = 0;
			if ( is_object( $item ) && isset( $item->ID ) ) {
				$post_id = (int) $item->ID;
			} elseif ( is_numeric( $item ) ) {
				$post_id = (int) $item;
			} elseif ( is_array( $item ) ) {
				$post_id = (int) ( $item['ID'] ?? $item['id'] ?? 0 );
			} elseif ( is_string( $item ) && preg_match( '#^https?://#i', $item ) ) {
				$out[] = $item;
				continue;
			}
			if ( $post_id > 0 ) {
				$title = get_the_title( $post_id );
				if ( '' !== $title ) {
					$out[] = $title;
				}
			}
		}
		return implode( ', ', $out );
	}

	/**
	 * @param array<mixed> $value
	 */
	private static function looks_like_repeater( array $value ): bool {
		if ( empty( $value ) ) {
			return false;
		}
		$first = reset( $value );
		return is_array( $first ) && ! isset( $first['url'] ) && ! isset( $first['ID'] ) && ! isset( $first['id'] );
	}

	private static function is_addressable_post( \WP_Post $post, bool $skip_type_check = false ): bool {
		if ( 'publish' !== $post->post_status ) {
			return false;
		}
		if ( $skip_type_check ) {
			return true;
		}
		return self::is_allowed_post_type( $post->post_type );
	}

	private static function is_allowed_post_type( string $post_type ): bool {
		$obj = get_post_type_object( $post_type );
		if ( ! $obj || empty( $obj->publicly_queryable ) ) {
			return false;
		}

		$allowed = apply_filters( 'clasbpro_merge_tag_post_types', null );
		if ( is_array( $allowed ) ) {
			return in_array( $post_type, $allowed, true );
		}

		return true;
	}

	private static function log_miss( string $tag ): void {
		Helpers::debug_log( '[class-bookings-with-stripe-pro] Merge tag lookup empty: ' . $tag );
	}
}
