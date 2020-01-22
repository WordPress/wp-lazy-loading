=== Lazy Loading Feature Plugin ===
Contributors: wordpressdotorg, azaozz, flixos90
Tags: feature plugin, lazy loading
Requires at least: 5.3
Tested up to: 5.3
Stable tag: 1.0
Requires PHP: 5.6.20
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress feature plugin for testing and experimenting with automatically adding the "loading" HTML attribute. Not for production use.

== Description ==

Lazy Loading Feature Plugin is an official plugin maintained by the WordPress team. It is intended for testing of automatically adding the "loading" HTML attribute to images and other elements that support it.

More information about the "loading" attribute:
Description: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-loading
HTML Specification (not finalized): https://github.com/whatwg/html/pull/3752

Currently the "loading" attribute is supported only in Chromium browsers. Coming soon to Firefox and Edge.

To test, install and enable the plugin. It will automatically add loading="lazy" attributes to all images in all new and existing posts, pages, and comments on the front-end.

Then use a Chromium based browser (Chrome, Opera, latest Android, etc.) and visit the site. Best would be to test over a slower connection, with a phone, etc. and test web pages that have a lot of images, like gallery posts.

Things to look for:
* Obvious bugs, for example images are missing.
* Try to scroll down as soon as the page loads. All images should be at their places, and the page shouldn't "jump" if/when images are loaded.
