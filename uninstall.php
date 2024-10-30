<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       http://www.madtownagency.com
 * @since      1.0.0
 *
 * @package    mta_leadgengated
 */

// If uninstall not called from WordPress, then exit.
/*
 * Uninstall plugin
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
  exit ();

/* time to clean up!! */

// drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mta_leadgen_user");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mta_leadgen_user_access");

//remove all plugin option values
delete_option('mta_leadgen_gated_content_options');
delete_option('mta_leadgen_gated_content_help');
