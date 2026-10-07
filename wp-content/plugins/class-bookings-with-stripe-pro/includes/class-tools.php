<?php
/**
 * Admin Tools: export / import Clasbpro snapshots.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Tools {

	public const MENU_SLUG = 'clasbpro-tools';

	private static string $page_hook = '';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ], 30 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_filter( 'admin_body_class', [ self::class, 'filter_body_class' ] );
		add_action( 'admin_post_clasbpro_tools_export', [ self::class, 'handle_export' ] );
		add_action( 'admin_post_clasbpro_tools_preview', [ self::class, 'handle_preview' ] );
		add_action( 'admin_post_clasbpro_tools_import', [ self::class, 'handle_import' ] );
	}

	public static function register_menu(): void {
		self::$page_hook = (string) add_submenu_page(
			'edit.php?post_type=' . CPT::CLASS_PT,
			__( 'Tools', 'class-bookings-with-stripe-pro' ),
			__( 'Tools', 'class-bookings-with-stripe-pro' ),
			'manage_options',
			self::MENU_SLUG,
			[ self::class, 'render_page' ]
		);
	}

	/**
	 * @param string $classes Space-prefixed body classes.
	 */
	public static function filter_body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && $screen->id === CPT::CLASS_PT . '_page_' . self::MENU_SLUG ) {
			return $classes . ' clasbpro-tools';
		}
		return $classes;
	}

	public static function enqueue_assets( string $hook ): void {
		if ( '' === self::$page_hook || $hook !== self::$page_hook ) {
			return;
		}
		$path = CLASBOWPRO_DIR . 'assets/cbfs-tools-admin.css';
		wp_enqueue_style(
			'clasbpro-tools-admin',
			CLASBOWPRO_URL . 'assets/cbfs-tools-admin.css',
			[],
			is_readable( $path ) ? (string) filemtime( $path ) : CLASBOWPRO_VERSION
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice  = isset( $_GET['clasbpro_tools_notice'] ) ? sanitize_key( (string) wp_unslash( $_GET['clasbpro_tools_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preview = self::get_pending_preview();

		echo '<div class="wrap clasbpro-tools-wrap">';
		echo '<h1>' . esc_html__( 'Tools', 'class-bookings-with-stripe-pro' ) . '</h1>';
		echo '<p class="clasbpro-tools-lead">' . esc_html__( 'Move all Stripe Class Pro data between sites. Import replaces this site’s Clasbpro data.', 'class-bookings-with-stripe-pro' ) . '</p>';

		if ( 'imported' === $notice ) {
			$backup = isset( $_GET['backup'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['backup'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success"><p>';
			esc_html_e( 'Import finished. Clasbpro data was replaced from the zip.', 'class-bookings-with-stripe-pro' );
			if ( $backup ) {
				echo '<br>';
				printf(
					/* translators: %s: backup file name */
					esc_html__( 'A copy of the previous data was saved as %s in uploads/clasbpro-backups/.', 'class-bookings-with-stripe-pro' ),
					'<code>' . esc_html( basename( $backup ) ) . '</code>'
				);
			}
			echo '</p></div>';
		} elseif ( 'export-failed' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Export failed.', 'class-bookings-with-stripe-pro' ) . '</p></div>';
		} elseif ( 'import-failed' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Import failed. The pre-import backup (if created) is in uploads/clasbpro-backups/.', 'class-bookings-with-stripe-pro' ) . '</p></div>';
		}

		self::render_export_card();
		if ( $preview ) {
			self::render_preview_card( $preview );
		} else {
			self::render_upload_card();
		}

		echo '</div>';
	}

	private static function render_export_card(): void {
		?>
		<section class="clasbpro-tools-card">
			<h2><?php esc_html_e( 'Export', 'class-bookings-with-stripe-pro' ); ?></h2>
			<p><?php esc_html_e( 'Download a zip of classes, bookings, coupons, settings, HTML emails, media, result pages, and the scheduled-email queue. Stripe and Mailchimp secrets are not included.', 'class-bookings-with-stripe-pro' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="clasbpro_tools_export">
				<?php wp_nonce_field( 'clasbpro_tools_export' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Download export zip', 'class-bookings-with-stripe-pro' ); ?></button>
			</form>
		</section>
		<?php
	}

	private static function render_upload_card(): void {
		?>
		<section class="clasbpro-tools-card">
			<h2><?php esc_html_e( 'Import', 'class-bookings-with-stripe-pro' ); ?></h2>
			<p><?php esc_html_e( 'Upload a zip from another Clasbpro site. You will see a preview and must type REPLACE before anything is deleted.', 'class-bookings-with-stripe-pro' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="clasbpro_tools_preview">
				<?php wp_nonce_field( 'clasbpro_tools_preview' ); ?>
				<p>
					<label for="clasbpro-tools-zip"><?php esc_html_e( 'Export zip', 'class-bookings-with-stripe-pro' ); ?></label><br>
					<input type="file" id="clasbpro-tools-zip" name="clasbpro_zip" accept=".zip,application/zip" required>
				</p>
				<button type="submit" class="button"><?php esc_html_e( 'Upload and preview', 'class-bookings-with-stripe-pro' ); ?></button>
			</form>
		</section>
		<?php
	}

	/**
	 * @param array{inspect: array<string, mixed>, file: string} $preview
	 */
	private static function render_preview_card( array $preview ): void {
		$inspect = $preview['inspect'];
		$counts  = is_array( $inspect['counts'] ?? null ) ? $inspect['counts'] : [];
		$rewrite = is_array( $inspect['url_rewrite'] ?? null ) ? $inspect['url_rewrite'] : [];
		?>
		<section class="clasbpro-tools-card clasbpro-tools-card--preview">
			<h2><?php esc_html_e( 'Import preview', 'class-bookings-with-stripe-pro' ); ?></h2>
			<p class="clasbpro-tools-warning">
				<?php esc_html_e( 'This will delete all Clasbpro classes, bookings, coupons, plugin settings (except secrets), and the scheduled-email queue on this site, then restore the zip. A backup zip is written first.', 'class-bookings-with-stripe-pro' ); ?>
			</p>
			<?php if ( ! empty( $inspect['warnings'] ) && is_array( $inspect['warnings'] ) ) : ?>
				<ul class="clasbpro-tools-warnings">
					<?php foreach ( $inspect['warnings'] as $warning ) : ?>
						<li><?php echo esc_html( (string) $warning ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<table class="widefat striped clasbpro-tools-counts">
				<tbody>
					<tr><th><?php esc_html_e( 'Classes', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['classes'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Bookings', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['bookings'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Coupon packs', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['packs'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Coupon purchases', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['pack_purchases'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Coupon codes', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['coupons'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Media files', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['media'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Scheduled emails', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['queue'] ?? 0 ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Pending scheduled emails', 'class-bookings-with-stripe-pro' ); ?></th><td><?php echo esc_html( (string) ( $counts['pending_queue'] ?? 0 ) ); ?></td></tr>
				</tbody>
			</table>
			<p>
				<strong><?php esc_html_e( 'Site URLs in the data will be rewritten:', 'class-bookings-with-stripe-pro' ); ?></strong><br>
				<code><?php echo esc_html( (string) ( $rewrite['from'] ?? '' ) ); ?></code>
				→
				<code><?php echo esc_html( (string) ( $rewrite['to'] ?? '' ) ); ?></code>
			</p>
			<?php if ( ! empty( $inspect['will_apply'] ) ) : ?>
				<h3><?php esc_html_e( 'Will apply', 'class-bookings-with-stripe-pro' ); ?></h3>
				<ul>
					<?php foreach ( (array) $inspect['will_apply'] as $line ) : ?>
						<li><?php echo esc_html( (string) $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $inspect['will_skip'] ) ) : ?>
				<h3><?php esc_html_e( 'Will skip', 'class-bookings-with-stripe-pro' ); ?></h3>
				<ul>
					<?php foreach ( (array) $inspect['will_skip'] as $line ) : ?>
						<li><?php echo esc_html( (string) $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="clasbpro_tools_import">
				<?php wp_nonce_field( 'clasbpro_tools_import' ); ?>
				<p>
					<label for="clasbpro-tools-confirm">
						<?php esc_html_e( 'Type REPLACE to confirm', 'class-bookings-with-stripe-pro' ); ?>
					</label><br>
					<input type="text" id="clasbpro-tools-confirm" name="clasbpro_confirm" autocomplete="off" required>
				</p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Replace Clasbpro data', 'class-bookings-with-stripe-pro' ); ?></button>
				<a class="button" href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( 'Cancel', 'class-bookings-with-stripe-pro' ); ?></a>
			</form>
		</section>
		<?php
	}

	public static function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}
		check_admin_referer( 'clasbpro_tools_export' );

		$path = Snapshot::export_to_temp_zip();
		if ( is_wp_error( $path ) ) {
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'export-failed', self::page_url() ) );
			exit;
		}

		$name = 'clasbpro-export-' . gmdate( 'Y-m-d-His' ) . '.zip';
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		readfile( $path );
		wp_delete_file( $path );
		exit;
	}

	public static function handle_preview(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}
		check_admin_referer( 'clasbpro_tools_preview' );

		$file = $_FILES['clasbpro_zip'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'import-failed', self::page_url() ) );
			exit;
		}

		$dest = Snapshot::backup_dir() . 'pending-import-' . get_current_user_id() . '.zip';
		if ( ! move_uploaded_file( (string) $file['tmp_name'], $dest ) ) {
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'import-failed', self::page_url() ) );
			exit;
		}

		$inspect = Snapshot::inspect( $dest );
		if ( is_wp_error( $inspect ) ) {
			wp_delete_file( $dest );
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'import-failed', self::page_url() ) );
			exit;
		}

		set_transient(
			self::preview_key(),
			[
				'file'    => $dest,
				'inspect' => $inspect,
			],
			HOUR_IN_SECONDS
		);

		wp_safe_redirect( self::page_url() );
		exit;
	}

	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}
		check_admin_referer( 'clasbpro_tools_import' );

		$typed = isset( $_POST['clasbpro_confirm'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['clasbpro_confirm'] ) ) : '';
		if ( 'REPLACE' !== $typed ) {
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'import-failed', self::page_url() ) );
			exit;
		}

		$preview = self::get_pending_preview();
		$file    = is_array( $preview ) ? (string) ( $preview['file'] ?? '' ) : '';
		if ( '' === $file || ! is_readable( $file ) ) {
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'import-failed', self::page_url() ) );
			exit;
		}

		$result = Snapshot::import_from_zip(
			$file,
			[
				'author_id'    => get_current_user_id(),
				'force_backup' => true,
				'confirm'      => true,
			]
		);

		delete_transient( self::preview_key() );
		wp_delete_file( $file );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'clasbpro_tools_notice', 'import-failed', self::page_url() ) );
			exit;
		}

		$backup = (string) ( $result['backup'] ?? '' );
		wp_safe_redirect(
			add_query_arg(
				[
					'clasbpro_tools_notice' => 'imported',
					'backup'                => $backup ? basename( $backup ) : '',
				],
				self::page_url()
			)
		);
		exit;
	}

	/**
	 * @return array{file: string, inspect: array<string, mixed>}|null
	 */
	private static function get_pending_preview(): ?array {
		$stored = get_transient( self::preview_key() );
		if ( ! is_array( $stored ) || empty( $stored['file'] ) || empty( $stored['inspect'] ) ) {
			return null;
		}
		if ( ! is_readable( (string) $stored['file'] ) ) {
			return null;
		}
		return [
			'file'    => (string) $stored['file'],
			'inspect' => (array) $stored['inspect'],
		];
	}

	private static function preview_key(): string {
		return 'clasbpro_tools_preview_' . get_current_user_id();
	}

	public static function page_url(): string {
		return admin_url( 'edit.php?post_type=' . CPT::CLASS_PT . '&page=' . self::MENU_SLUG );
	}
}
