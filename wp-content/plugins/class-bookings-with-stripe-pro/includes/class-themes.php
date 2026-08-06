<?php
/**
 * Admin Themes gallery screen.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Themes {

	private static string $page_hook = '';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_action( 'admin_post_clasbpro_theme_enable', [ self::class, 'handle_enable' ] );
		add_action( 'admin_post_clasbpro_theme_set_source', [ self::class, 'handle_set_source' ] );
		add_action( 'admin_post_clasbpro_theme_install', [ self::class, 'handle_install' ] );
		add_action( 'admin_post_clasbpro_theme_switch_files', [ self::class, 'handle_switch_files' ] );
		add_action( 'admin_post_clasbpro_theme_download', [ self::class, 'handle_download' ] );
		add_action( 'wp_ajax_clasbpro_theme_file', [ self::class, 'ajax_file_content' ] );
		add_action( 'wp_ajax_clasbpro_theme_preview_url', [ self::class, 'ajax_preview_url' ] );
	}

	public static function capability(): string {
		return (string) apply_filters( 'clasbpro_themes_capability', 'manage_options' );
	}

	public static function register_menu(): void {
		self::$page_hook = (string) add_submenu_page(
			'edit.php?post_type=' . CPT::CLASS_PT,
			__( 'Form Themes', 'class-bookings-with-stripe-pro' ),
			__( 'Themes', 'class-bookings-with-stripe-pro' ),
			self::capability(),
			'clasbpro-themes',
			[ self::class, 'render_page' ]
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( '' === self::$page_hook || $hook !== self::$page_hook ) {
			return;
		}

		wp_enqueue_style(
			'clasbpro-hljs-github-dark',
			CLASBOWPRO_URL . 'assets/vendor/highlight-github-dark.min.css',
			[],
			'11.9.0'
		);

		wp_enqueue_style(
			'clasbpro-themes-admin',
			CLASBOWPRO_URL . 'assets/cbfs-themes-admin.css',
			[ 'clasbpro-hljs-github-dark' ],
			CLASBOWPRO_VERSION
		);

		wp_enqueue_script(
			'clasbpro-hljs',
			CLASBOWPRO_URL . 'assets/vendor/highlight.min.js',
			[],
			'11.9.0',
			true
		);

		$hljs_langs = [ 'php', 'css', 'json', 'xml' ];
		$hljs_deps  = [ 'clasbpro-hljs' ];
		foreach ( $hljs_langs as $lang ) {
			$handle = 'clasbpro-hljs-' . $lang;
			wp_enqueue_script(
				$handle,
				CLASBOWPRO_URL . 'assets/vendor/highlight-' . $lang . '.min.js',
				$hljs_deps,
				'11.9.0',
				true
			);
			$hljs_deps[] = $handle;
		}

		wp_enqueue_script(
			'clasbpro-themes-admin',
			CLASBOWPRO_URL . 'assets/cbfs-themes-admin.js',
			$hljs_deps,
			CLASBOWPRO_VERSION,
			true
		);

		$theme_data = [];
		foreach ( Theme_Registry::all() as $slug => $theme ) {
			$theme_data[ $slug ] = [
				'name'  => (string) ( $theme['name'] ?? $slug ),
				'files' => Theme_Registry::list_files( $slug ),
			];
		}

		wp_localize_script(
			'clasbpro-themes-admin',
			'clasbproThemes',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'clasbpro_theme_file' ),
				'previewNonce'   => wp_create_nonce( 'clasbpro_theme_preview' ),
				'previewClasses' => Theme_Preview::list_preview_classes(),
				'themes'         => $theme_data,
				'i18n'         => [
					'copied'       => __( 'Copied!', 'class-bookings-with-stripe-pro' ),
					'copy'         => __( 'Copy', 'class-bookings-with-stripe-pro' ),
					'loading'      => __( 'Loading…', 'class-bookings-with-stripe-pro' ),
					'error'        => __( 'Could not load file.', 'class-bookings-with-stripe-pro' ),
					'close'        => __( 'Close', 'class-bookings-with-stripe-pro' ),
					'filesTitle'    => __( 'Theme files', 'class-bookings-with-stripe-pro' ),
					'selectFile'    => __( 'Select a file to view its source.', 'class-bookings-with-stripe-pro' ),
					'previewTitle'   => __( 'Live preview', 'class-bookings-with-stripe-pro' ),
					'previewLoading' => __( 'Loading preview…', 'class-bookings-with-stripe-pro' ),
					'previewError'   => __( 'Preview could not be loaded.', 'class-bookings-with-stripe-pro' ),
					'previewRetry'   => __( 'Try again', 'class-bookings-with-stripe-pro' ),
					'previewData'    => __( 'Test data', 'class-bookings-with-stripe-pro' ),
					'themeSingular'  => __( '%d theme', 'class-bookings-with-stripe-pro' ),
					'themePlural'    => __( '%d themes', 'class-bookings-with-stripe-pro' ),
				],
			]
		);
	}

	public static function page_url( array $args = [] ): string {
		return add_query_arg( $args, admin_url( 'admin.php?page=clasbpro-themes' ) );
	}

	public static function handle_enable(): void {
		self::verify_admin( 'clasbpro_theme_enable' );

		$slug = sanitize_key( (string) ( $_POST['theme'] ?? '' ) );
		if ( ! Theme_Registry::exists( $slug ) ) {
			wp_die( esc_html__( 'Invalid theme.', 'class-bookings-with-stripe-pro' ) );
		}

		Theme_Loader::update_settings( [
			'theme_source'         => 'gallery',
			'active_gallery_theme' => $slug,
		] );

		self::redirect_with_notice( 'enabled', $slug );
	}

	public static function handle_set_source(): void {
		self::verify_admin( 'clasbpro_theme_set_source' );

		$source = sanitize_key( (string) ( $_POST['theme_source'] ?? 'default' ) );
		if ( ! in_array( $source, [ 'default', 'gallery', 'theme' ], true ) ) {
			$source = 'default';
		}

		Theme_Loader::update_settings( [ 'theme_source' => $source ] );

		self::redirect_with_notice( 'source_updated' );
	}

	public static function handle_install(): void {
		self::verify_admin( 'clasbpro_theme_install' );

		$slug    = sanitize_key( (string) ( $_POST['theme'] ?? '' ) );
		$confirm = ! empty( $_POST['confirm'] );

		if ( ! Theme_Registry::exists( $slug ) ) {
			wp_die( esc_html__( 'Invalid theme.', 'class-bookings-with-stripe-pro' ) );
		}

		if ( ! $confirm ) {
			wp_safe_redirect( self::page_url( [
				'action' => 'install_confirm',
				'theme'  => $slug,
			] ) );
			exit;
		}

		$result = Theme_Installer::install( $slug, true );
		if ( ! $result['success'] ) {
			self::redirect_with_notice( 'install_error', $slug, $result['message'] );
		}

		set_transient(
			'clasbpro_theme_install_prompt_' . get_current_user_id(),
			$slug,
			MINUTE_IN_SECONDS * 10
		);

		self::redirect_with_notice( 'installed', $slug );
	}

	public static function handle_switch_files(): void {
		self::verify_admin( 'clasbpro_theme_switch_files' );

		Theme_Loader::update_settings( [ 'theme_source' => 'theme' ] );
		delete_transient( 'clasbpro_theme_install_prompt_' . get_current_user_id() );

		self::redirect_with_notice( 'source_theme' );
	}

	public static function handle_download(): void {
		$slug = sanitize_key( (string) ( $_GET['theme'] ?? '' ) );
		if ( ! wp_verify_nonce( (string) ( $_GET['_wpnonce'] ?? '' ), 'clasbpro_theme_download_' . $slug ) ) {
			wp_die( esc_html__( 'Invalid request.', 'class-bookings-with-stripe-pro' ) );
		}

		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}

		$tmp = Theme_Installer::build_zip( $slug );
		if ( ! $tmp || ! is_readable( $tmp ) ) {
			wp_die( esc_html__( 'Could not build ZIP archive.', 'class-bookings-with-stripe-pro' ) );
		}

		$theme = Theme_Registry::get( $slug );
		$name  = sanitize_file_name( (string) ( $theme['name'] ?? $slug ) );

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $name . '.zip"' );
		header( 'Content-Length: ' . (string) filesize( $tmp ) );
		readfile( $tmp );
		wp_delete_file( $tmp );
		exit;
	}

	public static function ajax_preview_url(): void {
		check_ajax_referer( 'clasbpro_theme_preview', 'nonce' );

		if ( ! current_user_can( self::capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) ], 403 );
		}

		$slug = sanitize_key( (string) ( $_POST['theme'] ?? '' ) );
		if ( ! Theme_Registry::exists( $slug ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid theme.', 'class-bookings-with-stripe-pro' ) ] );
		}

		$class_source = Theme_Preview::normalise_class_source( sanitize_text_field( (string) ( $_POST['class'] ?? 'default' ) ) );

		$url = Theme_Preview::preview_url( $slug, true, $class_source );
		$url = add_query_arg( '_cb', (string) time(), $url );

		wp_send_json_success( [ 'url' => esc_url_raw( $url ) ] );
	}

	public static function ajax_file_content(): void {
		check_ajax_referer( 'clasbpro_theme_file', 'nonce' );

		if ( ! current_user_can( self::capability() ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) ], 403 );
		}

		$slug     = sanitize_key( (string) ( $_POST['theme'] ?? '' ) );
		$relative = sanitize_text_field( (string) ( $_POST['file'] ?? '' ) );

		if ( ! Theme_Registry::exists( $slug ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid theme.', 'class-bookings-with-stripe-pro' ) ] );
		}

		$path = Theme_Registry::safe_pack_path( $slug, $relative );
		if ( '' === $path ) {
			wp_send_json_error( [ 'message' => __( 'File not found.', 'class-bookings-with-stripe-pro' ) ] );
		}

		$ext = strtolower( pathinfo( $relative, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'zip' ], true ) ) {
			wp_send_json_success( [
				'content' => __( '(Binary file — use Download ZIP or Install to theme.)', 'class-bookings-with-stripe-pro' ),
				'file'    => $relative,
			] );
		}

		$content = Theme_Registry::read_file( $slug, $relative );

		wp_send_json_success( [
			'content' => $content,
			'file'    => $relative,
		] );
	}

	private static function verify_admin( string $action ): void {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}

		check_admin_referer( $action );
	}

	private static function redirect_with_notice( string $code, string $slug = '', string $message = '' ): void {
		$args = [ 'clasbpro_notice' => $code ];
		if ( '' !== $slug ) {
			$args['theme'] = $slug;
		}
		if ( '' !== $message ) {
			$args['clasbpro_message'] = rawurlencode( $message );
		}

		wp_safe_redirect( self::page_url( $args ) );
		exit;
	}

	public static function render_page(): void {
		if ( ! current_user_can( self::capability() ) ) {
			return;
		}

		$action = sanitize_key( (string) ( $_GET['action'] ?? '' ) );
		if ( 'install_confirm' === $action ) {
			self::render_install_confirm();
			return;
		}

		$settings   = Theme_Loader::get_settings();
		$file_status = Theme_Loader::theme_files_status();
		$themes     = Theme_Registry::all();
		$active     = (string) $settings['active_gallery_theme'];
		$source     = (string) $settings['theme_source'];

		self::render_notices();

		?>
		<div class="wrap clasbpro-themes-wrap">
			<h1><?php esc_html_e( 'Form Themes', 'class-bookings-with-stripe-pro' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Switch booking form layout and styling, install packs into your theme, or browse source code.', 'class-bookings-with-stripe-pro' ); ?>
			</p>

			<?php self::render_source_banner( $source, $file_status, $active ); ?>

			<?php if ( empty( $themes ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'No theme packs found.', 'class-bookings-with-stripe-pro' ); ?></p></div>
			<?php else : ?>
				<?php self::render_gallery_toolbar( $themes ); ?>
				<div class="clasbpro-themes-grid" id="clasbpro-themes-grid">
					<?php foreach ( $themes as $slug => $theme ) : ?>
						<?php self::render_theme_card( $slug, $theme, $source, $active ); ?>
					<?php endforeach; ?>
				</div>
				<p class="clasbpro-themes-empty" id="clasbpro-themes-empty" hidden>
					<?php esc_html_e( 'No themes match your search or filters.', 'class-bookings-with-stripe-pro' ); ?>
				</p>
				<?php self::render_files_modal(); ?>
				<?php self::render_preview_modal(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_files_modal(): void {
		?>
		<div id="clasbpro-theme-files-modal" class="clasbpro-theme-files-modal" hidden>
			<div class="clasbpro-theme-files-modal__backdrop" data-close-files-modal></div>
			<div
				class="clasbpro-theme-files-modal__dialog"
				role="dialog"
				aria-modal="true"
				aria-labelledby="clasbpro-theme-files-modal-title"
			>
				<header class="clasbpro-theme-files-modal__header">
					<h2 id="clasbpro-theme-files-modal-title" class="clasbpro-theme-files-modal__title"></h2>
					<button
						type="button"
						class="clasbpro-theme-files-modal__close"
						data-close-files-modal
						aria-label="<?php esc_attr_e( 'Close', 'class-bookings-with-stripe-pro' ); ?>"
					>&times;</button>
				</header>
				<div class="clasbpro-theme-files-modal__body">
					<aside class="clasbpro-theme-files-modal__sidebar" aria-label="<?php esc_attr_e( 'Theme files', 'class-bookings-with-stripe-pro' ); ?>">
						<ul class="clasbpro-theme-files-list"></ul>
					</aside>
					<div class="clasbpro-theme-files-modal__editor">
						<div class="clasbpro-theme-code-toolbar">
							<code class="clasbpro-theme-code-filename"></code>
							<button type="button" class="button button-small clasbpro-theme-copy-btn" disabled>
								<?php esc_html_e( 'Copy', 'class-bookings-with-stripe-pro' ); ?>
							</button>
						</div>
						<pre class="clasbpro-theme-code-pre"><code class="clasbpro-theme-code-content"></code></pre>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_preview_modal(): void {
		?>
		<div id="clasbpro-theme-preview-modal" class="clasbpro-theme-preview-modal" hidden>
			<div class="clasbpro-theme-preview-modal__backdrop" data-close-preview-modal></div>
			<div
				class="clasbpro-theme-preview-modal__dialog"
				role="dialog"
				aria-modal="true"
				aria-labelledby="clasbpro-theme-preview-modal-title"
			>
				<header class="clasbpro-theme-preview-modal__header">
					<div class="clasbpro-theme-preview-modal__header-main">
						<h2 id="clasbpro-theme-preview-modal-title" class="clasbpro-theme-preview-modal__title"></h2>
						<label class="clasbpro-theme-preview-modal__data-label">
							<span class="clasbpro-theme-preview-modal__data-text"><?php esc_html_e( 'Test data', 'class-bookings-with-stripe-pro' ); ?></span>
							<select id="clasbpro-theme-preview-class" class="clasbpro-theme-preview-modal__data-select"></select>
						</label>
					</div>
					<button
						type="button"
						class="clasbpro-theme-preview-modal__close"
						data-close-preview-modal
						aria-label="<?php esc_attr_e( 'Close', 'class-bookings-with-stripe-pro' ); ?>"
					>&times;</button>
				</header>
				<div class="clasbpro-theme-preview-modal__body">
					<div class="clasbpro-theme-preview-modal__status" aria-live="polite">
						<p class="clasbpro-theme-preview-modal__loading"><?php esc_html_e( 'Loading preview…', 'class-bookings-with-stripe-pro' ); ?></p>
						<div class="clasbpro-theme-preview-modal__error" hidden>
							<p class="clasbpro-theme-preview-modal__error-text"></p>
							<button type="button" class="button button-primary clasbpro-theme-preview-retry">
								<?php esc_html_e( 'Try again', 'class-bookings-with-stripe-pro' ); ?>
							</button>
						</div>
					</div>
					<iframe
						class="clasbpro-theme-preview-modal__iframe"
						title="<?php esc_attr_e( 'Booking form preview', 'class-bookings-with-stripe-pro' ); ?>"
						referrerpolicy="strict-origin-when-cross-origin"
					></iframe>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $file_status
	 */
	private static function render_source_banner( string $source, array $file_status, string $active_slug ): void {
		$options = [
			'default' => __( 'Plugin default', 'class-bookings-with-stripe-pro' ),
			'gallery' => __( 'Gallery theme', 'class-bookings-with-stripe-pro' ),
			'theme'   => __( 'Theme files', 'class-bookings-with-stripe-pro' ),
		];
		$gallery_name = '' !== $active_slug
			? (string) ( Theme_Registry::get( $active_slug )['name'] ?? $active_slug )
			: '';
		?>
		<div class="clasbpro-themes-source-banner">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="clasbpro-themes-source-form">
				<input type="hidden" name="action" value="clasbpro_theme_set_source">
				<?php wp_nonce_field( 'clasbpro_theme_set_source' ); ?>

				<div class="clasbpro-themes-source-control">
					<span id="clasbpro-themes-source-label" class="clasbpro-themes-source-control__label">
						<?php esc_html_e( 'Active theme source', 'class-bookings-with-stripe-pro' ); ?>
					</span>
					<div class="clasbpro-themes-source-button-group" role="radiogroup" aria-labelledby="clasbpro-themes-source-label">
						<?php foreach ( $options as $value => $label ) : ?>
							<label class="clasbpro-themes-source-button-group__option<?php echo $source === $value ? ' is-selected' : ''; ?>">
								<input type="radio" name="theme_source" value="<?php echo esc_attr( $value ); ?>" <?php checked( $source, $value ); ?>>
								<span class="clasbpro-themes-source-button-group__text">
									<?php echo esc_html( $label ); ?>
									<?php if ( 'gallery' === $value && '' !== $gallery_name ) : ?>
										<span class="clasbpro-themes-source-button-group__meta"><?php echo esc_html( $gallery_name ); ?></span>
									<?php endif; ?>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</form>

			<?php if ( $file_status['exists'] ) : ?>
				<p class="clasbpro-themes-files-status">
					<?php
					printf(
						/* translators: 1: file count, 2: directory path */
						esc_html__( '%1$d file(s) in %2$s', 'class-bookings-with-stripe-pro' ),
						(int) $file_status['file_count'],
						esc_html( (string) $file_status['path'] )
					);
					?>
				</p>
			<?php else : ?>
				<p class="clasbpro-themes-files-status clasbpro-themes-files-status--empty">
					<?php esc_html_e( 'No theme overrides installed yet.', 'class-bookings-with-stripe-pro' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, array<string, mixed>> $themes
	 */
	private static function render_gallery_toolbar( array $themes ): void {
		$tags = Theme_Registry::all_tags();
		$total = count( $themes );
		?>
		<div class="clasbpro-themes-toolbar" id="clasbpro-themes-toolbar">
			<div class="clasbpro-themes-toolbar__row">
				<div class="clasbpro-themes-toolbar__search">
					<label for="clasbpro-themes-search" class="screen-reader-text">
						<?php esc_html_e( 'Search themes', 'class-bookings-with-stripe-pro' ); ?>
					</label>
					<input
						type="search"
						id="clasbpro-themes-search"
						class="clasbpro-themes-toolbar__search-input"
						placeholder="<?php esc_attr_e( 'Search themes…', 'class-bookings-with-stripe-pro' ); ?>"
						autocomplete="off"
					>
				</div>
				<div class="clasbpro-themes-toolbar__sort">
					<label for="clasbpro-themes-sort">
						<?php esc_html_e( 'Sort by', 'class-bookings-with-stripe-pro' ); ?>
						<select id="clasbpro-themes-sort" class="clasbpro-themes-toolbar__sort-select">
							<option value="name-asc"><?php esc_html_e( 'Name (A–Z)', 'class-bookings-with-stripe-pro' ); ?></option>
							<option value="name-desc"><?php esc_html_e( 'Name (Z–A)', 'class-bookings-with-stripe-pro' ); ?></option>
							<option value="slug-asc"><?php esc_html_e( 'Slug (A–Z)', 'class-bookings-with-stripe-pro' ); ?></option>
						</select>
					</label>
				</div>
				<p class="clasbpro-themes-toolbar__count" id="clasbpro-themes-count" aria-live="polite">
					<?php
					printf(
						/* translators: %d: number of themes shown */
						esc_html( _n( '%d theme', '%d themes', $total, 'class-bookings-with-stripe-pro' ) ),
						(int) $total
					);
					?>
				</p>
			</div>
			<?php if ( ! empty( $tags ) ) : ?>
				<div class="clasbpro-themes-toolbar__tags" role="group" aria-label="<?php esc_attr_e( 'Filter by tag', 'class-bookings-with-stripe-pro' ); ?>">
					<button
						type="button"
						class="clasbpro-themes-tag is-active"
						data-tag=""
						aria-pressed="true"
					>
						<?php esc_html_e( 'All', 'class-bookings-with-stripe-pro' ); ?>
					</button>
					<?php foreach ( $tags as $tag ) : ?>
						<button
							type="button"
							class="clasbpro-themes-tag"
							data-tag="<?php echo esc_attr( $tag ); ?>"
							aria-pressed="false"
						>
							<?php echo esc_html( Theme_Registry::tag_label( $tag ) ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $theme
	 */
	private static function render_theme_card( string $slug, array $theme, string $source, string $active ): void {
		$is_active  = 'gallery' === $source && $active === $slug;
		$screenshot = Theme_Registry::screenshot_url( $slug );
		$provides   = $theme['provides'] ?? [];
		$tags       = Theme_Registry::normalise_tags( $theme['tags'] ?? [] );
		$name       = (string) ( $theme['name'] ?? $slug );
		$desc       = (string) ( $theme['description'] ?? '' );
		$search     = strtolower( $slug . ' ' . $name . ' ' . $desc . ' ' . implode( ' ', $tags ) );
		?>
		<article
			class="clasbpro-theme-card<?php echo $is_active ? ' is-active' : ''; ?>"
			data-theme="<?php echo esc_attr( $slug ); ?>"
			data-slug="<?php echo esc_attr( $slug ); ?>"
			data-name="<?php echo esc_attr( strtolower( $name ) ); ?>"
			data-tags="<?php echo esc_attr( implode( ',', $tags ) ); ?>"
			data-search="<?php echo esc_attr( $search ); ?>"
		>
			<div class="clasbpro-theme-card__shot">
				<?php if ( $screenshot ) : ?>
					<img src="<?php echo esc_url( $screenshot ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<div class="clasbpro-theme-card__shot-placeholder" aria-hidden="true"></div>
				<?php endif; ?>
				<?php if ( $is_active ) : ?>
					<span class="clasbpro-theme-card__badge"><?php esc_html_e( 'Active', 'class-bookings-with-stripe-pro' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="clasbpro-theme-card__body">
				<h2 class="clasbpro-theme-card__title"><?php echo esc_html( $name ); ?></h2>

				<?php if ( ! empty( $tags ) ) : ?>
					<ul class="clasbpro-theme-card__tags" aria-label="<?php esc_attr_e( 'Tags', 'class-bookings-with-stripe-pro' ); ?>">
						<?php foreach ( $tags as $tag ) : ?>
							<li>
								<button
									type="button"
									class="clasbpro-theme-card__tag"
									data-filter-tag="<?php echo esc_attr( $tag ); ?>"
								>
									<?php echo esc_html( Theme_Registry::tag_label( $tag ) ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( '' !== $desc ) : ?>
					<p class="clasbpro-theme-card__desc"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>

				<?php if ( is_array( $provides ) && ! empty( $provides ) ) : ?>
					<ul class="clasbpro-theme-card__provides">
						<?php foreach ( $provides as $item ) : ?>
							<li><?php echo esc_html( (string) $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="clasbpro-theme-card__actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="clasbpro_theme_enable">
						<input type="hidden" name="theme" value="<?php echo esc_attr( $slug ); ?>">
						<?php wp_nonce_field( 'clasbpro_theme_enable' ); ?>
						<?php submit_button( $is_active ? __( 'Re-enable', 'class-bookings-with-stripe-pro' ) : __( 'Enable', 'class-bookings-with-stripe-pro' ), $is_active ? 'secondary' : 'primary', 'submit', false ); ?>
					</form>

					<a class="button" href="<?php echo esc_url( self::page_url( [ 'action' => 'install_confirm', 'theme' => $slug ] ) ); ?>">
						<?php esc_html_e( 'Install to theme', 'class-bookings-with-stripe-pro' ); ?>
					</a>

					<button type="button" class="button clasbpro-theme-open-preview" data-theme="<?php echo esc_attr( $slug ); ?>">
						<?php esc_html_e( 'Live preview', 'class-bookings-with-stripe-pro' ); ?>
					</button>

					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clasbpro_theme_download&theme=' . $slug ), 'clasbpro_theme_download_' . $slug ) ); ?>">
						<?php esc_html_e( 'Download ZIP', 'class-bookings-with-stripe-pro' ); ?>
					</a>

					<button type="button" class="button clasbpro-theme-open-files" data-theme="<?php echo esc_attr( $slug ); ?>">
						<?php esc_html_e( 'View files', 'class-bookings-with-stripe-pro' ); ?>
					</button>
				</div>
			</div>
		</article>
		<?php
	}

	private static function render_install_confirm(): void {
		$slug = sanitize_key( (string) ( $_GET['theme'] ?? '' ) );
		if ( ! Theme_Registry::exists( $slug ) ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Invalid theme.', 'class-bookings-with-stripe-pro' ) . '</p></div>';
			return;
		}

		$theme  = Theme_Registry::get( $slug );
		$diff   = Theme_Installer::analyze( $slug );
		$dest   = Theme_Installer::destination_root();

		?>
		<div class="wrap clasbpro-themes-wrap">
			<h1><?php esc_html_e( 'Install theme to your WordPress theme', 'class-bookings-with-stripe-pro' ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: 1: theme name, 2: destination path */
					esc_html__( 'Install “%1$s” into %2$s', 'class-bookings-with-stripe-pro' ),
					esc_html( (string) ( $theme['name'] ?? $slug ) ),
					'<code>' . esc_html( $dest ) . '</code>'
				);
				?>
			</p>

			<?php self::render_diff_list( __( 'New files', 'class-bookings-with-stripe-pro' ), $diff['add'] ); ?>
			<?php self::render_diff_list( __( 'Files to overwrite', 'class-bookings-with-stripe-pro' ), $diff['overwrite'] ); ?>
			<?php self::render_diff_list( __( 'Unchanged files', 'class-bookings-with-stripe-pro' ), $diff['unchanged'] ); ?>

			<p><?php esc_html_e( 'Existing files will be backed up before any changes are made.', 'class-bookings-with-stripe-pro' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="clasbpro_theme_install">
				<input type="hidden" name="theme" value="<?php echo esc_attr( $slug ); ?>">
				<input type="hidden" name="confirm" value="1">
				<?php wp_nonce_field( 'clasbpro_theme_install' ); ?>
				<?php submit_button( __( 'Confirm install', 'class-bookings-with-stripe-pro' ), 'primary' ); ?>
				<a class="button" href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( 'Cancel', 'class-bookings-with-stripe-pro' ); ?></a>
			</form>
		</div>
		<?php
	}

	/**
	 * @param list<string> $files
	 */
	private static function render_diff_list( string $title, array $files ): void {
		if ( empty( $files ) ) {
			return;
		}
		?>
		<h2><?php echo esc_html( $title ); ?> (<?php echo (int) count( $files ); ?>)</h2>
		<ul class="clasbpro-themes-diff-list">
			<?php foreach ( $files as $file ) : ?>
				<li><code><?php echo esc_html( $file ); ?></code></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	private static function render_notices(): void {
		$code = sanitize_key( (string) ( $_GET['clasbpro_notice'] ?? '' ) );
		if ( '' === $code ) {
			return;
		}

		$slug    = sanitize_key( (string) ( $_GET['theme'] ?? '' ) );
		$message = isset( $_GET['clasbpro_message'] ) ? sanitize_text_field( rawurldecode( (string) $_GET['clasbpro_message'] ) ) : '';

		$type = 'success';
		$text = '';

		switch ( $code ) {
			case 'enabled':
				$name = (string) ( Theme_Registry::get( $slug )['name'] ?? $slug );
				$text = sprintf(
					/* translators: %s: theme name */
					__( 'Gallery theme “%s” is now active.', 'class-bookings-with-stripe-pro' ),
					$name
				);
				break;
			case 'source_updated':
				$text = __( 'Theme source updated.', 'class-bookings-with-stripe-pro' );
				break;
			case 'installed':
				$name = (string) ( Theme_Registry::get( $slug )['name'] ?? $slug );
				$text = sprintf(
					/* translators: %s: theme name */
					__( '“%s” installed to your theme.', 'class-bookings-with-stripe-pro' ),
					$name
				);
				self::render_install_switch_prompt( $slug );
				break;
			case 'install_error':
				$type = 'error';
				$text = $message ?: __( 'Install failed.', 'class-bookings-with-stripe-pro' );
				break;
			case 'source_theme':
				$text = __( 'Now using theme files from your WordPress theme.', 'class-bookings-with-stripe-pro' );
				break;
			default:
				return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $text )
		);
	}

	private static function render_install_switch_prompt( string $slug ): void {
		$pending = get_transient( 'clasbpro_theme_install_prompt_' . get_current_user_id() );
		if ( $pending !== $slug ) {
			return;
		}

		?>
		<div class="notice notice-info clasbpro-themes-switch-prompt">
			<p><?php esc_html_e( 'Switch to Theme files to use the installed templates on your site?', 'class-bookings-with-stripe-pro' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
				<input type="hidden" name="action" value="clasbpro_theme_switch_files">
				<?php wp_nonce_field( 'clasbpro_theme_switch_files' ); ?>
				<?php submit_button( __( 'Switch now', 'class-bookings-with-stripe-pro' ), 'primary', 'submit', false ); ?>
			</form>
			<a class="button" href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( 'Keep gallery theme', 'class-bookings-with-stripe-pro' ); ?></a>
		</div>
		<?php
	}
}
