=== Lazy Loading Feature Plugin ===
Contributors: wordpressdotorg, azaozz, flixos90
Tags: feature plugin, lazy loading
Requires at least: 5.3
Tested up to: 5.4
Stable tag: 1.1
Requires PHP: 5.6.20
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress feature plugin for testing and experimenting with automatically adding the "loading" HTML attribute. Not for production use.

== Description ==

Lazy Loading Feature Plugin is an official plugin maintained by the WordPress team. It is intended for testing of automatically adding the `loading` HTML attribute to images and other elements that support it.

More information about the `loading` attribute:
Description: [https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-loading](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-loading).
HTML Specification: [https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-loading](https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-loading).

Currently the `loading` attribute is supported in the following browsers: [https://caniuse.com/#feat=loading-lazy-attr](https://caniuse.com/#feat=loading-lazy-attr).

To test, install and enable the plugin. It will automatically add `loading="lazy"` attributes to all images in all new and existing posts, pages, and text widgets on the front-end.

Then use one of the browsers that support it (Chrome, Opera, Firefox, Edge, Android, etc.) and visit the site. Best would be to test over a slower connection, with a phone, etc. and test web pages that have a lot of images, like gallery posts.

= Things to look for =

* Obvious bugs, for example images are missing.
* Try to scroll down as soon as the page loads. All images should be at their places, and the page shouldn't "jump" when images are loaded.

= Note to developers =

This plugin is intended for testing. If the tests are successful, this functionality will be added to WordPress, but the exact code may change, perhaps significantly.

When testing, please also test the filters added by this plugin, and provide feedback at [https://github.com/WordPress/wp-lazy-loading](https://github.com/WordPress/wp-lazy-loading) or at [https://core.trac.wordpress.org/ticket/44427](https://core.trac.wordpress.org/ticket/44427).

== Changelog ==

Please see the Github repository: [https://github.com/WordPress/wp-lazy-loading](https://github.com/WordPress/wp-lazy-loading).
