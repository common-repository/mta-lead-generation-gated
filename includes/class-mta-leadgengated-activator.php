<?php

/**
 * Fired during plugin activation
 *
 * @link       http://www.madtownagency.com
 * @since      1.0.0
 *
 * @package    mta_leadgengated
 * @subpackage mta_leadgengated/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    mta_leadgengated
 * @subpackage mta_leadgengated/includes
 * @author     Ryan Baron <ryan@madtownagency.com>
 */
class Mta_leadgengated_Activator {

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    1.0.0
   */
  public static function activate() {

    // Require parent plugin
    if ( !is_plugin_active( 'gravityforms/gravityforms.php' ) && current_user_can( 'activate_plugins' ) ) {
      // Stop activation redirect and show error
      wp_die('Sorry, but this plugin requires the Gravity Form Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }

  }

}
