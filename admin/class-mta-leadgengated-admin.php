<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.madtownagency.com
 * @since      1.0.0
 *
 * @package    Mta_Gated_Content
 * @subpackage Mta_Gated_Content/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mta_Gated_Content
 * @subpackage Mta_Gated_Content/admin
 * @author     Ryan Baron <ryan@madtownagency.com>
 */
class Mta_LeadGen_Gated_Content_Admin {

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;

  /**
   * The content options group name for the plugin settings.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $content    The content options group name for the plugin settings.
   */
  private $gated_content_options;

  /**
   * The page display options group name for the plugin settings.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $page_display    The page_display options group name for the plugin settings.
   */
  private $gated_content_help;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of this plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;

    add_action( 'admin_menu', array( $this, 'add_mta_leadgen_gated_content_admin_page' ) );
    add_action( 'admin_init', array( $this, 'page_init' ) );

    add_action( 'init', array($this, 'mta_gated_content_post_type'));
    add_action( 'save_post', array($this, 'save_gated_content_post'));
    add_action( 'add_meta_boxes', array($this, 'mta_gated_content_post_metabox_add') );
    add_action( 'template_include', array($this, 'mta_gated_content_redirect_post' ) );

    add_filter( 'gform_confirmation_anchor', array($this, 'gated_content_confirmation_anchor_function') );
    add_filter( 'single_template', array( $this, 'mta_gated_content_custom_template' ) );

