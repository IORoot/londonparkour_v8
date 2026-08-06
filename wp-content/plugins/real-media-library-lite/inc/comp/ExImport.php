<?php

namespace MatthiasWeb\RealMediaLibrary\comp;

use MatthiasWeb\RealMediaLibrary\base\UtilsProvider;
use MatthiasWeb\RealMediaLibrary\lite\comp\ExImport as CompExImport;
use MatthiasWeb\RealMediaLibrary\overrides\interfce\comp\IOverrideExImport;
use MatthiasWeb\RealMediaLibrary\Util;
use MatthiasWeb\RealMediaLibrary\view\Options;
use MatthiasWeb\RealMediaLibrary\Vendor\MatthiasWeb\Utils\ExpireOption;
// @codeCoverageIgnoreStart
\defined('ABSPATH') or die('No script kiddies please!');
// Avoid direct file request
// @codeCoverageIgnoreEnd
/**
 * Import and export functionality.
 * @internal
 */
class ExImport implements IOverrideExImport
{
    use UtilsProvider;
    use CompExImport;
    private static $me = null;
    private $idOffset = null;
    const IMPORT_TAX_MATRIX = ['nt_wmc_folder' => 'FileBird', 'filebase_folder' => 'FileBase', 'media_folder' => 'Folders', 'attachment_category' => 'Media Library Assistant', 'media_category' => 'Enhanced Media Library', 'mlo-category' => 'Media Library Organizer', 'mediamatic_wpfolder' => 'Mediamatic Lite', 'wpmf-category' => 'Media Library Folders', 'rl_media_folder' => 'Responsive Lightbox & Gallery'];
    const IMPORT_TAX_IGNORE = ['language'];
    /**
     * "Media Library Folders" uses `wp_posts` to store available folders.
     *
     * @see https://de.wordpress.org/plugins/media-library-plus/
     */
    const MLF_POST_TYPE = 'mgmlp_media_folder';
    /**
     * "Filebird" uses also an own database table system for media relationship.
     */
    const FILE_BIRD_TABLE_NAME = 'fbv';
    const FILE_BIRD_TABLE_NAME_POSTS = 'fbv_attachment_folder';
    /**
     * Column names for import and export.
     */
    private $columns = [];
    /**
     * C'tor.
     */
    private function __construct()
    {
        $this->columns = \explode(',', 'name,ord,type,restrictions,contentCustomOrder,importId');
    }
    /**
     * Register options in media settings.
     */
    public function options_register()
    {
        \add_settings_section('rml_options_import', \__('RealMediaLibrary:Import / Export', 'real-media-library-lite'), [Options::getInstance(), 'empty_callback'], 'media');
        \add_settings_field('rml_button_import_cats', '<label for="rml_button_import_cats">' . \esc_html__('Import from other plugins', 'real-media-library-lite') . '</label>', [$this, 'html_rml_button_import_cats'], 'media', 'rml_options_import');
        \add_settings_field('rml_button_export', '<label for="rml_button_export">' . \esc_html__('Export / Import Real Media Library folders', 'real-media-library-lite') . '</label>', [$this, 'html_rml_button_export'], 'media', 'rml_options_import');
    }
    /**
     * Output export button in options.
     */
    public function html_rml_button_export()
    {
        echo '<a class="rml-rest-button button button-primary" data-url="export" data-method="GET">' . \esc_html__('Export', 'real-media-library-lite') . '</a>
        <a class="rml-rest-button button" data-url="import" data-method="POST" ' . ($this->isPro() ? '' : 'disabled="disabled"') . '>' . \esc_html__('Import', 'real-media-library-lite') . '</a>
        <p class="description" style="margin-bottom:10px">' . \esc_html__('All available folders will be exported. The current structure is not lost during import - but check that there are no duplicate names in the import data, as these are not checked.', 'real-media-library-lite') . '</p>
        <div id="rml_export_data" style="float:left;margin-right: 10px;"><div>' . \esc_html__('Exported data:', 'real-media-library-lite') . '</div><textarea></textarea></div>
        <div id="rml_import_data" style="float:left;"><div>' . \esc_html__('Import data:', 'real-media-library-lite') . '</div><textarea ' . ($this->isPro() ? '' : 'disabled="disabled"') . '></textarea></div><div class="clear"></div>';
        if (!$this->isPro()) {
            echo '<p class="description"><strong>' . \esc_html__('Importing data is only available in PRO version.', 'real-media-library-lite') . ' <a href="' . \esc_url(RML_PRO_VERSION . '&feature=import-export') . '" target="_blank">' . \esc_html__('Learn more about PRO', 'real-media-library-lite') . '</a></strong></p>';
        }
    }
    /**
     * Output import button in options.
     */
    public function html_rml_button_import_cats()
    {
        $hasMediaLibraryFolders = $this->hasMediaLibraryFolders();
        $hasFileBird = $this->hasFileBird();
        if (\count($this->getHierarchicalTaxos()) || $hasMediaLibraryFolders || $hasFileBird) {
            foreach ($this->getHierarchicalTaxos() as $tax) {
                $name = isset(self::IMPORT_TAX_MATRIX[$tax]) ? \esc_html(self::IMPORT_TAX_MATRIX[$tax]) : '<code>' . \esc_html($tax) . '</code>';
                echo '<a class="rml-rest-button button" data-url="import/taxonomy" data-method="POST" data-taxonomy="' . \esc_attr($tax) . '" ' . ($this->isPro() ? '' : 'disabled="disabled"') . '>' . \esc_html__('Import', 'real-media-library-lite') . ' (' . \esc_html($name) . ')</a>&nbsp;';
            }
            // Media Library Folders plugin
            if ($hasMediaLibraryFolders) {
                echo '<a class="rml-rest-button button" data-url="import/mlf" data-method="POST" ' . ($this->isPro() ? '' : 'disabled="disabled"') . '>' . \esc_html__('Import', 'real-media-library-lite') . ' (Media Library Folders)</a>&nbsp;';
            }
            // Filebird plugin
            if ($hasFileBird) {
                echo '<a class="rml-rest-button button" data-url="import/filebird" data-method="POST" ' . ($this->isPro() ? '' : 'disabled="disabled"') . '>' . \esc_html__('Import', 'real-media-library-lite') . ' (FileBird)</a>&nbsp;';
            }
            echo '<p class="description">' . \esc_html__('Imports categories and post relations.', 'real-media-library-lite') . '</p>';
        } else {
            echo '<p>' . \esc_html__('Nothing to import.', 'real-media-library-lite') . '</p>';
        }
        if (!$this->isPro()) {
            echo '<p class="description"><strong>' . \esc_html__('Importing categories from another plugin is only available in PRO version.', 'real-media-library-lite') . ' <a href="' . \esc_url(RML_PRO_VERSION . '&feature=import-export') . '" target="_blank">' . \esc_html__('Learn more about PRO', 'real-media-library-lite') . '</a></strong></p>';
        }
    }
    /**
     * Get the folder tree for import process.
     *
     * @return array
     */
    public function getFolders()
    {
        global $wpdb;
        // phpcs:disable WordPress.DB.PreparedSQL
        // Folders
        $table_name = $this->getTableName();
        $folders = $wpdb->get_results('SELECT id,parent,' . \implode(',', $this->columns) . " FROM {$table_name}", ARRAY_A);
        // Metas
        $table_name = $this->getTableName('meta');
        $metas = Util::getInstance()->group_by($wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A), 'realmedialibrary_id');
        // phpcs:enable WordPress.DB.PreparedSQL
        // Group metas
        $grouped = [];
        foreach ($folders as $folder) {
            // Assign metas
            if (isset($metas[$folder['id']])) {
                $folder['__metas'] = [];
                foreach ($metas[$folder['id']] as $meta) {
                    unset($meta['meta_id']);
                    unset($meta['realmedialibrary_id']);
                    $folder['__metas'][] = $meta;
                }
            }
            $grouped[] = $folder;
        }
        // Cast
        foreach ($grouped as &$row) {
            $row['parent'] = \intval($row['parent']);
            $row['id'] = \intval($row['id']);
        }
        // Create tree
        $tree = Util::getInstance()->buildTree($grouped, -1, 'parent', 'id', '__children');
        return Util::getInstance()->clearTree($tree, ['id', 'parent'], '__children');
    }
    /**
     * Get the hierarchical taxonomies for the media taxonomy. It also
     * returns taxonomies which are no longer registered
     *
     * @return string[]
     */
    public function getHierarchicalTaxos()
    {
        global $wpdb;
        // Fetch the taxonomies which are able to filter
        $taxonomy_objects = \get_object_taxonomies('attachment', 'objects');
        $taxos = [];
        foreach ($taxonomy_objects as $key => $value) {
            if ($value->hierarchical === \true) {
                $taxos[] = $key;
            }
        }
        // Read non-active ones
        // phpcs:disable WordPress.DB.PreparedSQL
        $taxonomy_sql = $wpdb->get_col("SELECT DISTINCT(wptt.taxonomy) FROM {$wpdb->term_taxonomy} AS wptt\n        INNER JOIN {$wpdb->term_relationships} AS wptr ON wptt.term_taxonomy_id = wptr.term_taxonomy_id\n        INNER JOIN {$wpdb->posts} AS wpp ON wptr.object_id = wpp.ID\n        WHERE wpp.post_type = 'attachment'");
        // phpcs:enable WordPress.DB.PreparedSQL
        return \array_diff(\array_unique(\array_merge($taxonomy_sql, $taxos)), self::IMPORT_TAX_IGNORE);
    }
    /**
     * Checks if the import notice in folder tree sidebar is dismissed.
     *
     * @param boolean $set
     * @return boolean
     */
    public function isImportTaxNoticeDismissed($set = null)
    {
        $value = '1';
        $expireOption = new ExpireOption(RML_OPT_PREFIX . '_importTaxNotice', \false, 365 * \constant('DAY_IN_SECONDS'));
        $expireOption->enableTransientMigration(ExpireOption::TRANSIENT_MIGRATION_SITE_WIDE);
        if ($set !== null) {
            $expireOption->set($set ? $value : 0);
        }
        return $expireOption->get() === $value;
    }
    /**
     * Checks if "Media Library Folders" was used previously.
     *
     * @see https://de.wordpress.org/plugins/media-library-plus/
     */
    public function hasMediaLibraryFolders()
    {
        global $wpdb;
        return \intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=%s", self::MLF_POST_TYPE))) > 0;
    }
    /**
     * Checks if "Filebird" was used previously.
     */
    public function hasFileBird()
    {
        global $wpdb;
        // Check if table exists
        // phpcs:disable WordPress.DB.PreparedSQL
        $table_name = $wpdb->prefix . self::FILE_BIRD_TABLE_NAME_POSTS;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        // phpcs:enable WordPress.DB.PreparedSQL
        return $exists;
    }
    /**
     * Get instance.
     *
     * @return ExImport
     */
    public static function getInstance()
    {
        return self::$me === null ? self::$me = new \MatthiasWeb\RealMediaLibrary\comp\ExImport() : self::$me;
    }
}
