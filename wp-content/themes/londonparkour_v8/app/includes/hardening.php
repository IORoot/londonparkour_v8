<?php
/**
 * Close the public username-disclosure vectors the SEO audit flagged (H5).
 *
 * Anonymous clients must not enumerate `/wp-json/wp/v2/users`, hit xmlrpc.php,
 * or land on `/author/{login}/`. Logged-in editors who can `list_users` still
 * reach the REST users routes from wp-admin.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	exit;
}

add_filter( 'xmlrpc_enabled', '__return_false' );

remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );

/**
 * Anonymous REST requests to the users collection/item return 401.
 *
 * @param mixed           $result  Dispatch result so far.
 * @param WP_REST_Server  $server  Server instance.
 * @param WP_REST_Request $request Current request.
 * @return mixed
 */
function lp_rest_users_require_auth( $result, $server, $request ) {
	unset( $server );

	if ( true === $result || is_wp_error( $result ) ) {
		return $result;
	}

	$route = $request->get_route();
	if ( ! preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
		return $result;
	}

	if ( current_user_can( 'list_users' ) ) {
		return $result;
	}

	return new WP_Error(
		'rest_user_cannot_view',
		__( 'Sorry, you are not allowed to list users.', 'londonparkour_v8' ),
		array( 'status' => rest_authorization_required_code() )
	);
}
add_filter( 'rest_pre_dispatch', 'lp_rest_users_require_auth', 10, 3 );

/**
 * Author archives are not a public surface — `/author/admin/` must 404.
 */
function lp_author_archive_404(): void {
	if ( is_admin() || ! is_author() ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'lp_author_archive_404', 0 );
