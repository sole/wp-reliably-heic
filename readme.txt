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

If your server has the right requirements, it will attempt to convert images using your server's installation of ImageMagick and its corresponding php module.

If your server does NOT have the right requirements, you're invited to enable the experimental front-end processing as a fallback, and let your (modern) browser take care of converting images from HEIC to JPG before sending them to your server. This uses JavaScript and modern APIs like Canvas and WebAssembly, so you'll need a sufficiently up to date browser for this to work.

To verify if the requirements are met and/or to enable the front-end image processing, visit the plugin's _settings_ page.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Screenshots are stored in the /assets directory.
2. This is the second screen shot

== Changelog ==

= 1.0 =
* First public version

== Upgrade Notice ==

= 1.0 =
* First public version
