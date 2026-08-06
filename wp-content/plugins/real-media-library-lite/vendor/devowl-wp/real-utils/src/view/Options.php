<?php

namespace MatthiasWeb\RealMediaLibrary\Vendor\DevOwl\RealUtils\view;

use MatthiasWeb\RealMediaLibrary\Vendor\DevOwl\RealUtils\Core;
use MatthiasWeb\RealMediaLibrary\Vendor\DevOwl\RealUtils\UtilsProvider;
// @codeCoverageIgnoreStart
\defined('ABSPATH') or die('No script kiddies please!');
// Avoid direct file request
// @codeCoverageIgnoreEnd
/**
 * Add real-utils specific options to frontend.
 * @internal
 */
class Options
{
    use UtilsProvider;
    const FIELD_NAME_CROSS_SELLING = 'real-utils-cross-selling';
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
     * Register settings.
     */
    public function admin_init()
    {
        if (Core::getInstance()->getCrossSellingHandler()->isAnyProInstalled() && \current_user_can('activate_plugins')) {
            \add_option(self::FIELD_NAME_CROSS_SELLING, \true);
            \register_setting('general', self::FIELD_NAME_CROSS_SELLING, ['type' => 'boolean']);
            \add_settings_field(self::FIELD_NAME_CROSS_SELLING, '<label for="' . \esc_attr(self::FIELD_NAME_CROSS_SELLING) . '">' . \sprintf(
                // translators:
                \__('Products of %s', 'devowl-wp-real-utils'),
                '<a href="https://devowl.io/" target="_blank">devowl.io</a>'
            ) . '</label>', [$this, 'html_cross_selling'], 'general');
        }
    }
    /**
     * Allow to deactivate real-utils cross-selling functionality.
     */
    public function html_cross_selling()
    {
        echo \sprintf('<label><input type="checkbox" name="%s" %s value="1" />%s</label>', \esc_attr(self::FIELD_NAME_CROSS_SELLING), \checked(self::isCrossSellingActive(), \true, \false), \sprintf(
            // translators:
            \esc_html__('Show advertising for not yet installed %s products in the WordPress backend', 'devowl-wp-real-utils'),
            '<a href="https://devowl.io/" target="_blank">devowl.io</a>'
        ));
    }
    /**
     * Check if cross-selling is activated.
     *
     * @return boolean
     */
    public static function isCrossSellingActive()
    {
        if (Core::getInstance()->getCrossSellingHandler()->isAnyProInstalled()) {
            return \get_option(self::FIELD_NAME_CROSS_SELLING);
        }
        return \true;
    }
    /**
     * New instance.
     *
     * @codeCoverageIgnore
     */
    public static function instance()
    {
        return new Options();
    }
}
