<?php

namespace MatthiasWeb\RealMediaLibrary\Vendor\DevOwl\RealUtils;

use MatthiasWeb\RealMediaLibrary\Vendor\MatthiasWeb\Utils\Service as UtilsService;
use MatthiasWeb\RealMediaLibrary\Vendor\MatthiasWeb\Utils\ServiceNoStore;
// @codeCoverageIgnoreStart
\defined('ABSPATH') or die('No script kiddies please!');
// Avoid direct file request
// @codeCoverageIgnoreEnd
/**
 * Core for real-utils. It is only initialized once and holds all available initiators.
 * @internal
 */
class Core
{
    use UtilsProvider;
    /**
     * The initiators need to be declared globally, due to the fact the class instance
     * itself is scoped and can exist more than once.
     */
    const GLOBAL_INITIATORS = 'real_utils_initiators';
    /**
     * Singleton instance.
     */
    private static $me;
    /**
     * Assets handler.
     *
     * @var Assets
     */
    private $assets;
    /**
     * Rating handler.
     *
     * @var RatingHandler
     */
    private $ratingHandler;
    /**
     * C'tor.
     */
    private function __construct()
    {
        $this->ratingHandler = RatingHandler::instance($this);
        $this->assets = Assets::instance();
        \add_action('rest_api_init', [Service::instance(), 'rest_api_init']);
        \add_action('admin_enqueue_scripts', [$this->getAssets(), 'admin_enqueue_scripts']);
        \add_action('customize_controls_print_scripts', [$this->getAssets(), 'customize_controls_print_scripts']);
        // Enable `no-store` for our relevant WP REST API endpoints
        ServiceNoStore::hook('/' . UtilsService::getNamespace($this));
    }
    /**
     * Add an initiator.
     *
     * @param AbstractInitiator $initiator
     * @codeCoverageIgnore
     */
    public function addInitiator($initiator)
    {
        $slug = $initiator->getPluginSlug();
        $this->getInitiators();
        // Initialize global once
        $GLOBALS[self::GLOBAL_INITIATORS][$slug] = $initiator;
    }
    /**
     * Return the base URL to assets. Please ensure a trailing slash, if you override it!
     *
     * @param string $path
     * @codeCoverageIgnore
     */
    public function getBaseAssetsUrl($path = '')
    {
        return 'https://assets.devowl.io/in-app/' . $path;
    }
    /**
     * Get initiator by slug.
     *
     * @param string $slug
     * @codeCoverageIgnore
     */
    public function getInitiator($slug)
    {
        $initiators = $this->getInitiators();
        return isset($initiators[$slug]) ? $initiators[$slug] : null;
    }
    /**
     * Get all initiators.
     *
     * @codeCoverageIgnore
     * @return AbstractInitiator[]
     */
    public function getInitiators()
    {
        if (!isset($GLOBALS[self::GLOBAL_INITIATORS])) {
            $GLOBALS[self::GLOBAL_INITIATORS] = [];
        }
        return $GLOBALS[self::GLOBAL_INITIATORS];
    }
    /**
     * Get assets handler.
     *
     * @codeCoverageIgnore
     */
    public function getAssets()
    {
        return $this->assets;
    }
    /**
     * Get rating handler.
     *
     * @codeCoverageIgnore
     */
    public function getRatingHandler()
    {
        return $this->ratingHandler;
    }
    /**
     * Get singleton core class.
     *
     * @codeCoverageIgnore
     * @return Core
     */
    public static function getInstance()
    {
        return !isset(self::$me) ? self::$me = new Core() : self::$me;
    }
}
