<?php

namespace MatthiasWeb\RealMediaLibrary\Vendor\DevOwl\RealUtils;

use MatthiasWeb\RealMediaLibrary\Vendor\MatthiasWeb\Utils\Service as UtilsService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
// @codeCoverageIgnoreStart
\defined('ABSPATH') or die('No script kiddies please!');
// Avoid direct file request
// @codeCoverageIgnoreEnd
/**
 * Create an example REST Service.
 * @internal
 */
class Service
{
    use UtilsProvider;
    /**
     * C'tor.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
        // Silence is golden.
    }
    /**
     * Register endpoints.
     */
    public function rest_api_init()
    {
        $namespace = UtilsService::getNamespace($this);
        \register_rest_route($namespace, '/feedback/(?P<slug>[a-zA-Z0-9_-]+)', ['methods' => 'POST', 'callback' => [$this, 'routeFeedbackCreate'], 'permission_callback' => [$this, 'permission_callback'], 'args' => ['reason' => ['type' => 'string', 'required' => \true], 'note' => ['type' => 'string', 'required' => \true], 'email' => ['type' => 'string']]]);
        \register_rest_route($namespace, '/cross/(?P<slug>[a-zA-Z0-9_-]+)/(?P<action>[a-zA-Z0-9_-]+)/dismiss', ['methods' => 'DELETE', 'callback' => [$this, 'routeCrossDismiss'], 'permission_callback' => [$this, 'permission_callback'], 'args' => ['force' => ['type' => 'boolean', 'default' => \false]]]);
        \register_rest_route($namespace, '/rating/(?P<slug>[a-zA-Z0-9_-]+)/dismiss', ['methods' => 'DELETE', 'callback' => [$this, 'routeRatingDismiss'], 'permission_callback' => [$this, 'permission_callback'], 'args' => ['force' => ['type' => 'boolean', 'default' => \false]]]);
    }
    /**
     * Check if user is allowed to call this service requests.
     */
    public function permission_callback()
    {
        return \current_user_can('activate_plugins');
    }
    /**
     * See API docs.
     *
     * @param WP_REST_Request $request
     * @deprecated Use package `real-product-manager-wp-client` instead
     * @api {post} /real-utils/v1/feedback/:slug Create feedback after deactivating a plugin (forward to devowl.io)
     * @apiHeader {string} X-WP-Nonce
     * @apiParam {string} slug
     * @apiParam {string} reason
     * @apiParam {string} note
     * @apiParam {string} [email]
     * @apiName FeedbackCreate
     * @apiPermission activate_plugins
     * @apiGroup Service
     * @apiVersion 1.0.0
     */
    public function routeFeedbackCreate($request)
    {
        $slug = $request->get_param('slug');
        $reason = $request->get_param('reason');
        $note = $request->get_param('note');
        $email = $request->get_param('email');
        // Get API url
        $apiHost = \defined('DEVOWL_WP_DEV') && \constant('DEVOWL_WP_DEV') ? 'http://localhost/' : 'https://devowl.io/';
        \wp_remote_post($apiHost . 'wp-json/devowl-site/v1/feedback/' . $slug, ['method' => 'POST', 'body' => ['reason' => $reason, 'note' => $note]]);
        // We do not need to check privacy because this is done client-side
        if (!empty($email)) {
            $this->sendDeactivationFeedbackEmail($apiHost, $slug, $email, $reason, $note);
        }
        // Currently, ignore errors
        return new WP_REST_Response(['result' => [$slug, $reason, $note]]);
    }
    /**
     * Send a deactivation feedback via email. Internally it uses the support form API.
     * There are no validations done for e. g. passed email.
     *
     * @param string $apiHost
     * @param string $slug
     * @param string $email
     * @param string $reason
     * @param string $note
     * @deprecated Use package `real-product-manager-wp-client` instead
     */
    protected function sendDeactivationFeedbackEmail($apiHost, $slug, $email, $reason, $note)
    {
        $initiator = Core::getInstance()->getInitiator($slug);
        if ($initiator === null) {
            return new WP_Error('rest_invalid_param', \__('No such plugin slug available.', 'devowl-wp-real-utils'), ['status' => 400]);
        }
        $pluginName = $initiator->getPluginBase()->getCore()->getPluginData('Name');
        \wp_remote_post($apiHost . 'wp-json/devowl-site/v1/support', ['method' => 'POST', 'body' => ['name' => $email, 'email' => $email, 'type' => 'deactivateFeedback', 'body' => 'WordPress Plugin: ' . $pluginName . '
Feedback: ' . $reason . '

' . $note, 'siteHealth' => 'none', 'privacy' => \true]]);
    }
    /**
     * See API docs.
     *
     * @param WP_REST_Request $request
     * @api {delete} /real-utils/v1/cross/:slug/:action/dismiss Dismiss cross-selling popup
     * @apiHeader {string} X-WP-Nonce
     * @apiParam {string} slug
     * @apiParam {string} action
     * @apiParam {boolean} force
     * @apiName CrossDismiss
     * @apiPermission activate_plugins
     * @apiGroup Service
     * @apiVersion 1.0.0
     */
    public function routeCrossDismiss($request)
    {
        $slug = $request->get_param('slug');
        $action = $request->get_param('action');
        $force = $request->get_param('force');
        $impl = Core::getInstance()->getCrossSelling($slug);
        if ($impl === null) {
            return new WP_Error('rest_error', \__('The abstract implementation for your slug could not be found.', 'devowl-wp-real-utils'));
        }
        $result = $impl->dismiss($action, $force);
        // Currently, ignore errors
        return new WP_REST_Response(['result' => $result]);
    }
    /**
     * See API docs.
     *
     * @param WP_REST_Request $request
     * @api {delete} /real-utils/v1/rating/:slug/dismiss Dismiss rating popup
     * @apiHeader {string} X-WP-Nonce
     * @apiParam {string} slug
     * @apiParam {boolean} force
     * @apiName RatingDismiss
     * @apiPermission activate_plugins
     * @apiGroup Service
     * @apiVersion 1.0.0
     */
    public function routeRatingDismiss($request)
    {
        $slug = $request->get_param('slug');
        $force = $request->get_param('force');
        $result = Core::getInstance()->getRatingHandler()->dismiss($slug, $force);
        // Currently, ignore errors
        return new WP_REST_Response(['result' => $result]);
    }
    /**
     * New instance.
     *
     * @codeCoverageIgnore
     */
    public static function instance()
    {
        return new Service();
    }
}
