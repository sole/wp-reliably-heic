=== Reliably HEIC ===
Contributors: sole
Donate link: https://soledadpenades.com/projects/wordpress/#donate
Tags: heic, heif, iphone, ios, jpg, jpeg, front-end
Requires at least: 6.2.2
Tested up to: 6.3
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Convert HEIC uploaded images to JPG, either back-end or front-end side (as a fallback).

== Description ==

This plugin adds support for uploading HEIC images (which iPhones use) to WordPress.

If your server meets the requirements, it will attempt to convert images using your server's installation of ImageMagick and its corresponding php module.

If your server does NOT meet the requirements, you can try tweaking your server's settings, or if that is not possible, you can try enablinthe experimental front-end processing as a fallback, and let your (modern) browser take care of converting images from HEIC to JPG before sending them to your server. This uses JavaScript and modern APIs like Canvas and WebAssembly, so you'll need a sufficiently up to date browser for this to work.

To verify if the requirements are met and/or to enable the front-end image processing, visit the plugin's _settings_ page.

== Frequently Asked Questions ==

= What are the limitations of this plug-in? =

There are some [limitations](https://github.com/sole/wp-reliably-heic#limitations) about what the plugin can and not do, please read them if you run into trouble.

If you want to help, you can contribute to the plugin development here: https://github.com/sole/wp-reliably-heic

== Screenshots ==

1. Upload new media screen when the front-end fallback is enabled
2. Settings page when all requirements are met
3. Settings page when not all requirements are met (and with the front-end conversion enabled)

== Changelog ==

= 1.0 =
* First public version

== Upgrade Notice ==

= 1.0 =
* First public version
