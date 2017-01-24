=== Plugin Name ===
Contributors: pcfreak30
Donate link: https://www.paypal.me/pcfreak30
Tags: optimize, above the fold, critical css
Requires at least: 4.5
Tested up to: 4.7.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that will use CriticalCSS.com to automatically generate above-the-fold CSS for your pages.
== Description ==

This plugin will automatically have the CriticalCSS.com web service get the needed above the fold CSS for every page on your site to help improve your user experience and site speed. This is commonly required by google pagespeed as one of the last steps to do.

**This plugin alone will not improve site performance**. You need a minify and/or caching plugin as well.

This plugin **does not** handle any minification or re-ordering of assets. I recommend using [WP Rocket](https://wp-rocket.me/) with my [WP Rocket Footer JS](https://wordpress.org/plugins/rocket-footer-js/), [WP Rocket ASYNC CSS](https://wordpress.org/plugins/rocket-async-css/), and the plugin [Preloader](https://wordpress.org/plugin/the-preloader/)

If you need dedicated/professional assistance with this plugin or just want an expert to get your site to run the fastest it can be, you may hire me at [Codeable](https://codeable.io/developers/derrick-hammer/?ref=rvtGZ)

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/criticalcss` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
4. Go to Settings -> Critical CSS, and add your API key from [CriticalCSS.com](https://www.criticalcss.com/account/api-keys).

== Frequently Asked Questions ==

* Where do I get an API key from?

Please sign up at [CriticalCSS.com](https://www.criticalcss.com/?aff=3) then go to [CriticalCSS.com API Keys](https://www.criticalcss.com/account/api-keys). Be sure to read the additional pricing information!

At the time of writing (1 Jan 2017) the price for using [CriticalCSS.com](https://www.criticalcss.com/?aff=3) is:

£2/month for membership + £5/domain/month. This means the total cost will be £7/month if you use this plugin for one site.

* How do I report an issue?

You may go to https://www.github.com/pcfreak30/wp-criticalcss/issues and make a new issue. For any support, see the support forums.

* Will this work for inside paywalls or membershp sites?

Currently no. Since CriticalCSS.com can not access protected pages currently, the page must be publicaly visible to work. Means to allow this to work may come in the future.

* What will happen if I update content on the site or change my theme?

The plugins css cache will automatically purge for that post or term and get queued for processing again on the next user request of it.

* What will happen if I upgrade a plugin or theme?

The whole cache will be purged regardless of the purge setting

* Does this support any caching plugins?

Yes currently WP-Rocket is supported. Others can be added as integrations on request.

* What host is supported?

Generally any host. Some hosts like WPEngine has special support to purge the server cache.

== Changelog ==

### 0.3.1 ###

* Bug: Fix purge bulk action
* Cleanup: Merge settings classes together

### 0.3.0 ###

* Bug: Store status information when a generate API request is made
* Enhancement: Rework elements of settings UI
* Enhancement: Add bulk purge option in queue table
* Enhancement: Queue system and core system refactor that checks for changes by hashing html and css output via web request

### 0.2.5 ###

* Bug: after_rocket_clean_domain hook needs to be in disable_autopurge check

### 0.2.4.1 ###

* Bug: Messed up 0.2.4 version number

### 0.2.4 ###

* Change: Do not automatically auto-purge by default
* Bug: Use parse_url and http_build_url to safely append nocache in the permalink
* Bug: Allow purge through wp-rocket to work if its not through cron
* Bug: Fix timestamp logic
* Enhancement: Do not process critical css on 404 pages

### 0.2.3 ###

* Bug: Rename more places to wp_criticalcss

### 0.2.2 ###

* Change: Renamed options page
* Bug: Switch to using OPTIONNAME constant

### 0.2.1 ###

* Bug: Missed places to use new class name

### 0.2.0 ###

* Change: Rename everything to WP CriticalCSS due to legalities. This means that it will not be fully compatible with 0.1.x as all classes and options are renamed.

### 0.1.3 ###

* Bug/Enhancement: Use a simpler means to enable nocache on the homepage thats less error prone
* Cleanup: reorder_rewrite_rules method not needed

### 0.1.2 ###

* Bug: Revert bug fix for purging in 0.1.1 and just purge before setting cache

### 0.1.1 ###

* Bug: Always delete pending transient if there is no fatal error
* Bug: Toggle purge plugin integration to prevent generated css from getting purged
* Enhancement: If WP_Error just return item so it will get re-attempted
* Enhancement: Add method to disable purge plugin integration

### 0.1.0 ###

* Initial version