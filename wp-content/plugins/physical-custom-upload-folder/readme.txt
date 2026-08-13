=== Physical Custom Upload Folder for Real Media Library ===
Contributors: devowl, mguenter
Tags: real media library, real physical media, upload folder, media library folder, upload, file system
Stable tag: trunk
Requires at least: 4.0
Tested up to: 5.4
License: GPLv2

Upload files in your media library to a custom folder structure on your file system created with Real Media Library folders.

== Description ==

Upload files in your media library to a custom folder structure on your file system created with [Real Media Library](https://devowl.io/wordpress-real-media-library/) folders. With this plugin you can determine where your uploads should be stored. This can also have some **SEO benefits for your website**.

For example, if you have created a folder named <code>Products/</code> in your media library (using Real Media Library), your uploads will be stored in <code>wp-content/uploads/Products/</code> instead of, for example, <code>wp-content/uploads/2020/03/</code>.

**This plugin doesn't care about the following:**

- Redirection when renaming files (if you or another website is already using an uploaded image, the URL becomes invalid without redirection to the new location)
- Drag and drop files to another folder in the Real Media Library (the URL of the image does not change)
- The import of a Real Media Library folder structure is ignored and the files are not saved in a custom upload folder
- Limit of 255 characters for the path of a folder structure plus file name
- No support for WordPress multi-sites

**If you want a full-featured solution for a custom folder structure on your file system with Real Media Library, you should look at the [Real Physical Media](https://devowl.io/go/codecanyon/real-physical-media?source=physical-custom-upload-folder-wordpress-org) add-on.** It has solved all of the above mentioned limitations, is performant and stable.

*This plugin is a free micro add-on for Real Media Library with limited support.*

== Installation ==

1. Go to your WordPress backend
2. Navigate to "Plugins" > "Add New"
3. Search for "Physical Custom Upload Folder for Real Media Library"
4. Install and activate the plugin
6. Install [Real Media Library from devowl.io](https://devowl.io/go/codecanyon/real-media-library?source=physical-custom-upload-folder-wordpress-org)
7. Upload files to your media library!

== Frequently Asked Questions ==

= How does this plugin work? =

The steps are simple: Activate both plugins (Real Media Library and this one), navigate to Media Library > Add New. Simply select the destination folder and upload a new file. The newly uploaded file is physically placed in your absolute Real Media Library path. This also works in the media library grid mode: Select a folder in the folder tree and upload a new file.

== Changelog ==

= 1.0.5 =
* Added link to PRO version in plugin row
* Fixed bug with PHP 7.3 (deprecation notice in error log)

= 1.0.4 =
* Fixed bug with pathes generation
* Removed unnecessery code blocks

= 1.0.3 =
* Fixed incompatibility with RML version 2.8.2

= 1.0.1 & 1.0.2 =
* Failure version commits

= 1.0.0 =
* Initial Release.