    add_shortcode( 'mta_gated_content', array($this, 'mta_gated_content_shortcode_function') );
  }

  /**
   * Filter the single_template with our custom function
   *
   * @since   1.0.0
   * @param   string    $single     The single page template file
   *
   * return   single page template file
   */
  function mta_gated_content_custom_template($single) {
    global $post;
    /* Checks for single template by post type */
    if ($post->post_type == "gated_content"){
      if(file_exists(MTA_LEADGENGATED_DIR_PATH . '/public/templates/single-gated-content.php')) {
        return MTA_LEADGENGATED_DIR_PATH . '/public/templates/single-gated-content.php';
      }
    }
    return $single;
  }


  /**
   * Rediect non admins away from the gated content single page
   *
   * @since   1.0.0
   * @param   string    $origina_template     The single page template file
   *
   * return   single page template file
   */
  function mta_gated_content_redirect_post($original_template) {

    /*
    * ToDo: allow admins to set a specific redirect url on the admin side of the gated content post.
    */

    global $post;

    $redirect_url = site_url();
    $is_admin = current_user_can( 'manage_options' );
    $queried_post_type = get_query_var('post_type');

    if ( is_single() && 'gated_content' == $queried_post_type && $is_admin) {
      print '<div class="gated-content-admin-notice">';
        print '<div>';
          printf( __('You are seeing this page because you are logged in as an administrator. Regular users will be redirected to the <a href="%1$s">home page</a>.', 'mta-leadgengated'), $redirect_url );
        print '</div>';
        print '<div>';
          printf( __("This gated content should be displayed on other post/pages using the shortcode: <strong>[mta_gated_content gated_id='".$post->ID."']</strong>", 'mta-leadgengated'), $redirect_url );
        print '</div>';
      print '</div>';

      return $original_template;
    }
    elseif ( is_single() && 'gated_content' ==  $queried_post_type && !$is_admin) {
      wp_redirect( home_url(), 301 ); //redirect the user to the home page
    }

    return $original_template;
  }


  /**
   * The allowed tags for various field types
   *
   * @since   1.0.1
   * @param   string    $type     The type of allowed field tags
   *
   * return   an array of tags allowed for field input
   */
  public function get_allowed_field_tags($type = '') {
    switch($type) {
      case 'textarea':
        return array(
          'p' => array(
          'class' => array(),
        ),
          'strong' => array(),
          'br' => array(),
          'em' => array(),
          'span' => array(
          'class' => array()
        ),
          'ul' => array(
          'class' => array()
        ),
          'ol' => array(
          'class' => array()
        ),
          'li' => array(
          'class' => array()
        ),
          'h1' => array(
          'class' => array()
        ),
          'h2' => array(
          'class' => array()
        ),
        );
        break;

      case 'text':
        return array(
          'strong' => array(),
          'em' => array(),
          'i' => array(
          'class' => array(),
          'aria-hidden' => array(),
        ),
          'span' => array(
          'class' => array(),
        ),
        );
        break;

      default:
        return array();
        break;
    }
    return array();
  }

  /**
   * The options select field values
   *
   * @since   1.0.0
   * @param   string    $type     The select field values are needed for
   *
   * return   an array of select field options
   */
  public function get_select_values($type) {
    switch($type) {
      case 'content_align':
        return array(
          'text-left' => __("Left",  'mta-leadgengated'),
          'text-center' => __("Center",  'mta-leadgengated'),
        );
        break;
      case 'form_type':
        return array(
          'gform' => __("Gravity Forms",  'mta-leadgengated'),
        );
        break;

      case 'analytics_view_tracking':
        return array(
          0 => __("Don't send view events to Google Analytics",  'mta-leadgengated'),
          1 => __("Send view events to Google Analytics",  'mta-leadgengated'),
        );
        break;

      case 'analytics_user_view_tracking':
        return array(
          0 => __("Don't send user information to analytics",  'mta-leadgengated'),
          1 => __("Send user information to analytics",  'mta-leadgengated'),
        );
        break;

      case 'access_period':
        return array(
          //'0'    => __('1 Time',     'mta-leadgengated'),
          //'session'    => __('Session (Until the user closes the browser.)',     'mta-leadgengated'),
          '60'      => __('1 min',    'mta-leadgengated'),
          '600'     => __('10 min',   'mta-leadgengated'),
          '3600'    => __('1 hr',     'mta-leadgengated'),
          '14400'   => __('4 hrs',    'mta-leadgengated'),
          '21600'   => __('6 hrs',    'mta-leadgengated'),
          '28800'   => __('8 hrs',    'mta-leadgengated'),
          '36000'   => __('10 hrs',   'mta-leadgengated'),
          '43200'   => __('12 hrs',   'mta-leadgengated'),
          '64800'   => __('18 hrs',   'mta-leadgengated'),
          '86400'   => __('1 Day',    'mta-leadgengated'),
          '864000'  => __('10 Days',  'mta-leadgengated'),
          '1728000' => __('20 Days',  'mta-leadgengated'),
          '2592000' => __('30 Days',  'mta-leadgengated'),
          '5184000' => __('60 Days',  'mta-leadgengated'),
          '7776000' => __('90 Days',  'mta-leadgengated'),
        );
        break;
      /*
      case 'thank_you_display':
        return array(
          'hide_ty' => __("Hide (Hide form thank you after submit)",  'mta-leadgengated'),
          'show_ty' => __("Show (Show form thank you after submit)",  'mta-leadgengated'),
        );
        break;
      */
    }
    return array();
  }

  /**
   * Register the gated_content Post Type
   *
   * @since   1.0.0
   */
  public function mta_gated_content_post_type() {
    $labels = array(
      'name' => _x('Gated Content', 'post type general name'),
      'singular_name' => _x('Resource', 'post type singular name'),
      'add_new' => _x('Add New Gated Content', 'gated_content'),
      'add_new_item' => __('Add New Gated Content'),
      'edit_item' => __('Edit Gated Content'),
      'new_item' => __('New Gated Content'),
      'view_item' => __('View Gated Content'),
      'search_items' => __('Search Gated Content'),
      'not_found' =>  __('Nothing found'),
      'not_found_in_trash' => __('Nothing found in Trash'),
      'parent_item_colon' => ''
    );
    $args = array(
      'labels' => $labels,
      'public' => true,
      'publicly_queryable' => true,
      'exclude_from_search' => true,
      'show_ui' => true,
      'query_var' => true,
      'rewrite' => array(
      'slug' => 'gated-content',
      'with_front' => false,
    ),
      'show_in_menu' => false,
      'capability_type' => 'post',
      'hierarchical' => false,
      'supports' => array('title', 'revisons')
    );

    register_post_type( 'gated_content' , $args );
  }

  /**
   * Disable gravity form confirmation scroll to anchor
   *
   * @since   1.0.0
   * @param   string    $type     The select field values are needed for
   *
   * return   an array of select field options
   */
  function gated_content_confirmation_anchor_function() {

    /*
     * ToDo: find a way to disable this on a per form basis so it only applies to gated form
     * https://www.gravityhelp.com/documentation/article/disabling-automatic-scrolling-on-form-confirmations/
     * add_filter( 'gform_confirmation_anchor_#', '__return_false' ); is not sufficient as we dont know the gated form numbers in advance
     * likely solution is to all the admin to select gated forms on the options and check those values here, returning false for the form ids selected by the admin
     */

    return false;
  }

  /**
   * Shortcode display for mta_gated_content
   *
   * @since   1.0.0
   * @param   string    $atts     The attributes passed in with the shortcode
   *
   * return   an array of select field options
   */
  function mta_gated_content_shortcode_function( $atts ){

    global $post;
    $id = isset($atts['gated_id']) && !empty($atts['gated_id']) ? $atts['gated_id'] : 0;
    $gated_post = get_post( $id );
    $post_type = $gated_post->post_type;

    $align         = get_post_meta($id, '_mta_leadgen_gated_form_align', true);
    $superheadline = get_post_meta($id, '_mta_leadgen_gated_form_superheadline', true);
    $headline      = get_post_meta($id, '_mta_leadgen_gated_form_headline', true);
    $subheadline   = get_post_meta($id, '_mta_leadgen_gated_form_subheadline', true);
    $text          = get_post_meta($id, '_mta_leadgen_gated_form_text', true);

    /* $ty_display     = get_post_meta($id, '_mta_leadgen_gated_ty_display', true);*/

    //if the shortcode does not have a gated id, let the admin user know
    if(!$id) {
      if ( is_admin() ) {
        $ret = "<div>" . __( "The shortcode requires a valid 'gated_id' attribute.", "mta-leadgengated" ) . "</div>";
      }
    }

    //if the post id is not valid, let the admin know
    if(!isset($gated_post) || empty($gated_post)) {
      if ( is_admin() ) {
        $ret = "<div>" . __( "The post id: $id is not a valid post id.", "mta-leadgengated" ) . "</div>";
        return $ret;
      }
    }

    //if the post type is not of the type "gated_content", let the admin know
    if($post_type != 'gated_content') {
      if ( is_admin() ) {
        $ret = "<div>" . __( "Sorry, the post id: $id is not a 'Gated Content' Post.", "mta-leadgengated" ) . "</div>";
        return $ret;
      }
    }

    $gform_id     = get_post_meta($id, '_mta_leadgen_gated_gform_id', true);
    $gform_exists = GFAPI::form_id_exists($gform_id);
    if(!$gform_exists) {
      if ( is_admin() ) {
        $ret = "<div>" . __( "Sorry, the gravity form with the id: $gform_id is not a valid form.", "mta-leadgengated" ) . "</div>";
        return $ret;
      }
    }

    //build the form header html
    $headline_html  = "";
    $headline_html .= !empty($superheadline) ? "<span class='superheadline'>$superheadline</span>" : "";
    $headline_html .= !empty($headline) ? $headline : "";
    $headline_html .= !empty($subheadline) ? "<span class='subheadline'>$subheadline</span>" : "";

    //wrap the content headline html
    $headline_html = !empty($headline_html) ? "<h2 class='gated-form-headline'>$headline_html</h2>" : "";
    $form_header = isset($text) ? '<div class="gated-form-header '.$align.'">' . $headline_html . '<div class="text">' . $text . '</div></div>' : '<div class="gated-form-header '.$align.'">' . $headline_html . '</div>';

    //all items have passed validation, show the form on the page.
    //valid post, valid gated_content post, gravity form selected on the gated content page is valid
    $ret            = GFFormDisplay::get_form($gform_id, false, false, false, array('gated_content' => $id), true, 999);
    $gated_content  = get_post_meta($id, '_mta_leadgen_gated_public_content', true );

    /*
    //this is temporarly disabled as we are not using the thank you dispaly option yet
    $ret = '<div id="gated_content_wrapper"><div class="gated-content-public">' . $gated_content . '</div><div id="gated_form_wrapper" class="' . $ty_display . '" data-ty-display="' . $ty_display . '" data-gated-id="' . $id . '" data-access-id="' . $post->ID . '">' . $ret . '</div></div>';
    */

    $ret = '
      <div id="gated_content_wrapper">
        <div class="gated-content-public">' . $gated_content . '</div>
        <div id="gated_form_wrapper" data-gated-id="' . $id . '" data-access-id="' . $post->ID . '">'. $form_header . $ret . '</div>
      </div>';

    return $ret;

  }

  /**
   * Adding the MTA LeadGenGated plugin options page
   *
   * @since   1.0.0
   *
   */
  public function add_mta_leadgen_gated_content_admin_page() {
    //Add a new top level Lead Generation Gated Page
    add_menu_page(
      'Lead Generation Gated Content Options',
      'Gated Content',
      'manage_options',
      'mta-gated-content',
      array( $this, 'create_gated_content_admin_page' )
    );

    add_submenu_page( 'mta-gated-content', 'Gated Items', 'Gated Content Items',  'manage_options', 'edit.php?post_type=gated_content', NULL );
  }

  /**
   * MTA LeadGenGated Options page callback
   *
   * @since   1.0.0
   *
   */
  public function create_gated_content_admin_page() {
    $this->gated_content_options = get_option( 'mta_leadgen_gated_content_options' );
    $this->gated_content_help = get_option( 'mta_leadgen_gated_content_help' ); ?>

    <div id="mta_leadgengated_options_page" class="wrap mta-leadgengated-options-page">
      <h1><?php _e("Lead Generation Gated Content Options", "mta-leadgengated"); ?></h1>
      <?php
      //we check if the page is visited by click on the tabs or on the menu button.
      //then we get the active tab.
      $active_tab = "gated-content-options";
      if(isset($_GET["tab"])) {
        if($_GET["tab"] == "gated-content-help") {
          $active_tab = "gated-content-help";
        } else {
          $active_tab = "gated-content-options";
        }
      } ?>

      <!-- wordpress provides the styling for tabs. -->
      <h2 class="nav-tab-wrapper">
        <!-- when tab buttons are clicked we jump back to the same page but with a new parameter that represents the clicked tab. accordingly we make it active -->
        <a href="?page=mta-gated-content&tab=gated-content-options" class="nav-tab <?php if($active_tab == 'gated-content-options'){echo 'nav-tab-active';} ?> ">
          <?php _e('Gated Content Options', 'mta-leadgengated'); ?>
        </a>
        <a href="?page=mta-gated-content&tab=gated-content-help" class="nav-tab <?php if($active_tab == 'gated-content-help'){echo 'nav-tab-active';} ?>">
          <?php _e('Help', 'mta-leadgengated'); ?>
        </a>
      </h2>
      <form method="post" action="options.php">
        <?php
        if($active_tab == 'gated-content-options') {
          // This prints out all hidden setting fields
          settings_fields( 'mta_leadgen_gated_content_options_group' );
          do_settings_sections( 'mta-leadgen-gated-settings' );
          submit_button();
        } elseif($active_tab == 'gated-content-help') {
          // This prints out all hidden setting fields
          settings_fields( 'mta_leadgen_gated_content_help_group' );
          do_settings_sections( 'mta-leadgen-gated-help' );
        }
        ?>
      </form>
    </div>
  <?php
  }

  /**
   * MTA LeadGenGated Add metaboxes to the gated_content post type
   *
   * @since   1.0.0
   *
   * @param   string    $post_type     The wordpress post type
   *
   */
  public function mta_gated_content_post_metabox_add($post_type) {
    $post_types = array('gated_content');

    //limit meta box to certain post types
    if (in_array($post_type, $post_types)) {

      add_meta_box('mta-gated_content-shortcode',
                   __('Shortcode'),
                   array($this, 'mta_gated_content_shortcode_meta_box_function'),
                   $post_type,
                   'normal',
                   'high');

      add_meta_box('mta-gated_content-public',
                   __('Public Content'),
                   array($this, 'mta_gated_content_public_meta_box_function'),
                   $post_type,
                   'normal',
                   'high');

      add_meta_box('mta-gated_content-form',
                   __('Form Content'),
                   array($this, 'mta_gated_content_form_meta_box_function'),
                   $post_type,
                   'normal',
                   'high');

      add_meta_box('mta-gated_content-private',
                   __('Private Content'),
                   array($this, 'mta_gated_content_private_meta_box_function'),
                   $post_type,
                   'normal',
                   'high');
    }
  }

  /**
    * Sanitize each content settings field as needed
    *
    * @since   1.0.0
    *
    * @param array $input Contains all settings fields as array keys
    */
  public function sanitize_content( $input ) {
    $new_input = array();

    if( isset( $input['mta_leadgengated_analytics_view_tracking'] ) )
      $new_input['mta_leadgengated_analytics_view_tracking'] = intval( $input['mta_leadgengated_analytics_view_tracking'] );

    if( isset( $input['mta_leadgengated_analytics_user_view_tracking'] ) )
      $new_input['mta_leadgengated_analytics_user_view_tracking'] = intval( $input['mta_leadgengated_analytics_user_view_tracking'] );

    if( isset( $input['mta_leadgengated_form_type'] ) )
      $new_input['mta_leadgengated_form_type'] = sanitize_text_field( $input['mta_leadgengated_form_type'] );

    return $new_input;
  }

  /**
   * Register and add settings
   *
   * @since   1.0.0
   *
   */
  public function page_init() {
    $this->gated_content_options = get_option( 'mta_leadgen_gated_content_options' );
    $this->gated_content_help = get_option( 'mta_leadgen_gated_content_help' );
    //////
    // Register mta leadgen gated content settings
    //////
    register_setting(
      'mta_leadgen_gated_content_options_group', // Option group
      'mta_leadgen_gated_content_options', // Option name
      array( $this, 'sanitize_content' ) // Sanitize
    );
    //////
    // Create the mta leadgen gated content settings section
    /////
    add_settings_section(
      'gated_settings', // ID
      'Gated Content Settings', // Title
      array( $this, 'print_settings_section_info' ), // Callback
      'mta-leadgen-gated-settings' // Page
    );
    //////
    // Add the mta leadgen gated setting fields
    //////
    add_settings_field(
      'select_form_type', // ID
      'Gated Content Forms', // Title
      array( $this, 'select_form_type_callback' ), // Callback
      'mta-leadgen-gated-settings', // Page
      'gated_settings' // Section
    );
    add_settings_field(
      'analytics_view_tracking', // ID
      'Gated Content View Tracking', // Title
      array( $this, 'select_analytics_view_tracking_callback' ), // Callback
      'mta-leadgen-gated-settings', // Page
      'gated_settings' // Section
    );
    add_settings_field(
      'analytics_user_view_tracking', // ID
      'Send User Information To Analytics', // Title
      array( $this, 'select_analytics_user_view_tracking_callback' ), // Callback
      'mta-leadgen-gated-settings', // Page
      'gated_settings' // Section
    );
    //////
    // Register mta leadgen gated content settings
    //////
    register_setting(
      'mta_leadgen_gated_content_help_group', // Option group
      'mta_leadgen_gated_content_help', // Option name
      array( $this, 'sanitize_content' ) // Sanitize
    );
    //////
    // Create the mta leadgen gated content settings section
    /////
    add_settings_section(
      'gated_help_header', // ID
      'Help', // Title
      array( $this, 'print_help_section_info' ), // Callback
      'mta-leadgen-gated-help' // Page
    );
    add_settings_section(
      'gated_help_gravity_form', // ID
      'Gravity Form Creation', // Title
      array( $this, 'print_help_section_gravity_form' ), // Callback
      'mta-leadgen-gated-help' // Page
    );
    add_settings_section(
      'gated_help_gated_item', // ID
      'Gated Item Creation', // Title
      array( $this, 'print_help_section_gated_item_creation' ), // Callback
      'mta-leadgen-gated-help' // Page
    );
    add_settings_section(
      'gated_help_gated_item_display', // ID
      'Gated Item Display', // Title
      array( $this, 'print_help_section_gated_item_display' ), // Callback
      'mta-leadgen-gated-help' // Page
    );
    add_settings_section(
      'gated_help_gated_styling', // ID
      __('Gated Item CSS Styling', 'mta-leadgengated'), // Title
      array( $this, 'print_help_section_gated_styling' ), // Callback
      'mta-leadgen-gated-help' // Page
    );
  }

  /**
    * Print the Settings Section text
    */
  public function print_settings_section_info() {
    print '<p>' . __('MTA Lead Generation Gated Content Settings.', 'mta-leadgengated') . '</p>';
  }

  /**
    * Print the Settings Section text
    */
  public function print_help_section_info() {
    print '<p>' . __('MTA Lead Generation Gated Content Help.', 'mta-leadgengated') . '</p>';
  }

  /**
    * Print the Help Section Styling text
    */
  public function print_help_section_gated_styling() {
    print '
    <ol>
      <li>' . __('Gated content (Public, Private and Form) is all wrapped with the id <strong>"#print_help_section_gated_styling"</strong>', 'mta-leadgengated') . '</li>
      <li>' . __('The Public content is wrapped with the class <strong>".gated-content-public"</strong>', 'mta-leadgengated') . '</li>
      <li>' . __('The Gated Content Form is wrapped with the id <strong>"#gated_form_wrapper"</strong>', 'mta-leadgengated') . '</li>
      <li>' . __('The Private content is wrapped with the class <strong>".gated-content-private"</strong>', 'mta-leadgengated') . '</li>
    </ol>
    ';
  }

  /**
    * Print the Gated Content Item Display Section text
    */
  public function print_help_section_gated_item_display() {
    $plugins_url = plugins_url();
    print '
      <ol class="help-list">
        <li>' . __('Each gated content item created will generate a shortcode that can be used to display the gated content on any post or page.', 'mta-leadgengated') . '<a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-display-1.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('Add the shortcode to any page or post to display the gated content.', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-display-2.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
      </ol>
    ';
  }

  /**
    * Print the Gated Content Item Creation Section text
    */
  public function print_help_section_gated_item_creation() {
    $plugins_url = plugins_url();
    print '
      <ol class="help-list">
        <li>' . __('In the sidebar navigation click "Gated Content->Gated Content Items".', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-create-1.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('At the top of the page, click "Add New Gated Content".', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-create-2.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('Gated Content Items', 'mta-leadgengated') . '
          <ol>
            <li><' . __('em><strong>Publc Content Field</strong></em> - The public content field is optional.  This text/content displays above the gated content access form and can be used as a content teaser, allowing users to see an introduction to the content or a part of the gated content before submitting the form.', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-create-public-content.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
            <li>' . __('<em><strong>Private Content Field</strong></em> - This text/content displays after the gated content form has been successfully filled out.  When a user successfully fills out the form the form will be replaced with the contents from this field.', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-create-private-content.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
            <li>' . __('<em><strong>Gated Content Form Select Field</strong></em> - Select the Gravity Form (must already be created, see above) the user must fill out to gain access to the gated content. (<em>Private Content Field</em>)', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-create-form-select.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
            <li>' . __('* <em><strong>Access Time Period Field</strong></em> - Select how long a user will have access to the gated content for.', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gated-content-create-access-period.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
          </ol>
        </li>
      </ol>
      <div><em>' . __('* - When a user successfully fills out the gated content form a randomly generated local storage value is added, allowing the user to revisit the gated content (from the same computer) without having to fill out the form again.', 'mta-leadgengated') . '</em></div>
    ';
  }

  /**
    * Print the Gated Content Gravity Form Section text
    */
  public function print_help_section_gravity_form() {
    $plugins_url = plugins_url();
    print '
      <ol class="help-list">
        <li>' . __('Create a standard Gravity Form form.', 'mta-leadgengated') . ' </li>
        <li>' . __('* Add a name field to the form.  For the name field, Under the appearance tab add the class "gated-name".', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gravity-form-name-field.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('* Add a company field (standard input field) to the form.  For the company field, Under the appearance tab add the class "gated-company".', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gravity-form-company-field.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('* Add a email field to the form.  For the email field, Under the appearance tab add the class "gated-email".', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gravity-form-company-field.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('* Add a phone field to the form.  For the phone field, Under the appearance tab add the class "gated-phone".', 'mta-leadgengated') . ' <a data-screenshot-url="'.$plugins_url.'/mta-lead-generation-gated/admin/img/gravity-form-company-field.jpg" class="help-screenshot" href="#">View Screenshot</a><div class="screenshot-wrapper"><a href="#" class="hide-screenshot"><span>Close</span> &times;</a></div></li>
        <li>' . __('The form will be attached to the individual gated content items. See Gated Item Creation section below.', 'mta-leadgengated') . '</li>
      </ol>
      <div><em>' . __('* - Adding these classes to the form fields is optional, but with the classes added the plugin will create a database entry that stores the user\'s information.  That information can then be passed to Google Analytics for lead tracking', 'mta-leadgengated') . '.</em></div>
    ';
  }

  /**
    * Print the Settings Section text
    */
  public function print_help_section_tracking_content() {
    print '<p>' . __('This plugin uses a combination of localstorage and and the WordPress database to store and track user interaction with gated content.', 'mta-leadgengated') . '</p>';
    print '<p>' . __('When a user successfully fills in a from to gain access to the gated content a random 64 digit alphanumeric string is generated and stored using local storage.  A database entry is also created storing the users contact information along with the 64 digit alphanumeric string.', 'mta-leadgengated') . '</p>';
    print '<p>' . __('* No personal information is stored in local storage.', 'mta-leadgengated') . '</p>';
  }

  /**
    * Print the mta_leadgengated_form_type select field
    */
  public function select_form_type_callback() {
    $options = '';
    foreach( $this->get_select_values('form_type') as $key => $label ) {
      $options .= '<option value="'.$key.'" '. selected( $this->gated_content_options['mta_leadgengated_form_type'], $key, FALSE) .'>'.$label.'</option>';
    }
    print '<select name="mta_leadgen_gated_content_options[mta_leadgengated_form_type]" id="mta_leadgengated_form_type">' . $options .'</select>';
    print '<div class="desc">' . __('Select the type of form to be used for gated content.', 'mta-leadgengated') . '</div>';
  }

  /**
    * Print the mta_leadgengated_analytics_view_tracking select field
    */
  public function select_analytics_view_tracking_callback() {
    $options = '';
    foreach( $this->get_select_values('analytics_view_tracking') as $key => $label ) {
      $options .= '<option value="'.$key.'" '. selected( $this->gated_content_options['mta_leadgengated_analytics_view_tracking'], $key, FALSE) .'>'.$label.'</option>';
    }
    print '<select name="mta_leadgen_gated_content_options[mta_leadgengated_analytics_view_tracking]" id="mta_leadgengated_analytics_view_tracking">' . $options .'</select>';
    print '<div class="desc">' . __('Send an anonymous event to Google Analytics when a user interacts with gated content.<br>* Google Analytics must be added to your website for tracking.', 'mta-leadgengated') . '</div>';
  }

  /**
    * Print the mta_leadgengated_analytics_user_view_tracking select field
    */
  public function select_analytics_user_view_tracking_callback() {
    $options = '';
    foreach( $this->get_select_values('analytics_user_view_tracking') as $key => $label ) {
      $options .= '<option value="'.$key.'" '. selected( $this->gated_content_options['mta_leadgengated_analytics_user_view_tracking'], $key, FALSE) .'>'.$label.'</option>';
    }
    print '<select name="mta_leadgen_gated_content_options[mta_leadgengated_analytics_user_view_tracking]" id="mta_leadgengated_analytics_user_view_tracking">' . $options .'</select>';
    print '<div class="desc">' . __('Send and event to Google Analytics with the user\'s information when a user interacts with gated content.<br>* Google Analytics must be added to your website for tracking.', 'mta-leadgengated') . '</div>';
  }

  public function mta_gated_content_shortcode_meta_box_function($post) {
    $post_id = $post->ID;
    echo "<div><h3>Gated Content Shortcode</h3></div>";
    echo "<div><p>Use the shortcode on any post or page to display the gated content/gated content form.</p></div>";
    echo "<div class='meta-item'><strong>[mta_gated_content gated_id='$post_id']</strong></div>";
  }

  public function mta_gated_content_public_meta_box_function($post) {
    wp_nonce_field('mta_gated_content_post_nonce_check', 'mta_gated_content_post_nonce_check_value');
    $mta_leadgen_gated_public_content        = get_post_meta($post->ID, '_mta_leadgen_gated_public_content', true );

    echo '<div class="mta-leadgen-metabox mta-leadgen-gated-content-metabox">';
    echo '<div><p>This content is displayed initially on the page (above the gated content form).</p><p>The user does not need to successfully submit the form to see this content, it will be displayed initially on the page when it loads.  When the form is successfully submitted the <a href="#mta-gated_content-private">Private Content</a> will be appended below this public content.</p></div>';
    wp_editor( $mta_leadgen_gated_public_content, 'mta_leadgen_gated_public_content' );
    echo '</div>';
  }

  public function mta_gated_content_form_meta_box_function($post) {
    wp_nonce_field('mta_gated_content_post_nonce_check', 'mta_gated_content_post_nonce_check_value');
    $mta_leadgen_gated_access_period      = get_post_meta($post->ID, '_mta_leadgen_gated_access_period', true);
    $mta_leadgen_gated_form_align         = get_post_meta($post->ID, '_mta_leadgen_gated_form_align', true);
    $mta_leadgen_gated_form_superheadline = get_post_meta($post->ID, '_mta_leadgen_gated_form_superheadline', true);
    $mta_leadgen_gated_form_headline      = get_post_meta($post->ID, '_mta_leadgen_gated_form_headline', true);
    $mta_leadgen_gated_form_subheadline   = get_post_meta($post->ID, '_mta_leadgen_gated_form_subheadline', true);
    $mta_leadgen_gated_form_text          = get_post_meta($post->ID, '_mta_leadgen_gated_form_text', true);
    $mta_leadgen_gated_gform_id           = get_post_meta($post->ID, '_mta_leadgen_gated_gform_id', true);

    $form_items = [];
    echo '<div class="mta-leadgen-metabox mta-leadgen-gated-content-metabox">';
    echo '<h3>Form Content</h3>';
    echo '<div><p>The form content is displayed below the public content and is replaced with the private content upon successful submission of the form.</p></div>';
    echo '</div>';

    $form_align = $this->get_select_values('content_align');
    if(is_array($form_align) && !empty($form_align)) {
      $form_align_select = '<select name="mta_leadgen_gated_form_align" id="mta_leadgen_gated_form_align">';
      foreach( $form_align as $value => $label ):
      $form_align_select .= '<option value="' . $value . '" ' . selected( $mta_leadgen_gated_form_align, $value, false ) .' >' . $label . '</option>';
      endforeach;
      $form_align_select .= '</select>';

      $form_items['form_align_select'] = array(
        'wrapper_class' => 'meta-input',
        'field_item' => $form_align_select,
        'field_label'   => '<label for="mta_leadgen_gated_form_align">'.__('Form header align', 'mta-leadgengated') .'<br></label>',
      );
    }

    $form_superheadline = '<input name="mta_leadgen_gated_form_superheadline" id="mta_leadgen_gated_form_superheadline" value="'.$mta_leadgen_gated_form_superheadline.'">';
    $form_items['superheadline'] = array(
      'wrapper_class' => 'meta-input',
      'field_item'    => $form_superheadline,
      'field_label'   => '<label for="mta_leadgen_gated_form_superheadline">'. __('Form Super Headline', 'mta-leadgengated') .'<br></label>',
      'field_desc'    => __('Allowed Tags: span(class), i(class), strong and em','mta-leadgengated'),
    );

    $form_headline = '<input name="mta_leadgen_gated_form_headline" id="mta_leadgen_gated_form_headline" value="'.$mta_leadgen_gated_form_headline.'">';
    $form_items['headline'] = array(
      'wrapper_class' => 'meta-input',
      'field_item'    => $form_headline,
      'field_label'   => '<label for="mta_leadgen_gated_form_headline">'. __('Form Headline', 'mta-leadgengated') .'<br></label>',
      'field_desc'    => __('Allowed Tags: span(class), i(class), strong and em','mta-leadgengated'),
    );

    $form_subheadline = '<input name="mta_leadgen_gated_form_subheadline" id="mta_leadgen_gated_form_subheadline" value="'.$mta_leadgen_gated_form_subheadline.'">';
    $form_items['subheadline'] = array(
      'wrapper_class' => 'meta-input',
      'field_item'    => $form_subheadline,
      'field_label'   => '<label for="mta_leadgen_gated_form_subheadline">'. __('Form Sub Headline', 'mta-leadgengated') .'<br></label>',
      'field_desc'    => __('Allowed Tags: span(class), i(class), strong and em','mta-leadgengated'),
    );

    $form_text = '<textarea name="mta_leadgen_gated_form_text" id="mta_leadgen_gated_form_text">'.$mta_leadgen_gated_form_text.'</textarea>';
    $form_items['text'] = array(
      'wrapper_class' => 'meta-input',
      'field_item'    => $form_text,
      'field_label'   => '<label for="mta_leadgen_gated_form_text">'. __('Form Text', 'mta-leadgengated') .'<br></label>',
      'field_desc'    => __('Allowed Tags: p(class), span(class), ul(class), ol(class), li(class), h1(class), h2(class), i(class), strong, em and br','mta-leadgengated'),
    );

    $forms = RGFormsModel::get_forms( null, 'title' );
    if(is_array($forms) && !empty($forms)) {
      $form_select = '<select name="mta_leadgen_gated_gform_id" id="mta_leadgen_gated_gform_id">';
      $form_select .= '<option id="">None</option>';
      $forms = RGFormsModel::get_forms( null, 'title' );
      foreach( $forms as $form ):
      $form_select .= '<option value="' . $form->id . '" ' . selected( $mta_leadgen_gated_gform_id, $form->id, false ) .' >' . $form->title . '</option>';
      endforeach;
      $form_select .= '</select>';

      $form_items['form_select'] = array(
        'wrapper_class' => 'meta-input',
        'field_item'    => $form_select,
        'field_label'   => '<label for="mta_leadgen_gated_gform_id">'. __('Gated content form select', 'mta-leadgengated') .'<br></label>',
        'field_desc'    => __('Select the form the user needs to successfully fill out in order to gain access to the private content.','mta-leadgengated'),
      );
    }

    $access_period = $this->get_select_values('access_period');
    if(is_array($access_period) && !empty($access_period)) {
      $access_period_select = '<select name="mta_leadgen_gated_access_period" id="mta_leadgen_gated_access_period">';
      foreach( $access_period as $value => $label ):
      $access_period_select .= '<option value="' . $value . '" ' . selected( $mta_leadgen_gated_access_period, $value, false ) .' >' . $label . '</option>';
      endforeach;
      $access_period_select .= '</select>';

      $form_items['access_period_select'] = array(
        'wrapper_class' => 'meta-input',
        'field_item' => $access_period_select,
        'field_label'   => '<label for="mta_leadgen_gated_access_period">'.__('Access time period', 'mta-leadgengated') .'<br></label>',
        'field_desc' => __('After a visitor successfully fills out the gated content form, select how long they will have access to the gated content for.','mta-leadgengated'),
      );
    }

    include('partials/gated-content-meta-box-display.php');

  }

  public function mta_gated_content_private_meta_box_function($post) {
    wp_nonce_field('mta_gated_content_post_nonce_check', 'mta_gated_content_post_nonce_check_value');
    $mta_leadgen_gated_private_content    = get_post_meta($post->ID, '_mta_leadgen_gated_private_content', true );
    /*$mta_leadgen_gated_ty_display       = get_post_meta($post->ID, '_mta_leadgen_gated_ty_display', true);*/

    $form_items = [];
    echo '<div class="mta-leadgen-metabox mta-leadgen-gated-content-metabox">';
    echo '<h3>Private Content</h3>';
    echo '<div><p>This content is <strong>PRIVATE</strong>, and not displayed on the page until after the user successfully submits the form.</p><p>Once the from is successfully submitted it will be replaced with this private content.</p></div>';
    wp_editor( $mta_leadgen_gated_private_content, 'mta_leadgen_gated_private_content' );
    echo '</div>';



    /*
    //temporarly removed and the thank you option has not been fully implemented
    $ty_options = $this->get_select_values('thank_you_display');
    if(is_array($ty_options) && !empty($ty_options)) {
      $ty_display_select = '<select name="mta_leadgen_gated_ty_display" id="mta_leadgen_gated_ty_display">';
      foreach( $ty_options as $value => $label ):
        $ty_display_select .= '<option value="' . $value . '" ' . selected( $mta_leadgen_gated_ty_display, $value, false ) .' >' . $label . '</option>';
      endforeach;
      $ty_display_select .= '</select>';

      $form_items['ty_display_select'] = array(
        'wrapper_class' => 'meta-input',
        'field_item' => $ty_display_select,
        'field_label'   => '<label for="mta_leadgen_gated_ty_display">'.__('Display form thank you', 'mta-leadgengated') .'<br></label>',
        'field_desc'    => __('Upon successful form submission display the default form thank you message above the gated content added to the page.','mta-leadgengated')
      );
    }
    */

    /*
    $access_period = $this->get_select_values('access_period');
    if(is_array($access_period) && !empty($access_period)) {
      $access_period_select = '<select name="mta_leadgen_gated_access_period" id="mta_leadgen_gated_access_period">';
      foreach( $access_period as $value => $label ):
        $access_period_select .= '<option value="' . $value . '" ' . selected( $mta_leadgen_gated_access_period, $value, false ) .' >' . $label . '</option>';
      endforeach;
      $access_period_select .= '</select>';

      $form_items['access_period_select'] = array(
        'wrapper_class' => 'meta-input',
        'field_item' => $access_period_select,
        'field_label'   => '<label for="mta_leadgen_gated_access_period">'.__('Access time period', 'mta-leadgengated') .'<br></label>',
        'field_desc' => __('After a visitor successfully fills out the gated content form, select how long they will have access to the gated content for.','mta-leadgengated'),
      );
    }
    */

  //include the template (which uses the above generated variables $content, $form, $wrapper_classes)
  //include('partials/gated-content-meta-box-display.php');
  }

  /**
   * Save the meta when the post is saved.
   *
   * @param int $post_id The ID of the post being saved.
   */
  public function save_gated_content_post($post_id) {
    /*
     * We need to verify this came from the our screen and with
     * proper authorization,
     * because save_post can be triggered at other times.
     */

    // Check if our nonce is set.
    if (!isset($_POST['mta_gated_content_post_nonce_check_value']))
      return $post_id;

    $nonce = $_POST['mta_gated_content_post_nonce_check_value'];

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($nonce, 'mta_gated_content_post_nonce_check'))
      return $post_id;

    // If this is an autosave, our form has not been submitted,
    // so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      return $post_id;

    // Check the user's permissions.
    if ('page' == $_POST['post_type']) {
      if (!current_user_can('edit_page', $post_id))
        return $post_id;
    } else {
      if (!current_user_can('edit_post', $post_id))
        return $post_id;
    }

    $allowed_text     = $this->get_allowed_field_tags('text');
    $allowed_textarea = $this->get_allowed_field_tags('textarea');

    /* OK, its safe for us to save the data now. */
    $mta_leadgen_gated_gform_id = wp_kses( $_POST['mta_leadgen_gated_gform_id'], array() );
    if( isset( $mta_leadgen_gated_gform_id ) )
      update_post_meta($post_id, '_mta_leadgen_gated_gform_id', $mta_leadgen_gated_gform_id);

    $mta_leadgen_gated_access_period = wp_kses( $_POST['mta_leadgen_gated_access_period'], array() );
    if( isset( $mta_leadgen_gated_access_period ) )
      update_post_meta($post_id, '_mta_leadgen_gated_access_period', $mta_leadgen_gated_access_period);

    $mta_leadgen_gated_private_content = $_POST['mta_leadgen_gated_private_content'];
    if( isset( $mta_leadgen_gated_private_content ) )
      update_post_meta($post_id, '_mta_leadgen_gated_private_content', $mta_leadgen_gated_private_content);

    $mta_leadgen_gated_public_content = $_POST['mta_leadgen_gated_public_content'];
    if( isset( $mta_leadgen_gated_public_content ) )
      update_post_meta($post_id, '_mta_leadgen_gated_public_content', $mta_leadgen_gated_public_content);

    $mta_leadgen_gated_form_align = wp_kses( $_POST['mta_leadgen_gated_form_align'], array());
    if( isset( $mta_leadgen_gated_form_align ) )
      update_post_meta($post_id, '_mta_leadgen_gated_form_align', $mta_leadgen_gated_form_align);

    $mta_leadgen_gated_form_superheadline = wp_kses( $_POST['mta_leadgen_gated_form_superheadline'], $allowed_text);
    if( isset( $mta_leadgen_gated_form_superheadline ) )
      update_post_meta($post_id, '_mta_leadgen_gated_form_superheadline', $mta_leadgen_gated_form_superheadline);

    $mta_leadgen_gated_form_headline = wp_kses( $_POST['mta_leadgen_gated_form_headline'], $allowed_text);
    if( isset( $mta_leadgen_gated_form_headline ) )
      update_post_meta($post_id, '_mta_leadgen_gated_form_headline', $mta_leadgen_gated_form_headline);

    $mta_leadgen_gated_form_subheadline = wp_kses( $_POST['mta_leadgen_gated_form_subheadline'], $allowed_text);
    if( isset( $mta_leadgen_gated_form_subheadline ) )
      update_post_meta($post_id, '_mta_leadgen_gated_form_subheadline', $mta_leadgen_gated_form_subheadline);

    $mta_leadgen_gated_form_text = wp_kses( $_POST['mta_leadgen_gated_form_text'], $allowed_textarea);
    if( isset( $mta_leadgen_gated_form_text ) )
      update_post_meta($post_id, '_mta_leadgen_gated_form_text', $mta_leadgen_gated_form_text);

    /*
    $mta_leadgen_gated_ty_display = wp_kses( $_POST['mta_leadgen_gated_ty_display'], array() );
    if( isset( $mta_leadgen_gated_ty_display ) )
      update_post_meta($post_id, '_mta_leadgen_gated_ty_display', $mta_leadgen_gated_ty_display);
    */
  }

  /*
   * Register the stylesheets for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_styles() {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Mta_Gated_Content_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Mta_Gated_Content_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/styles.min.css', array(), $this->version, 'all' );

  }

  /**
   * Register the JavaScript for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts() {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Mta_Gated_Content_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Mta_Gated_Content_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    //wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/scripts.min.js', array( 'jquery' ), $this->version, false );
    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'src/js/scripts.js', array( 'jquery' ), $this->version, false );
  }
}
