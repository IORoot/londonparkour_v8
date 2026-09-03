<?php

namespace MatthiasWeb\RealMediaLibrary\view;

use MatthiasWeb\RealMediaLibrary\folder\Creatable;
// @codeCoverageIgnoreStart
\defined('ABSPATH') or die('No script kiddies please!');
// Avoid direct file request
// @codeCoverageIgnoreEnd
/**
 * Handles the shortcode for [folder-gallery].
 * @internal
 */
class FolderShortcode
{
    private static $me = null;
    public static $TAG = 'folder-gallery';
    /**
     * Modify original shortcode attributes of [gallery].
     *
     * @param array $out
     * @param array $pairs
     * @param array $atts
     * @return array
     */
    public function shortcode_atts_gallery($out, $pairs, $atts)
    {
        $atts = \shortcode_atts(['fid' => -2, 'order' => 'DESC', 'orderby' => 'date', 'posts_per_page' => -1], $atts);
        // The fid can also come from $out
        if (isset($out['fid']) && $out['fid'] > -2) {
            $atts['fid'] = $out['fid'];
        }
        // RML order is only available with ASC
        if ($atts['orderby'] === 'rml' || isset($out['orderby']) && $out['orderby'] === 'rml') {
            $out['orderby'] = 'menu_order ID';
        }
        if ($atts['fid'] > -2) {
            if (!isset($out['include'])) {
                $out['include'] = '';
            }
            if ($atts['fid'] > -1) {
                $folder = \wp_rml_get_object_by_id($atts['fid']);
                if ($folder !== null) {
                    $out['include'] .= ',' . \implode(',', $folder->read($atts['order'], $atts['orderby']));
                }
            } else {
                $out['include'] .= ',' . \implode(',', Creatable::xread(-1, $atts['order'], $atts['orderby']));
            }
            $out['include'] = \ltrim($out['include'], ',');
            $out['include'] = \rtrim($out['include'], ',');
        }
        // Overwrite the default order by this shortcode
        if (isset($out['orderby']) && $out['orderby'] === 'menu_order ID') {
            $out['orderby'] = 'post__in';
        }
        return $out;
    }
    /**
     * Get instance.
     *
     * @return FolderShortcode
     */
    public static function getInstance()
    {
        return self::$me === null ? self::$me = new \MatthiasWeb\RealMediaLibrary\view\FolderShortcode() : self::$me;
    }
}
