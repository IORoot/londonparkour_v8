<?php
/**
 * Admin reports for Class Bookings with Stripe.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Reports {

	private const VIEW_SUMMARY  = 'summary';
	private const VIEW_CLASSES  = 'classes';
	private const VIEW_BOOKINGS = 'bookings';
	private const CACHE_KEY     = 'clasbpro_reports_paid_bookings_v1';
	private const CACHE_TTL     = 600;
	private const CHART_MONTHS  = 12;

	/**
	 * @var string
	 */
	private static string $page_hook = '';

	/**
	 * Cached yearly chart payload per year (same request: enqueue + render).
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $yearly_chart_cache = [];

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_filter( 'admin_body_class', [ self::class, 'filter_body_class' ] );
		add_action( 'save_post_' . CPT::BOOKING_PT, [ self::class, 'invalidate_cache' ] );
	}

	public static function invalidate_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * @param string $classes Space-prefixed body classes.
	 */
	public static function filter_body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && $screen->id === CPT::CLASS_PT . '_page_clasbpro-reports' ) {
			return $classes . ' clasbpro-reports';
		}
		return $classes;
	}

	public static function register_menu(): void {
		self::$page_hook = (string) add_submenu_page(
			'edit.php?post_type=' . CPT::CLASS_PT,
			__( 'Reports', 'class-bookings-with-stripe-pro' ),
			__( 'Reports', 'class-bookings-with-stripe-pro' ),
			'manage_options',
			'clasbpro-reports',
			[ self::class, 'render_page' ]
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( '' === self::$page_hook || $hook !== self::$page_hook ) {
			return;
		}

		wp_enqueue_style(
			'clasbpro-reports',
			CLASBOWPRO_URL . 'assets/cbfs-booking-reports-admin.css',
			[],
			CLASBOWPRO_VERSION
		);

		wp_enqueue_script(
			'clasbpro-chart',
			CLASBOWPRO_URL . 'assets/vendor/chart.umd.min.js',
			[],
			'4.5.1',
			true
		);

		$view = self::reports_view();

		if ( self::VIEW_SUMMARY === $view ) {
			wp_enqueue_script(
				'clasbpro-chart-adapter-date-fns',
				CLASBOWPRO_URL . 'assets/vendor/chartjs-adapter-date-fns.bundle.min.js',
				[ 'clasbpro-chart' ],
				'3.0.0',
				true
			);
			wp_enqueue_script(
				'clasbpro-hammer',
				CLASBOWPRO_URL . 'assets/vendor/hammer.min.js',
				[],
				'2.0.8',
				true
			);
			wp_enqueue_script(
				'clasbpro-chartjs-zoom',
				CLASBOWPRO_URL . 'assets/vendor/chartjs-plugin-zoom.min.js',
				[ 'clasbpro-chart', 'clasbpro-hammer' ],
				'2.2.0',
				true
			);
			wp_enqueue_script(
				'clasbpro-reports-chart',
				CLASBOWPRO_URL . 'assets/cbfs-booking-reports-chart.js',
				[ 'clasbpro-chart', 'clasbpro-chart-adapter-date-fns', 'clasbpro-chartjs-zoom' ],
				CLASBOWPRO_VERSION,
				true
			);
			wp_localize_script(
				'clasbpro-reports-chart',
				'clasbproReportsChart',
				self::yearly_bookings_chart_data( self::reports_year() )
			);
		}

		if ( self::VIEW_CLASSES === $view ) {
			wp_enqueue_script(
				'clasbpro-chart-adapter-date-fns',
				CLASBOWPRO_URL . 'assets/vendor/chartjs-adapter-date-fns.bundle.min.js',
				[ 'clasbpro-chart' ],
				'3.0.0',
				true
			);
			wp_enqueue_script(
				'clasbpro-reports-class-charts',
				CLASBOWPRO_URL . 'assets/cbfs-booking-reports-class-charts.js',
				[ 'clasbpro-chart', 'clasbpro-chart-adapter-date-fns' ],
				CLASBOWPRO_VERSION,
				true
			);
			wp_localize_script(
				'clasbpro-reports-class-charts',
				'clasbproClassCharts',
				self::class_charts_payload( self::selected_class_id() )
			);
		}

		if ( self::VIEW_BOOKINGS === $view ) {
			wp_enqueue_script(
				'clasbpro-reports-customers',
				CLASBOWPRO_URL . 'assets/cbfs-booking-reports-customers.js',
				[],
				CLASBOWPRO_VERSION,
				true
			);
			wp_localize_script(
				'clasbpro-reports-customers',
				'clasbproCustomerReport',
				self::customer_report_payload()
			);
		}
	}

	public static function render_page(): void {
		$view = self::reports_view();
		?>
		<div class="wrap clasbpro-reports-wrap">
			<?php
			if ( self::VIEW_SUMMARY === $view ) {
				self::render_hero();
			} else {
				self::render_compact_header( $view );
			}
			?>
			<div class="clasbpro-reports-layout">
				<?php self::render_sidebar( $view ); ?>
				<div class="clasbpro-reports-main">
					<?php
					if ( self::VIEW_CLASSES === $view ) {
						self::render_classes_view();
					} elseif ( self::VIEW_BOOKINGS === $view ) {
						self::render_bookings_view();
					} else {
						self::render_summary_view();
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_hero(): void {
		?>
		<header class="clasbpro-reports-hero">
			<div class="clasbpro-reports-hero__bg" aria-hidden="true">
				<span class="clasbpro-reports-hero__blob clasbpro-reports-hero__blob--1"></span>
				<span class="clasbpro-reports-hero__blob clasbpro-reports-hero__blob--2"></span>
				<span class="clasbpro-reports-hero__blob clasbpro-reports-hero__blob--3"></span>
				<span class="clasbpro-reports-hero__dots"></span>
			</div>
			<div class="clasbpro-reports-hero__inner">
				<div class="clasbpro-reports-hero__title-row">
					<div class="clasbpro-reports-hero__logo-wrap">
						<img
							class="clasbpro-reports-hero__logo"
							src="<?php echo esc_url( CLASBOWPRO_URL . 'assets/logo_plugin.svg' ); ?>"
							width="80"
							height="67"
							alt=""
							decoding="async"
							loading="lazy"
						/>
					</div>
					<div class="clasbpro-reports-hero__text">
						<h1 class="clasbpro-reports-hero__title"><?php esc_html_e( 'Class Bookings with Stripe — Reports', 'class-bookings-with-stripe-pro' ); ?></h1>
						<p class="clasbpro-reports-hero__lead">
							<?php esc_html_e( 'Historic trends, upcoming occupancy, and per-class guest lists in one place.', 'class-bookings-with-stripe-pro' ); ?>
						</p>
					</div>
				</div>
			</div>
		</header>
		<?php
	}

	private static function render_compact_header( string $view ): void {
		$titles = [
			self::VIEW_CLASSES  => __( 'Class reports', 'class-bookings-with-stripe-pro' ),
			self::VIEW_BOOKINGS => __( 'Customer reports', 'class-bookings-with-stripe-pro' ),
		];
		$leads = [
			self::VIEW_CLASSES  => __( 'Per-class metrics and charts for the last 12 months.', 'class-bookings-with-stripe-pro' ),
			self::VIEW_BOOKINGS => __( 'Lifetime value, repeat bookings, and customer tenure.', 'class-bookings-with-stripe-pro' ),
		];
		?>
		<header class="clasbpro-reports-compact-header">
			<h1 class="clasbpro-reports-compact-header__title"><?php echo esc_html( $titles[ $view ] ?? '' ); ?></h1>
			<p class="clasbpro-reports-compact-header__lead"><?php echo esc_html( $leads[ $view ] ?? '' ); ?></p>
		</header>
		<?php
	}

	private static function render_sidebar( string $active_view ): void {
		$items = [
			self::VIEW_SUMMARY  => __( 'Summary', 'class-bookings-with-stripe-pro' ),
			self::VIEW_CLASSES  => __( 'Classes', 'class-bookings-with-stripe-pro' ),
			self::VIEW_BOOKINGS => __( 'Customers', 'class-bookings-with-stripe-pro' ),
		];
		?>
		<nav class="clasbpro-reports-sidebar" aria-label="<?php esc_attr_e( 'Report sections', 'class-bookings-with-stripe-pro' ); ?>">
			<ul class="clasbpro-reports-sidebar__list">
				<?php foreach ( $items as $view => $label ) : ?>
					<li class="clasbpro-reports-sidebar__item">
						<a
							class="clasbpro-reports-sidebar__link<?php echo $active_view === $view ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( self::reports_url( $view ) ); ?>"
							<?php echo $active_view === $view ? ' aria-current="page"' : ''; ?>
						><?php echo esc_html( $label ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	private static function render_summary_view(): void {
		$year             = self::reports_year();
		$chart            = self::yearly_bookings_chart_data( $year );
		$upcoming_sessions = self::next_sessions( 12 );
		$by_class         = self::next_sessions_by_class( 3 );
		$current_year     = (int) wp_date( 'Y' );
		$year_min         = max( 2018, $current_year - 12 );
		$year_max         = $current_year + 1;
		?>
		<section class="clasbpro-reports-panel" aria-labelledby="clasbpro-reports-historic-heading">
			<div class="clasbpro-reports-panel__head clasbpro-reports-panel__head--split">
				<div class="clasbpro-reports-panel__head-main">
					<h2 id="clasbpro-reports-historic-heading" class="clasbpro-reports-panel__title"><?php esc_html_e( 'Bookings by class (year view)', 'class-bookings-with-stripe-pro' ); ?></h2>
					<p class="clasbpro-reports-panel__desc">
						<?php
						printf(
							/* translators: %s: calendar year (e.g. 2026) */
							esc_html__( 'One line per Class with paid bookings. X-axis is every day from 1 Jan to 31 Dec %s (students booked that day). Scroll the mouse wheel to zoom; click-drag to pan. Double-click the chart to reset zoom.', 'class-bookings-with-stripe-pro' ),
							esc_html( (string) $year )
						);
						?>
					</p>
				</div>
				<form class="clasbpro-reports-year-form" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
					<input type="hidden" name="post_type" value="<?php echo esc_attr( CPT::CLASS_PT ); ?>" />
					<input type="hidden" name="page" value="clasbpro-reports" />
					<input type="hidden" name="view" value="summary" />
					<label class="clasbpro-reports-year-form__label" for="clasbpro-reports-year"><?php esc_html_e( 'Year', 'class-bookings-with-stripe-pro' ); ?></label>
					<select class="clasbpro-reports-year-form__select" id="clasbpro-reports-year" name="clasbpro_year">
						<?php for ( $y = $year_min; $y <= $year_max; $y++ ) : ?>
							<option value="<?php echo esc_attr( (string) $y ); ?>"<?php selected( $year, $y ); ?>><?php echo esc_html( (string) $y ); ?></option>
						<?php endfor; ?>
					</select>
				</form>
			</div>

			<?php if ( empty( $chart['hasData'] ) ) : ?>
				<div class="clasbpro-reports-empty">
					<p>
						<?php
						printf(
							/* translators: %d: calendar year */
							esc_html__( 'No paid bookings with class dates in %d yet. Choose another year or complete a checkout to see data here.', 'class-bookings-with-stripe-pro' ),
							(int) $year
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<div class="clasbpro-reports-chartjs" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: year */ __( 'Students booked per day in %d, by class', 'class-bookings-with-stripe-pro' ), $year ) ); ?>">
					<canvas id="clasbpro-reports-year-chart"></canvas>
				</div>
				<p class="clasbpro-reports-actions">
					<button type="button" class="button button-secondary clasbpro-reports-btn" id="clasbpro-reports-chart-reset"><?php esc_html_e( 'Reset zoom', 'class-bookings-with-stripe-pro' ); ?></button>
				</p>
			<?php endif; ?>
		</section>

		<section class="clasbpro-reports-panel" aria-labelledby="clasbpro-reports-upcoming-heading">
			<div class="clasbpro-reports-panel__head">
				<h2 id="clasbpro-reports-upcoming-heading" class="clasbpro-reports-panel__title"><?php esc_html_e( 'Booked people for upcoming classes', 'class-bookings-with-stripe-pro' ); ?></h2>
				<p class="clasbpro-reports-panel__desc"><?php esc_html_e( 'Next sessions across all active Classes, ordered by date.', 'class-bookings-with-stripe-pro' ); ?></p>
			</div>
			<div class="clasbpro-reports-table-scroll">
				<table class="widefat striped clasbpro-reports-table">
					<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Class', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Time', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'People booked', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Capacity', 'class-bookings-with-stripe-pro' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php if ( empty( $upcoming_sessions ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No upcoming classes found.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $upcoming_sessions as $session ) : ?>
							<?php
							$people = self::people_booked_count( (int) $session['class_id'], (string) $session['date'] );
							$cap    = (int) $session['capacity'];
							$pct    = $cap > 0 ? min( 100, round( 100 * $people / $cap ) ) : 0;
							?>
							<tr>
								<td data-label="<?php esc_attr_e( 'Class', 'class-bookings-with-stripe-pro' ); ?>"><?php echo esc_html( (string) $session['class_name'] ); ?></td>
								<td data-label="<?php esc_attr_e( 'Date', 'class-bookings-with-stripe-pro' ); ?>"><?php echo esc_html( Helpers::format_date( (string) $session['date'] ) ); ?></td>
								<td data-label="<?php esc_attr_e( 'Time', 'class-bookings-with-stripe-pro' ); ?>"><?php echo esc_html( Helpers::format_time( (string) $session['start_time'] ) ); ?></td>
								<td data-label="<?php esc_attr_e( 'People booked', 'class-bookings-with-stripe-pro' ); ?>">
									<span class="clasbpro-reports-meter">
										<span class="clasbpro-reports-meter__text"><?php echo esc_html( (string) $people ); ?></span>
										<span class="clasbpro-reports-meter__bar" style="--clasbpro-meter: <?php echo esc_attr( (string) $pct ); ?>%;"></span>
									</span>
								</td>
								<td data-label="<?php esc_attr_e( 'Capacity', 'class-bookings-with-stripe-pro' ); ?>"><?php echo esc_html( (string) $cap ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>

		<section class="clasbpro-reports-panel" aria-labelledby="clasbpro-reports-by-class-heading">
			<div class="clasbpro-reports-panel__head">
				<h2 id="clasbpro-reports-by-class-heading" class="clasbpro-reports-panel__title"><?php esc_html_e( 'Next three sessions per Class', 'class-bookings-with-stripe-pro' ); ?></h2>
				<p class="clasbpro-reports-panel__desc"><?php esc_html_e( 'Guest names and emails for each upcoming date.', 'class-bookings-with-stripe-pro' ); ?></p>
			</div>
			<?php if ( empty( $by_class ) ) : ?>
				<div class="clasbpro-reports-empty">
					<p><?php esc_html_e( 'No upcoming classes found.', 'class-bookings-with-stripe-pro' ); ?></p>
				</div>
			<?php else : ?>
				<div class="clasbpro-reports-table-scroll clasbpro-reports-table-scroll--wide">
					<table class="widefat striped clasbpro-reports-table clasbpro-reports-table--nested">
						<thead>
						<tr>
							<th scope="col" class="clasbpro-reports-table__class-col"><?php esc_html_e( 'Class', 'class-bookings-with-stripe-pro' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Upcoming #1', 'class-bookings-with-stripe-pro' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Upcoming #2', 'class-bookings-with-stripe-pro' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Upcoming #3', 'class-bookings-with-stripe-pro' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( $by_class as $class_row ) : ?>
							<tr>
								<td class="clasbpro-reports-table__class-name" data-label="<?php esc_attr_e( 'Class', 'class-bookings-with-stripe-pro' ); ?>">
									<strong><?php echo esc_html( (string) $class_row['class_name'] ); ?></strong>
								</td>
								<?php for ( $i = 0; $i < 3; $i++ ) : ?>
									<?php $session = $class_row['sessions'][ $i ] ?? null; ?>
									<td class="clasbpro-reports-session" data-label="<?php echo esc_attr( sprintf( /* translators: %d: slot number 1–3 */ __( 'Upcoming #%d', 'class-bookings-with-stripe-pro' ), $i + 1 ) ); ?>">
										<?php if ( ! $session ) : ?>
											<span class="clasbpro-reports-session__empty"><?php esc_html_e( 'No session', 'class-bookings-with-stripe-pro' ); ?></span>
										<?php else : ?>
											<?php $rows = self::bookings_for_session( (int) $session['class_id'], (string) $session['date'] ); ?>
											<div class="clasbpro-reports-session__when">
												<?php
												printf(
													/* translators: 1: date, 2: time */
													esc_html__( '%1$s · %2$s', 'class-bookings-with-stripe-pro' ),
													esc_html( Helpers::format_date( (string) $session['date'] ) ),
													esc_html( Helpers::format_time( (string) $session['start_time'] ) )
												);
												?>
											</div>
											<table class="clasbpro-reports-mini">
												<thead>
												<tr>
													<th scope="col"><?php esc_html_e( 'Name', 'class-bookings-with-stripe-pro' ); ?></th>
													<th scope="col"><?php esc_html_e( 'Email', 'class-bookings-with-stripe-pro' ); ?></th>
													<th scope="col" class="clasbpro-reports-mini__seats"><?php esc_html_e( 'Seats', 'class-bookings-with-stripe-pro' ); ?></th>
												</tr>
												</thead>
												<tbody>
												<?php if ( empty( $rows ) ) : ?>
													<tr><td colspan="3" class="clasbpro-reports-mini__empty"><?php esc_html_e( 'No bookings yet.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
												<?php else : ?>
													<?php foreach ( $rows as $row ) : ?>
														<tr>
															<td><?php echo esc_html( (string) $row['name'] ); ?></td>
															<td>
																<?php if ( (string) $row['email'] !== '' ) : ?>
																	<a href="<?php echo esc_url( 'mailto:' . sanitize_email( (string) $row['email'] ) ); ?>"><?php echo esc_html( (string) $row['email'] ); ?></a>
																<?php endif; ?>
															</td>
															<td class="clasbpro-reports-mini__seats"><?php echo esc_html( (string) (int) $row['seats'] ); ?></td>
														</tr>
													<?php endforeach; ?>
												<?php endif; ?>
												</tbody>
											</table>
										<?php endif; ?>
									</td>
								<?php endfor; ?>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	private static function render_classes_view(): void {
		$class_ids = self::published_class_ids();
		$class_id  = self::selected_class_id();
		?>
		<section class="clasbpro-reports-panel" aria-labelledby="clasbpro-reports-class-picker-heading">
			<div class="clasbpro-reports-panel__head clasbpro-reports-panel__head--split">
				<div class="clasbpro-reports-panel__head-main">
					<h2 id="clasbpro-reports-class-picker-heading" class="clasbpro-reports-panel__title"><?php esc_html_e( 'Class overview', 'class-bookings-with-stripe-pro' ); ?></h2>
					<p class="clasbpro-reports-panel__desc"><?php esc_html_e( 'All-time headline metrics with charts for the last 12 months (by class date).', 'class-bookings-with-stripe-pro' ); ?></p>
				</div>
				<?php if ( ! empty( $class_ids ) ) : ?>
					<form class="clasbpro-reports-year-form" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
						<input type="hidden" name="post_type" value="<?php echo esc_attr( CPT::CLASS_PT ); ?>" />
						<input type="hidden" name="page" value="clasbpro-reports" />
						<input type="hidden" name="view" value="classes" />
						<label class="clasbpro-reports-year-form__label" for="clasbpro-reports-class-id"><?php esc_html_e( 'Class', 'class-bookings-with-stripe-pro' ); ?></label>
						<select class="clasbpro-reports-year-form__select" id="clasbpro-reports-class-id" name="class_id">
							<?php foreach ( $class_ids as $cid ) : ?>
								<option value="<?php echo esc_attr( (string) $cid ); ?>"<?php selected( $class_id, $cid ); ?>><?php echo esc_html( get_the_title( $cid ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</form>
				<?php endif; ?>
			</div>

			<?php if ( empty( $class_ids ) ) : ?>
				<div class="clasbpro-reports-empty">
					<p><?php esc_html_e( 'No published classes found.', 'class-bookings-with-stripe-pro' ); ?></p>
				</div>
			<?php else : ?>
				<?php $metrics = self::class_metrics( $class_id ); ?>
				<div class="clasbpro-reports-metrics">
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Total revenue', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['total_revenue_display'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Students booked', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['total_students'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Bookings', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['total_bookings'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Unique customers', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['unique_customers'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Avg occupancy', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['avg_occupancy_display'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Current price', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['price_display'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'First booking', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['first_booking_display'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Most recent booking', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['last_booking_display'] ); ?></span>
					</div>
					<div class="clasbpro-reports-metric">
						<span class="clasbpro-reports-metric__label"><?php esc_html_e( 'Upcoming sessions booked', 'class-bookings-with-stripe-pro' ); ?></span>
						<span class="clasbpro-reports-metric__value"><?php echo esc_html( (string) $metrics['upcoming_sessions'] ); ?></span>
					</div>
				</div>

				<div class="clasbpro-reports-charts-grid">
					<div class="clasbpro-reports-chart-card">
						<h3 class="clasbpro-reports-chart-card__title"><?php esc_html_e( 'Students per month', 'class-bookings-with-stripe-pro' ); ?></h3>
						<div class="clasbpro-reports-chartjs clasbpro-reports-chartjs--compact"><canvas id="clasbpro-class-chart-students"></canvas></div>
					</div>
					<div class="clasbpro-reports-chart-card">
						<h3 class="clasbpro-reports-chart-card__title"><?php esc_html_e( 'Revenue per month', 'class-bookings-with-stripe-pro' ); ?></h3>
						<div class="clasbpro-reports-chartjs clasbpro-reports-chartjs--compact"><canvas id="clasbpro-class-chart-revenue"></canvas></div>
					</div>
					<div class="clasbpro-reports-chart-card">
						<h3 class="clasbpro-reports-chart-card__title"><?php esc_html_e( 'Occupancy per session', 'class-bookings-with-stripe-pro' ); ?></h3>
						<div class="clasbpro-reports-chartjs clasbpro-reports-chartjs--compact"><canvas id="clasbpro-class-chart-occupancy"></canvas></div>
					</div>
					<div class="clasbpro-reports-chart-card">
						<h3 class="clasbpro-reports-chart-card__title"><?php esc_html_e( 'Cumulative revenue', 'class-bookings-with-stripe-pro' ); ?></h3>
						<div class="clasbpro-reports-chartjs clasbpro-reports-chartjs--compact"><canvas id="clasbpro-class-chart-cumulative"></canvas></div>
					</div>
					<div class="clasbpro-reports-chart-card clasbpro-reports-chart-card--wide">
						<h3 class="clasbpro-reports-chart-card__title"><?php esc_html_e( 'Bookings by day of week', 'class-bookings-with-stripe-pro' ); ?></h3>
						<div class="clasbpro-reports-chartjs clasbpro-reports-chartjs--compact"><canvas id="clasbpro-class-chart-dow"></canvas></div>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	private static function render_bookings_view(): void {
		$customers = self::customer_report_rows();
		$kpis      = self::customer_kpis( $customers );
		?>
		<section class="clasbpro-reports-kpis" aria-label="<?php esc_attr_e( 'Customer summary', 'class-bookings-with-stripe-pro' ); ?>">
			<div class="clasbpro-reports-kpi">
				<span class="clasbpro-reports-kpi__label"><?php esc_html_e( 'Total customers', 'class-bookings-with-stripe-pro' ); ?></span>
				<span class="clasbpro-reports-kpi__value"><?php echo esc_html( (string) $kpis['total_customers'] ); ?></span>
			</div>
			<div class="clasbpro-reports-kpi">
				<span class="clasbpro-reports-kpi__label"><?php esc_html_e( 'Total revenue', 'class-bookings-with-stripe-pro' ); ?></span>
				<span class="clasbpro-reports-kpi__value"><?php echo esc_html( (string) $kpis['total_revenue_display'] ); ?></span>
			</div>
			<div class="clasbpro-reports-kpi">
				<span class="clasbpro-reports-kpi__label"><?php esc_html_e( 'Average lifetime value', 'class-bookings-with-stripe-pro' ); ?></span>
				<span class="clasbpro-reports-kpi__value"><?php echo esc_html( (string) $kpis['avg_ltv_display'] ); ?></span>
			</div>
			<div class="clasbpro-reports-kpi">
				<span class="clasbpro-reports-kpi__label"><?php esc_html_e( 'Repeat customer rate', 'class-bookings-with-stripe-pro' ); ?></span>
				<span class="clasbpro-reports-kpi__value"><?php echo esc_html( (string) $kpis['repeat_rate_display'] ); ?></span>
			</div>
			<div class="clasbpro-reports-kpi">
				<span class="clasbpro-reports-kpi__label"><?php esc_html_e( 'New customers (30 days)', 'class-bookings-with-stripe-pro' ); ?></span>
				<span class="clasbpro-reports-kpi__value"><?php echo esc_html( (string) $kpis['new_30d'] ); ?></span>
			</div>
		</section>

		<section class="clasbpro-reports-panel" aria-labelledby="clasbpro-reports-customers-heading">
			<div class="clasbpro-reports-panel__head clasbpro-reports-panel__head--split">
				<div class="clasbpro-reports-panel__head-main">
					<h2 id="clasbpro-reports-customers-heading" class="clasbpro-reports-panel__title"><?php esc_html_e( 'Customers', 'class-bookings-with-stripe-pro' ); ?></h2>
					<p class="clasbpro-reports-panel__desc"><?php esc_html_e( 'Paid bookings grouped by email. Click a column header to sort; search by name or email. Use “View bookings” to open that customer’s bookings.', 'class-bookings-with-stripe-pro' ); ?></p>
				</div>
				<div class="clasbpro-reports-customers-toolbar">
					<label class="screen-reader-text" for="clasbpro-reports-customers-search"><?php esc_html_e( 'Search customers', 'class-bookings-with-stripe-pro' ); ?></label>
					<input type="search" class="clasbpro-reports-customers-search" id="clasbpro-reports-customers-search" placeholder="<?php esc_attr_e( 'Search name or email…', 'class-bookings-with-stripe-pro' ); ?>" />
					<button type="button" class="button button-secondary clasbpro-reports-btn" id="clasbpro-reports-customers-csv"><?php esc_html_e( 'Download CSV', 'class-bookings-with-stripe-pro' ); ?></button>
				</div>
			</div>
			<div class="clasbpro-reports-table-scroll">
				<table class="widefat striped clasbpro-reports-table clasbpro-reports-customers-table">
					<thead>
					<tr>
						<th scope="col" data-sort-key="email"><?php esc_html_e( 'Email', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" data-sort-key="name"><?php esc_html_e( 'Name', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" data-sort-key="sessions"><?php esc_html_e( 'Classes booked', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" data-sort-key="total_spent"><?php esc_html_e( 'Total spent', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" data-sort-key="customer_since"><?php esc_html_e( 'Customer since', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" data-sort-key="tenure"><?php esc_html_e( 'Tenure', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" data-sort-key="last_booking"><?php esc_html_e( 'Last booking', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col" class="clasbpro-reports-customers-table__actions"><?php esc_html_e( 'Bookings', 'class-bookings-with-stripe-pro' ); ?></th>
					</tr>
					</thead>
					<tbody id="clasbpro-reports-customers-body"></tbody>
				</table>
			</div>
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	private static function reports_url( string $view, array $extra = [] ): string {
		$args = array_merge(
			[
				'post_type' => CPT::CLASS_PT,
				'page'      => 'clasbpro-reports',
				'view'      => $view,
			],
			$extra
		);
		if ( self::VIEW_SUMMARY === $view ) {
			unset( $args['view'] );
		}
		return admin_url( 'edit.php?' . http_build_query( $args ) );
	}

	private static function reports_view(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter UI.
		$requested = isset( $_GET['view'] ) ? sanitize_key( (string) wp_unslash( $_GET['view'] ) ) : self::VIEW_SUMMARY;
		$allowed   = [ self::VIEW_SUMMARY, self::VIEW_CLASSES, self::VIEW_BOOKINGS ];
		return in_array( $requested, $allowed, true ) ? $requested : self::VIEW_SUMMARY;
	}

	/**
	 * @return array<int, int>
	 */
	private static function published_class_ids(): array {
		$ids = get_posts(
			[
				'post_type'      => CPT::CLASS_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);
		return array_map( 'intval', $ids );
	}

	private static function selected_class_id(): int {
		$ids = self::published_class_ids();
		if ( empty( $ids ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter UI.
		$requested = isset( $_GET['class_id'] ) ? absint( (string) wp_unslash( $_GET['class_id'] ) ) : 0;
		if ( $requested > 0 && in_array( $requested, $ids, true ) ) {
			return $requested;
		}
		return (int) $ids[0];
	}

	/**
	 * @return array<int, array{class_id:int,class_date:string,email:string,name:string,seats:int,amount:int,created_gmt:string}>
	 */
	private static function paid_bookings_rows(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && isset( $cached['rows'] ) && is_array( $cached['rows'] ) ) {
			return $cached['rows'];
		}

		$query = new \WP_Query(
			[
				'post_type'      => CPT::BOOKING_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin reports only; bounded meta keys on booking CPT.
				'meta_query'     => [
					[
						'key'   => '_clasbpro_status',
						'value' => Bookings::STATUS_PAID,
					],
				],
			]
		);

		$rows = [];
		foreach ( $query->posts as $booking_id ) {
			$booking_id = (int) $booking_id;
			$class_id   = (int) get_post_meta( $booking_id, '_clasbpro_class_id', true );
			$class_date = (string) get_post_meta( $booking_id, '_clasbpro_class_date', true );
			$email      = strtolower( trim( (string) get_post_meta( $booking_id, '_clasbpro_customer_email', true ) ) );
			$name       = (string) get_post_meta( $booking_id, '_clasbpro_customer_name', true );
			$seats      = max( 1, (int) get_post_meta( $booking_id, '_clasbpro_seats', true ) );
			$amount     = (int) get_post_meta( $booking_id, '_clasbpro_amount_total', true );
			$created    = (string) get_post_meta( $booking_id, '_clasbpro_created_gmt', true );
			if ( '' === $created ) {
				$post    = get_post( $booking_id );
				$created = $post ? (string) $post->post_date_gmt : '';
			}
			if ( $class_id <= 0 ) {
				continue;
			}
			$rows[] = [
				'class_id'    => $class_id,
				'class_date'  => $class_date,
				'email'       => $email,
				'name'        => $name,
				'seats'       => $seats,
				'amount'      => $amount,
				'created_gmt' => $created,
			];
		}

		set_transient(
			self::CACHE_KEY,
			[
				'rows'  => $rows,
				'built' => time(),
			],
			self::CACHE_TTL
		);

		return $rows;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function class_metrics( int $class_id ): array {
		$class    = Helpers::get_class_data( $class_id );
		$capacity = (int) ( $class['capacity'] ?? 0 );
		$price    = (float) ( $class['price'] ?? 0.0 );
		$rows     = array_values(
			array_filter(
				self::paid_bookings_rows(),
				static fn( array $row ): bool => (int) $row['class_id'] === $class_id
			)
		);

		$total_students    = 0;
		$total_revenue     = 0;
		$unique_emails     = [];
		$session_seats     = [];
		$class_dates       = [];
		$today             = wp_date( 'Y-m-d' );
		$upcoming_sessions = 0;

		foreach ( $rows as $row ) {
			$total_students += (int) $row['seats'];
			$total_revenue  += (int) $row['amount'];
			if ( (string) $row['email'] !== '' && is_email( (string) $row['email'] ) ) {
				$unique_emails[ (string) $row['email'] ] = true;
			}
			$date = (string) $row['class_date'];
			if ( '' !== $date ) {
				$class_dates[] = $date;
				if ( ! isset( $session_seats[ $date ] ) ) {
					$session_seats[ $date ] = 0;
				}
				$session_seats[ $date ] += (int) $row['seats'];
			}
		}

		$occupancies = [];
		foreach ( $session_seats as $seats ) {
			if ( $capacity > 0 ) {
				$occupancies[] = min( 100, ( 100 * $seats ) / $capacity );
			}
		}
		$avg_occupancy = count( $occupancies ) > 0 ? round( array_sum( $occupancies ) / count( $occupancies ), 1 ) : 0.0;

		$class_dates = array_values( array_unique( array_filter( $class_dates ) ) );
		sort( $class_dates );
		$first_date = $class_dates[0] ?? '';
		$last_date  = $class_dates ? $class_dates[ count( $class_dates ) - 1 ] : '';

		foreach ( array_keys( $session_seats ) as $date ) {
			if ( $date >= $today ) {
				++$upcoming_sessions;
			}
		}

		return [
			'total_revenue'         => $total_revenue,
			'total_revenue_display' => Helpers::format_stripe_amount( $total_revenue ),
			'total_students'        => $total_students,
			'total_bookings'        => count( $rows ),
			'unique_customers'      => count( $unique_emails ),
			'avg_occupancy'         => $avg_occupancy,
			'avg_occupancy_display' => $capacity > 0 ? $avg_occupancy . '%' : '—',
			'price_display'         => Helpers::format_price( $price ),
			'first_booking_display' => '' !== $first_date ? Helpers::format_date( $first_date ) : '—',
			'last_booking_display'  => '' !== $last_date ? Helpers::format_date( $last_date ) : '—',
			'upcoming_sessions'     => $upcoming_sessions,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function class_charts_payload( int $class_id ): array {
		$class       = Helpers::get_class_data( $class_id );
		$capacity    = (int) ( $class['capacity'] ?? 0 );
		$months      = self::last_n_month_keys( self::CHART_MONTHS );
		$range_start = $months[0] . '-01';
		$range_end   = wp_date( 'Y-m-t' );

		$rows = array_values(
			array_filter(
				self::paid_bookings_rows(),
				static function ( array $row ) use ( $class_id, $range_start, $range_end ): bool {
					if ( (int) $row['class_id'] !== $class_id ) {
						return false;
					}
					$date = (string) $row['class_date'];
					return '' !== $date && $date >= $range_start && $date <= $range_end;
				}
			)
		);

		$students_monthly = array_fill_keys( $months, 0 );
		$revenue_monthly  = array_fill_keys( $months, 0 );
		$session_seats    = [];
		$dow_counts       = array_fill( 0, 7, 0 );
		$dow_labels       = [
			__( 'Mon', 'class-bookings-with-stripe-pro' ),
			__( 'Tue', 'class-bookings-with-stripe-pro' ),
			__( 'Wed', 'class-bookings-with-stripe-pro' ),
			__( 'Thu', 'class-bookings-with-stripe-pro' ),
			__( 'Fri', 'class-bookings-with-stripe-pro' ),
			__( 'Sat', 'class-bookings-with-stripe-pro' ),
			__( 'Sun', 'class-bookings-with-stripe-pro' ),
		];

		foreach ( $rows as $row ) {
			$date  = (string) $row['class_date'];
			$month = substr( $date, 0, 7 );
			if ( isset( $students_monthly[ $month ] ) ) {
				$students_monthly[ $month ] += (int) $row['seats'];
				$revenue_monthly[ $month ]  += Helpers::from_stripe_amount( (int) $row['amount'] );
			}
			if ( ! isset( $session_seats[ $date ] ) ) {
				$session_seats[ $date ] = 0;
			}
			$session_seats[ $date ] += (int) $row['seats'];

			$dow_index = (int) wp_date( 'N', strtotime( $date . ' 12:00:00' ) ) - 1;
			if ( $dow_index >= 0 && $dow_index < 7 ) {
				++$dow_counts[ $dow_index ];
			}
		}

		$cumulative = [];
		$running    = 0.0;
		foreach ( $months as $month ) {
			$running      += (float) $revenue_monthly[ $month ];
			$cumulative[]  = round( $running, 2 );
		}

		$occupancy = [];
		ksort( $session_seats );
		foreach ( $session_seats as $date => $seats ) {
			if ( $capacity <= 0 ) {
				continue;
			}
			$occupancy[] = [
				'x' => $date,
				'y' => round( min( 100, ( 100 * $seats ) / $capacity ), 1 ),
			];
		}

		return [
			'months'            => $months,
			'studentsMonthly'   => array_values( $students_monthly ),
			'revenueMonthly'    => array_values( $revenue_monthly ),
			'cumulativeRevenue' => $cumulative,
			'occupancy'         => $occupancy,
			'dowLabels'         => $dow_labels,
			'dowCounts'         => $dow_counts,
			'currency'          => Helpers::currency_format_config(),
			'i18n'              => [
				'students'          => __( 'Students', 'class-bookings-with-stripe-pro' ),
				'revenue'           => __( 'Revenue', 'class-bookings-with-stripe-pro' ),
				'cumulativeRevenue' => __( 'Cumulative revenue', 'class-bookings-with-stripe-pro' ),
				'occupancy'         => __( 'Occupancy', 'class-bookings-with-stripe-pro' ),
				'bookings'          => __( 'Bookings', 'class-bookings-with-stripe-pro' ),
				'dateAxis'          => __( 'Session date', 'class-bookings-with-stripe-pro' ),
			],
		];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function customer_report_rows(): array {
		$by_email = [];

		foreach ( self::paid_bookings_rows() as $row ) {
			$email = (string) $row['email'];
			if ( '' === $email || ! is_email( $email ) ) {
				continue;
			}
			if ( ! isset( $by_email[ $email ] ) ) {
				$by_email[ $email ] = [
					'email'         => $email,
					'name'          => '',
					'sessions'      => 0,
					'total_spent'   => 0,
					'first_booking' => '',
					'last_booking'  => '',
					'name_ts'       => 0,
				];
			}
			$by_email[ $email ]['sessions']++;
			$by_email[ $email ]['total_spent'] += (int) $row['amount'];

			$created = (string) $row['created_gmt'];
			if ( '' !== $created ) {
				if ( '' === $by_email[ $email ]['first_booking'] || $created < $by_email[ $email ]['first_booking'] ) {
					$by_email[ $email ]['first_booking'] = $created;
				}
				if ( '' === $by_email[ $email ]['last_booking'] || $created > $by_email[ $email ]['last_booking'] ) {
					$by_email[ $email ]['last_booking'] = $created;
				}
				$ts = strtotime( $created . ' UTC' );
				if ( false !== $ts && $ts >= $by_email[ $email ]['name_ts'] ) {
					$by_email[ $email ]['name_ts'] = $ts;
					$by_email[ $email ]['name']    = (string) $row['name'];
				}
			}
		}

		$out = [];
		foreach ( $by_email as $customer ) {
			unset( $customer['name_ts'] );
			$first_date = '' !== $customer['first_booking'] ? substr( $customer['first_booking'], 0, 10 ) : '';
			$last_date  = '' !== $customer['last_booking'] ? substr( $customer['last_booking'], 0, 10 ) : '';
			$customer['tenure']                 = self::humanize_duration( $customer['first_booking'] );
			$customer['customer_since_display'] = '' !== $first_date ? Helpers::format_date( $first_date ) : '—';
			$customer['last_booking_display']   = '' !== $last_date ? Helpers::format_date( $last_date ) : '—';
			$customer['total_spent_display']    = Helpers::format_stripe_amount( (int) $customer['total_spent'] );
			$out[]                              = $customer;
		}

		usort(
			$out,
			static fn( array $a, array $b ): int => (int) $b['total_spent'] <=> (int) $a['total_spent']
		);

		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $rows
	 * @return array<string, mixed>
	 */
	private static function customer_kpis( array $rows ): array {
		$total_customers = count( $rows );
		$total_revenue   = 0;
		$repeat          = 0;
		$cutoff          = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );
		$new_30d         = 0;

		foreach ( $rows as $row ) {
			$total_revenue += (int) $row['total_spent'];
			if ( (int) $row['sessions'] >= 2 ) {
				++$repeat;
			}
			if ( (string) $row['first_booking'] >= $cutoff ) {
				++$new_30d;
			}
		}

		$avg_ltv     = $total_customers > 0 ? (int) round( $total_revenue / $total_customers ) : 0;
		$repeat_rate = $total_customers > 0 ? round( ( 100 * $repeat ) / $total_customers, 1 ) : 0.0;

		return [
			'total_customers'       => $total_customers,
			'total_revenue_display' => Helpers::format_stripe_amount( $total_revenue ),
			'avg_ltv_display'       => Helpers::format_stripe_amount( $avg_ltv ),
			'repeat_rate_display'   => $repeat_rate . '%',
			'new_30d'               => $new_30d,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function customer_report_payload(): array {
		return [
			'rows' => self::customer_report_rows(),
			'bookingsListUrl' => CPT::bookings_list_url(),
			'i18n' => [
				'noResults'    => __( 'No customers match your search.', 'class-bookings-with-stripe-pro' ),
				'viewBookings' => __( 'View bookings', 'class-bookings-with-stripe-pro' ),
			],
			'csvHeaders' => [
				__( 'Email', 'class-bookings-with-stripe-pro' ),
				__( 'Name', 'class-bookings-with-stripe-pro' ),
				__( 'Classes booked', 'class-bookings-with-stripe-pro' ),
				__( 'Total spent', 'class-bookings-with-stripe-pro' ),
				__( 'Customer since', 'class-bookings-with-stripe-pro' ),
				__( 'Tenure', 'class-bookings-with-stripe-pro' ),
				__( 'Last booking', 'class-bookings-with-stripe-pro' ),
			],
			'csvFilename' => 'clasbpro-customers-' . wp_date( 'Y-m-d' ) . '.csv',
		];
	}

	/**
	 * @return array<int, string> Y-m keys, oldest first.
	 */
	private static function last_n_month_keys( int $count ): array {
		$months = [];
		for ( $i = $count - 1; $i >= 0; $i-- ) {
			$months[] = wp_date( 'Y-m', strtotime( '-' . $i . ' months' ) );
		}
		return $months;
	}

	private static function humanize_duration( string $from_gmt ): string {
		if ( '' === $from_gmt ) {
			return '—';
		}
		$from = strtotime( $from_gmt . ' UTC' );
		if ( false === $from ) {
			return '—';
		}
		$now  = time();
		$days = (int) floor( max( 0, $now - $from ) / DAY_IN_SECONDS );
		if ( $days < 30 ) {
			return sprintf(
				/* translators: %d: number of days */
				_n( '%d day', '%d days', $days, 'class-bookings-with-stripe-pro' ),
				$days
			);
		}
		$months = (int) floor( $days / 30 );
		if ( $months < 12 ) {
			return sprintf(
				/* translators: %d: number of months */
				_n( '%d mo', '%d mo', $months, 'class-bookings-with-stripe-pro' ),
				$months
			);
		}
		$years      = (int) floor( $months / 12 );
		$rem_months = $months % 12;
		if ( 0 === $rem_months ) {
			return sprintf(
				/* translators: %d: number of years */
				_n( '%d yr', '%d yr', $years, 'class-bookings-with-stripe-pro' ),
				$years
			);
		}
		return sprintf(
			/* translators: 1: years, 2: months */
			__( '%1$d yr %2$d mo', 'class-bookings-with-stripe-pro' ),
			$years,
			$rem_months
		);
	}
	private static function next_sessions( int $limit ): array {
		$classes = get_posts(
			[
				'post_type'      => CPT::CLASS_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);
		$sessions = [];
		foreach ( $classes as $class_id ) {
			$class = Helpers::get_class_data( (int) $class_id );
			if ( ! $class || empty( $class['class_active'] ) ) {
				continue;
			}
			$dates = self::upcoming_dates_for_class( $class, 3 );
			foreach ( $dates as $date ) {
				if ( '' === $date ) {
					continue;
				}
				$ts = strtotime( $date . ' ' . (string) $class['start_time'] );
				$sessions[] = [
					'class_id'    => (int) $class['id'],
					'class_name'  => (string) $class['name'],
					'date'        => $date,
					'start_time'  => (string) $class['start_time'],
					'capacity'    => (int) $class['capacity'],
					'ts'          => (int) ( $ts ?: 0 ),
				];
			}
		}

		usort(
			$sessions,
			static fn( array $a, array $b ): int => (int) $a['ts'] <=> (int) $b['ts']
		);
		return array_slice( $sessions, 0, max( 1, $limit ) );
	}

	private static function people_booked_count( int $class_id, string $date ): int {
		$total = 0;
		foreach ( self::bookings_for_session( $class_id, $date ) as $row ) {
			$total += (int) $row['seats'];
		}
		return $total;
	}

	/**
	 * @return array<int, array{class_id:int,class_name:string,sessions:array<int, array{class_id:int,class_name:string,date:string,start_time:string,capacity:int,ts:int}>}>
	 */
	private static function next_sessions_by_class( int $count_per_class ): array {
		$classes = get_posts(
			[
				'post_type'      => CPT::CLASS_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			]
		);
		$out = [];
		foreach ( $classes as $class_id ) {
			$class = Helpers::get_class_data( (int) $class_id );
			if ( ! $class || empty( $class['class_active'] ) ) {
				continue;
			}
			$sessions = [];
			foreach ( self::upcoming_dates_for_class( $class, $count_per_class ) as $date ) {
				if ( '' === $date ) {
					continue;
				}
				$ts = strtotime( $date . ' ' . (string) $class['start_time'] );
				$sessions[] = [
					'class_id'    => (int) $class['id'],
					'class_name'  => (string) $class['name'],
					'date'        => $date,
					'start_time'  => (string) $class['start_time'],
					'capacity'    => (int) $class['capacity'],
					'ts'          => (int) ( $ts ?: 0 ),
				];
			}
			$out[] = [
				'class_id'   => (int) $class['id'],
				'class_name' => (string) $class['name'],
				'sessions'   => $sessions,
			];
		}
		return $out;
	}

	/**
	 * Build upcoming session dates by combining:
	 * - next available dates, and
	 * - future dates that already have paid bookings.
	 *
	 * @param array<string,mixed> $class
	 * @return array<int,string> Y-m-d
	 */
	private static function upcoming_dates_for_class( array $class, int $limit ): array {
		$class_id = (int) ( $class['id'] ?? 0 );
		if ( $class_id <= 0 ) {
			return [];
		}

		$available = array_map(
			static fn( array $row ): string => (string) ( $row['date'] ?? '' ),
			Bookings::next_available_dates( $class, max( 3, $limit ) )
		);
		$booked = self::future_booked_dates_for_class( $class_id );
		$dates = array_values( array_unique( array_filter( array_merge( $available, $booked ) ) ) );
		usort(
			$dates,
			static fn( string $a, string $b ): int => strcmp( $a, $b )
		);
		return array_slice( $dates, 0, max( 1, $limit ) );
	}

	/**
	 * Future class dates with at least one paid booking.
	 *
	 * @return array<int,string> Y-m-d
	 */
	private static function future_booked_dates_for_class( int $class_id ): array {
		$today = wp_date( 'Y-m-d' );
		$query = new \WP_Query(
			[
				'post_type'      => CPT::BOOKING_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin reports only; bounded meta keys on booking CPT.
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => '_clasbpro_class_id',
						'value' => $class_id,
					],
					[
						'key'   => '_clasbpro_status',
						'value' => Bookings::STATUS_PAID,
					],
					[
						'key'     => '_clasbpro_class_date',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					],
				],
			]
		);
		$dates = [];
		foreach ( $query->posts as $booking_id ) {
			$date = (string) get_post_meta( (int) $booking_id, '_clasbpro_class_date', true );
			if ( '' !== $date ) {
				$dates[] = $date;
			}
		}
		return array_values( array_unique( $dates ) );
	}

	/**
	 * @return array<int,array{name:string,email:string,seats:int}>
	 */
	private static function bookings_for_session( int $class_id, string $date ): array {
		$query = new \WP_Query(
			[
				'post_type'      => CPT::BOOKING_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin reports only; bounded meta keys on booking CPT.
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => '_clasbpro_class_id',
						'value' => $class_id,
					],
					[
						'key'   => '_clasbpro_class_date',
						'value' => $date,
					],
					[
						'key'   => '_clasbpro_status',
						'value' => Bookings::STATUS_PAID,
					],
				],
			]
		);

		$rows = [];
		foreach ( $query->posts as $booking_id ) {
			$rows[] = [
				'name'  => (string) get_post_meta( (int) $booking_id, '_clasbpro_customer_name', true ),
				'email' => (string) get_post_meta( (int) $booking_id, '_clasbpro_customer_email', true ),
				'seats' => (int) get_post_meta( (int) $booking_id, '_clasbpro_seats', true ),
			];
		}
		return $rows;
	}

	/**
	 * Calendar year for the reports chart (GET `clasbowi_year`, clamped).
	 */
	private static function reports_year(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter UI.
		$requested = isset( $_GET['clasbowi_year'] ) ? absint( (string) wp_unslash( $_GET['clasbowi_year'] ) ) : 0;
		$current   = (int) wp_date( 'Y' );
		if ( $requested < 2000 || $requested > 2100 ) {
			$requested = $current;
		}
		if ( $requested > $current + 1 ) {
			$requested = $current + 1;
		}
		return $requested;
	}

	/**
	 * Every calendar day in a year (Y-m-d), inclusive.
	 *
	 * @return array<int, string>
	 */
	private static function dates_in_calendar_year( int $year ): array {
		$tz  = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
		$out = [];
		$d   = new \DateTimeImmutable( sprintf( '%d-01-01', $year ), $tz );
		$end = new \DateTimeImmutable( sprintf( '%d-12-31', $year ), $tz );
		while ( $d <= $end ) {
			$out[] = $d->format( 'Y-m-d' );
			$d     = $d->modify( '+1 day' );
		}
		return $out;
	}

	private static function chart_line_color( int $index, int $total ): string {
		if ( $total < 1 ) {
			return '#0e7490';
		}
		$hue = (int) round( ( $index * 360 / $total ) % 360 );
		return sprintf( 'hsl(%d, 58%%, 40%%)', $hue );
	}

	/**
	 * Chart.js payload: one line per Class with paid bookings in the year; points for each day Jan–Dec.
	 *
	 * @return array<string, mixed>
	 */
	private static function yearly_bookings_chart_data( int $year ): array {
		if ( isset( self::$yearly_chart_cache[ $year ] ) ) {
			return self::$yearly_chart_cache[ $year ];
		}

		$start = sprintf( '%d-01-01', $year );
		$end   = sprintf( '%d-12-31', $year );
		$dates = self::dates_in_calendar_year( $year );

		$i18n = [
			'studentsBooked' => __( 'Students booked', 'class-bookings-with-stripe-pro' ),
			'dateAxis'       => __( 'Date', 'class-bookings-with-stripe-pro' ),
			'resetZoom'      => __( 'Reset zoom', 'class-bookings-with-stripe-pro' ),
		];

		$query = new \WP_Query(
			[
				'post_type'      => CPT::BOOKING_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin reports only; bounded meta keys on booking CPT.
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => '_clasbpro_status',
						'value' => Bookings::STATUS_PAID,
					],
					[
						'key'     => '_clasbpro_class_date',
						'value'   => $start,
						'compare' => '>=',
						'type'    => 'DATE',
					],
					[
						'key'     => '_clasbpro_class_date',
						'value'   => $end,
						'compare' => '<=',
						'type'    => 'DATE',
					],
				],
			]
		);

		/** @var array<int, array<string, int>> $counts class_id => Y-m-d => seat sum */
		$counts = [];
		foreach ( $query->posts as $booking_id ) {
			$booking_id = (int) $booking_id;
			$class_id   = (int) get_post_meta( $booking_id, '_clasbpro_class_id', true );
			$class_date = (string) get_post_meta( $booking_id, '_clasbpro_class_date', true );
			$seats      = (int) get_post_meta( $booking_id, '_clasbpro_seats', true );
			if ( $class_id <= 0 || '' === $class_date ) {
				continue;
			}
			if ( ! isset( $counts[ $class_id ] ) ) {
				$counts[ $class_id ] = [];
			}
			if ( ! isset( $counts[ $class_id ][ $class_date ] ) ) {
				$counts[ $class_id ][ $class_date ] = 0;
			}
			$counts[ $class_id ][ $class_date ] += max( 1, $seats );
		}

		if ( empty( $counts ) ) {
			$payload = [
				'year'     => $year,
				'hasData'  => false,
				'xMin'     => $start,
				'xMax'     => $end,
				'datasets' => [],
				'i18n'     => $i18n,
			];
			self::$yearly_chart_cache[ $year ] = $payload;
			return $payload;
		}

		$class_ids = array_keys( $counts );
		usort(
			$class_ids,
			static function ( int $a, int $b ): int {
				return strcasecmp( get_the_title( $a ), get_the_title( $b ) );
			}
		);

		$total    = count( $class_ids );
		$datasets = [];
		$i        = 0;
		foreach ( $class_ids as $class_id ) {
			$title = get_the_title( $class_id );
			if ( '' === $title ) {
				$title = sprintf(
					/* translators: %d: Class post ID */
					__( 'Class #%d', 'class-bookings-with-stripe-pro' ),
					$class_id
				);
			}
			$points = [];
			foreach ( $dates as $d ) {
				$points[] = [
					'x' => $d,
					'y' => (int) ( $counts[ $class_id ][ $d ] ?? 0 ),
				];
			}
			$datasets[] = [
				'label'       => $title,
				'data'        => $points,
				'borderColor' => self::chart_line_color( $i, $total ),
			];
			++$i;
		}

		$payload = [
			'year'     => $year,
			'hasData'  => true,
			'xMin'     => $start,
			'xMax'     => $end,
			'datasets' => $datasets,
			'i18n'     => $i18n,
		];
		self::$yearly_chart_cache[ $year ] = $payload;
		return $payload;
	}
}
