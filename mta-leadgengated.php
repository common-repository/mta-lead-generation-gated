<?php

define('MTA_LEADGENGATED_URL_PATH', plugins_url().'/mta-lead-generation-gated');
define('MTA_LEADGENGATED_DIR_PATH', plugin_dir_path(__FILE__) );
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.madtownagency.com
 * @since             1.0.0
 * @package           mta_leadgengated
 *
 * @wordpress-plugin
 * Plugin Name:       MTA Lead Generation Gated
 * Plugin URI:        http://www.madtownagency.com/
 * Description:       This plugin allows admins to hide content on a page that is only displayed after a user fills out a form, granting them access.
 * Version:           1.0.0
 * Author:            Ryan Baron
 * Author URI:        http://www.madtownagency.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mta-leadgengated
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

//define('MTA_LEADGEN_TRACKING_TABLE',  $wpdb->prefix . 'mta_lgut';)
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mta-leadgengated-activator.php
 */
function activate_mta_leadgengated() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-mta-leadgengated-activator.php';
  Mta_leadgengated_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mta-leadgengated-deactivator.php
 */
function deactivate_mta_leadgengated() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-mta-leadgengated-deactivator.php';
  Mta_leadgengated_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mta_leadgengated' );
register_deactivation_hook( __FILE__, 'deactivate_mta_leadgengated' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mta-leadgengated.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mta_leadgengated() {

  $plugin = new Mta_leadgengated();
  $plugin->run();

}

//https://codex.wordpress.org/Creating_Tables_with_Plugins
register_activation_hook( __FILE__, 'mta_leadgengated_install' );

global $mta_leadgengated_db_version;
$mta_leadgengated_db_version = '1.0.0';

function mta_leadgengated_install() {
  global $mta_leadgengated_db_version;
  global $wpdb;
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  $user_table = $wpdb->prefix . 'mta_leadgen_user';
  $access_table = $wpdb->prefix . 'mta_leadgen_user_access';
  $wpdb_collate = $wpdb->collate;

  $sql =
    "CREATE TABLE {$user_table} (
         id mediumint(10) unsigned NOT NULL auto_increment,
         uuid varchar(255) NOT NULL,
         fname varchar(150) NULL,
         lname varchar(150) NULL,
         company varchar(200) NULL,
         phone varchar(50) NULL,
         email varchar(200) NULL,
         created_on bigint(20) NOT NULL DEFAULT 0,
         PRIMARY KEY  (id),
         KEY uuid (uuid)
         )
         COLLATE {$wpdb_collate}";

  dbDelta( $sql );

  $sql =
    "CREATE TABLE {$access_table} (
         id mediumint(10) unsigned NOT NULL auto_increment,
         uuid varchar(255) NOT NULL,
         gated_id mediumint(10) NOT NULL,
         access_id mediumint(10) NULL,
         access_until bigint(20) NOT NULL DEFAULT 0,
         created_on bigint(20) NOT NULL DEFAULT 0,
         last_access_on bigint(20) NOT NULL DEFAULT 0,
         PRIMARY KEY  (id),
         KEY uuid (uuid),
         KEY gated_id (gated_id)
         )
         COLLATE {$wpdb_collate}";

  dbDelta( $sql );

  add_option( 'mta_leadgengated_db_version', $mta_leadgengated_db_version );
}

run_mta_leadgengated();
