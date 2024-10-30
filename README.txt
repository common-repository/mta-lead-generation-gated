=== MTA Lead Generation Gated ===
Contributors: ryanbaron
Tags: Lead Generation, Lead Generation Gated Content, Gated Content Gravity Forms
Requires at least: 3.7
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create gated content that can be added to any post or page where users must fill out a form in order to gain access to the content.

== Description ==

MTA Lead Generation Gated give administrators the ability to created gated content that can only be access once a form has been successfully submitted by a visitor.

This plugin adds a custom "gated_content" post type to WordPress, and with a shortcode allows Administrators add that gated content to any post or page.  Once the form has been successfully submitted the visitor will be given access to the gated content.

A few notes about the sections above:

* Currently this plugin requires Gravity Forms.
* Administrators can choose how long a visitor has access to the gated content for.
* Local Storage is used to save a randomly generated string allowing visitors to re-access the gated content on their next visit (Admins choose the access time period).
* Admins can choose to send gated content access events to Google Analytics.
* The user's contact information is saved to the WordPress database.
* Admins can choose to send the user's informaiton to Google Analytics to track user access to gated content

GitHub: [View](https://github.com/RyanBaron/mta-lead-generation-gated)

Questions: ryan@madtownagency.com

== Installation ==

1. Upload `mta-lead-generation-gated` plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Gated content main options
2. Gated content help
3. Gated content custom post type

== Changelog ==

**Version 1.0.0**

* Initial release
