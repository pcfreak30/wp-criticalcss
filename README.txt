=== Plugin Name ===
Contributors: pcfreak30
Donate link: https://www.paypal.me/pcfreak30
Tags: optimize, above the fold, critical css
Requires at least: 4.5
Tested up to: 4.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin that will use CriticalCSS.com to automatically generate above-the-fold CSS for your pages.
== Description ==

This plugin will automatically have the CriticalCSS.com web service get the needed above the fold CSS for every page on your site to help improve your user experience and site speed. This is commonly required by google pagespeed as one of the last steps to do.

**This plugin alone will not improve site performance**. You need a minify and/or caching plugin as well.

This plugin **does not** handle any minification or re-ordering of assets. I recommend using [WP Rocket](https://wp-rocket.me/) with my [WP Rocket Footer JS](https://wordpress.org/plugins/rocket-footer-js/), [WP Rocket ASYNC CSS](https://wordpress.org/plugins/rocket-async-css/), and the plugin [Preloader](https://wordpress.org/plugins/the-preloader/)

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

* What is the `/nocache/` in the URL's in the queue?

This is used as a special version of the web page that forcibly disables supported caching and minify plugins to ensure that critical css is created without complications. SEO wise these URL's are safe as they have no references anywhere and google will not be aware to crawl them.

== Changelog ==

### 0.6.4 ###

* Bug: SECOND_IN_SECONDS is defined in wp-rockets compatibility code only, so must be removed

### 0.6.3 ###

* Bug: Ensure object_id is an integer in get_permalink
* Bug: Disable rocket_clean_wpengine and rocket_clean_supercacher functions in after_rocket_clean_domain action when disabling integrations
* Enhancement: Don't use web check transient in template mode

### 0.6.2 ###

* Bug: Don't clear web check flags for posts or terms on edit in template mode

### 0.6.1 ###

* Bug: Fix the queue item exists method to only query template if it exists
* Bug: Correct data unset's in save and update queue methods
* Bug: Don't try adding to API queue if no template exists
* Bug: Purge all cache in template mode
* Bug: Fix missing variable assignment for get_transient call in get_item_data
* Bug: Restore set_cache call accidentally removed in 0.6.0

 ### 0.6.0 ###

 * Bug: Fix multisite settings menu
 * Feature: Add new cache mode to process pages by the wordpress template
 * Feature: Add full multisite compatibilty using site transients and network wide queue tables
 * Security: Prevent direct access class files

### 0.5.1 ###

* Bug: Use get_expire_period not get_rocket_purge_cron_interval

### 0.5.0 ###

* Bug: Replace purge lock with disable_external_integration method due to the order that the actions run
* Bug: Disable external integration when purging cache from web check queue
* Enhancement: Improve redirect_canonical logic
* Enhancement: Rebuild cache system without using SQL
* Cleanup: Clean up code and fix typo with cache


### 0.4.5 ###

* Change: Generalize fix_woocommerce_rewrite to fix_rewrites
* Bug: Add nocache URL rewrite fix for page archives
* Tweak: Don't append $query_string as it is generally unnecessary

### 0.4.4 ###

* Bug: Ensure all custom taxonomies that have rewrite enabled have the nocache url enabled
* Bug: Fix woocommerce taxonomies by forcing all nocache rewrite rules for woocommerce taxonomies to the top of the rewrite rule list

### 0.4.3 ###

* Enhancement: Added hack for WPEngine websites to prevent duplicate item entries

### 0.4.2 ###

* Enhancement: Convert relative URL's to absolute when doing a web check

### 0.4.1 ###

* Bug: Add a purge_lock property with getter/setter to flag when cache is being purged due to a item completing the API queue to prevent a process infinate cycle

### 0.4.0 ###

* Bug: Fix version comparison logic for upgrade routines and allow previous upgrade code to run on 0.4.0 upgrade due to the bug
* Enhancement: Major refactor to use dedicated mysql storage tables for queue instead of wp_options to simplify data management and ensure no duplicates can exist
* Enhancement: If $url in WP_CriticalCSS::get_permalink is a WP_Error, return false
* Enhancement: Skip item in web check queue if item exists in API queue or the permalink is false
* Cleanup: Purge all queue items from options table and web check transients on 0.4.0 upgrade


### 0.3.6 ###

* Bug: Only set DONOTCACHEPAGE if not set

### 0.3.5 ###

* Enhancement: If nocache is on, add robots meta for SEO to prevent duplicate content

### 0.3.4 ###

* Bug: Add slashes with wp_slash to protect post meta with slashes

### 0.3.3 ###

* Bug: Ensure the version setting actually updates on upgrade

### 0.3.2 ###

* Enhancement: Prevent duplicate web request queue items by querying the serialized data from the options table

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