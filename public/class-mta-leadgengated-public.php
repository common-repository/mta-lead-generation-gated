<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.madtownagency.com
 * @since      1.0.0
 *
 * @package    mta_leadgengated
 * @subpackage mta_leadgengated/public
 */

/**
 * The public-facing functionality of the plugin.s
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    mta_leadgengated
 * @subpackage mta_leadgengated/public
 * @author     Ryan Baron <ryan@madtownagency.com>
 */
class Mta_leadgengated_Public {

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
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_name       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;

    add_filter( 'body_class', array($this, 'mta_leadgengated_body_classes' ));
    add_action( 'wp_ajax_display_gated_content', array($this, 'display_gated_content' ) );
    add_action( 'wp_ajax_nopriv_display_gated_content', array($this, 'display_gated_content') );
  }

  /**
   * Cehck if the UUID already has an entry access for the specific gated ID
   *
   * @since   1.0.0
   * @param   string    $uuid
   * @param   string    $gated_id
   *
   * @return  boolean/array - true/false if the entry exists or not, or the returned query array
   */
  function uuid_access_entry_exists($uuid = '', $gated_id = 0) {
    global $wpdb;

    //return false if no uuid or gated id is passed in
    if(empty($uuid) || !$gated_id) return false;

    //check if the user uuid is in the database
    $query = $wpdb->prepare( 'SELECT * FROM %1$s WHERE uuid = "%2$s" AND gated_id = %3$d', "{$wpdb->prefix}mta_leadgen_user_access", $uuid, $gated_id );
    $access_entry_results = $wpdb->get_results( $query );

    if(is_array($access_entry_results) && count($access_entry_results)) {
      return true; //return true, an entry exists
    }

    return false; //return false, no entry exists
  }

  /**
   * Cehck if a UUID entry already exists in the database
   *
   * @since   1.0.0
   * @param   string    $uuid
   *
   * @return  boolean/array - true/false if the entry exists or not, or the returned query array
   */
  function uuid_exists($uuid = '', $return_results = false) {
    global $wpdb;

    //return false if no uuid is passed in
    if(empty($uuid)) return false;

    //check if the user uuid is in the database
    $query = $wpdb->prepare( 'SELECT * FROM %1$s WHERE uuid = "%2$s"', "{$wpdb->prefix}mta_leadgen_user",  $uuid );
    $user_results = $wpdb->get_results( $query );

    if(is_array($user_results) && count($user_results)) {
      if($return_results) {
        return $user_results; //return the query results
      }
      else {
        return true; //return true, an entry exists
      }
    }
    //No entries were found
    if($return_results) {
      return $user_results; //return the query results (empty array in this case)
    }
    else {
      return false; //return false, no entry exists
    }
  }

  /**
   * Check if a UUID has access to the specific gated content
   *
   * @since   1.0.0
   * @param   string    $uuid
   * @param   string    $gated_id
   *
   * @return  boolean - true/false if the entry exists or not
   */
  function uuid_access_content($gated_id, $uuid = '') {
    global $wpdb;
    $now_ts = current_time('timestamp', true);  //get the local time Value

    if(empty($uuid)) return false;

    //check if the user uuid has access to the gated content
    $query = $wpdb->prepare( '
    SELECT * FROM %1$s WHERE uuid = "%2$s" AND gated_id = %3$d', "{$wpdb->prefix}mta_leadgen_user_access",  $uuid, $gated_id );
    $access_results = $wpdb->get_results( $query );

    if(is_array($access_results) && count($access_results)) {

      //get the date the uuid has access to the entry until
      $access_until = isset($access_results[0]->access_until) ? $access_results[0]->access_until : 0;

      //check if the access period has expired
      if($access_until > $now_ts) {
        return true; //return true, the uuid has access to the content
      }
      else {
        return false; //return false, the uuid had access but their access period has expired
      }
    }

    return false; //return false, no uuid access entry exists
  }

  /**
   * Create a gated entry access db entry, if one already exists then update it
   *
   * @since   1.0.0
   * @param   string    $uuid
   * @param   string    $gated_id
   * @param   string    $access_id
   * @param   string    $access_until
   *
   * @return  boolean   true if entry created or updated, false if not
   */
  function create_uuid_access_entry($uuid, $gated_id, $access_id, $access_until = 0) {

    /*ToDo: currently when a user revisits the page within the access period their access until time is being updated, this needs to be corrected to only update when they refill out the form after their access period has expired*/

    global $wpdb;
    $now = current_time('timestamp', true);

    //check if an entry already exists for this uuid and gated content
    $existing_entry = $this->uuid_access_entry_exists($uuid, $gated_id);

    //if there is no entry existing, then create a new entry
    if(!$existing_entry) {
      //there is NOT an access entry for this uuid and gated id in the database
      $insert_access = $wpdb->insert(
        $wpdb->prefix . 'mta_leadgen_user_access',
        array(
          'uuid' => $uuid,
          'gated_id' => $gated_id,
          'access_id' => $access_id,
          'access_until' => $access_until,
          'created_on' => $now,
          'last_access_on' => $now,
        ),
        array(
          '%s',
          '%d',
          '%d',
          '%d',
          '%d',
          '%d',
        )
      );

      return $insert_access;
    }
    else {
      //an access entry already exists, update the last access and access until data in the db entry
      if($access_until) {
        $update_access = $wpdb->update(
          $wpdb->prefix . 'mta_leadgen_user_access',
          array(
            'last_access_on' => $now,
            'access_until' => $access_until,
            'access_id' => $access_id,
          ),
          array(
            'uuid' => $uuid,
            'gated_id' => $gated_id,
          ),
          array(
            '%d',
            '%d',
            '%d',
            '%s',
            '%d',
          )
        );
      }
      else {
        $update_access = $wpdb->update(
          $wpdb->prefix . 'mta_leadgen_user_access',
          array(
            'last_access_on' => $now,
            'access_id' => $access_id,
          ),
          array(
            'uuid' => $uuid,
            'gated_id' => $gated_id,
          ),
          array(
            '%d',
            '%d',
            '%s',
            '%d',
          )
        );
      }

      return $update_access;
    }
    return false;
  }

  function update_uuid_access_entry_time($uuid, $gated_id, $accessed_on) {
    global $wpdb;

    //there is NOT an access entry for this uuid and gated id in the database
    $update_access = $wpdb->update(
      $wpdb->prefix . 'mta_leadgen_user_access',
      array(
      'last_access_on' => $accessed_on,
      ),
      array(
        'uuid' => $uuid,
        'gated_id' => $gated_id,
      ),
      array(
        '%d',
        '%s',
        '%d',
      )
    );

    return $update_access;
  }

  function create_uuid_entry($uuid, $fname = '', $lname = '', $cname = '', $phone = '', $email = '') {
    global $wpdb;

    if(empty($uuid)) {
      return false;
    }

    $now = current_time('timestamp', true);
    $current_entry = $this->uuid_exists($uuid, true);
    $current_entry_fname = isset($current_entry[0]->fname) ? $current_entry[0]->fname : '';
    $current_entry_lname = isset($current_entry[0]->lname) ? $current_entry[0]->lname : '';
    $current_entry_cname = isset($current_entry[0]->company) ? $current_entry[0]->company : '';
    $current_entry_email = isset($current_entry[0]->email) ? $current_entry[0]->email : '';
    $current_entry_phone = isset($current_entry[0]->phone) ? $current_entry[0]->phone : '';

    if(count($current_entry)) {
      //if the entry already exists we are updating entry content, however if the user previously filled in a form that collects their email or phone and the second form they fill out doesnt include email or phone, we want to preserve that data and not overwite it with nothing.
      $fname = !empty($fname) ? $fname : $current_entry_fname;
      $lname = !empty($lname) ? $lname : $current_entry_lname;
      $cname = !empty($cname) ? $cname : $current_entry_cname;
      $email = !empty($email) ? $email : $current_entry_email;
      $phone = !empty($phone) ? $phone : $current_entry_phone;

      //if the entry already exists, update it
      $update_access = $wpdb->update(
        $wpdb->prefix . 'mta_leadgen_user',
        array(
          'fname' => $fname,
          'lname' => $lname,
          'company' => $cname,
          'phone' => $phone,
          'email' => $email,
        ),
          array(
          'uuid' => $uuid,
        ),
          array(
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
        )
      );

      /* ToDo: put a last access field on the table and update that, for tracking and returning true when we need it */
      return true;  //return true, an updated doesnt have to take place (if the users information does not change), but we still want to return true
    }
    else {
      //create a new uuid entry
      $insert_access = $wpdb->insert(
        $wpdb->prefix . 'mta_leadgen_user',
        array(
          'uuid' => $uuid,
          'fname' => $fname,
          'lname' => $lname,
          'company' => $cname,
          'phone' => $phone,
          'email' => $email,
          'created_on' => $now,
        ),
          array(
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%d',
        )
      );

      return $insert_access;
    }

    return false;
  }

  function display_gated_content() {
    global $wpdb;

    $mta_leadgen_options = get_option( 'mta_leadgen_gated_content_options', array() );
    $view_tracking = isset($mta_leadgen_options['mta_leadgengated_analytics_view_tracking']) ? $mta_leadgen_options['mta_leadgengated_analytics_view_tracking'] : 0;
    $user_tracking = isset($mta_leadgen_options['mta_leadgengated_analytics_user_view_tracking']) ? $mta_leadgen_options['mta_leadgengated_analytics_user_view_tracking'] : 0;


    //get time information
    $now_ts       = current_time('timestamp', true);  //get the GMT timestamp value
    $created_on   = $now_ts;
    $accessed_on  = $now_ts;

    //get post infromation from the ajax call
    $uuid           = isset($_POST['uuid']) && !empty($_POST['uuid'])                   ? $_POST['uuid']          : '';
    $fname          = isset($_POST['fname']) && !empty($_POST['fname'])                 ? $_POST['fname']         : '';
    $lname          = isset($_POST['lname']) && !empty($_POST['lname'])                 ? $_POST['lname']         : '';
    $cname          = isset($_POST['cname']) && !empty($_POST['cname'])                 ? $_POST['cname']         : '';
    $email          = isset($_POST['email']) && !empty($_POST['email'])                 ? $_POST['email']         : '';
    $phone          = isset($_POST['phone']) && !empty($_POST['phone'])                 ? $_POST['phone']         : '';
    $gated_id       = isset($_POST['gated_id']) && !empty($_POST['gated_id'])           ? $_POST['gated_id']      : 0;
    $access_id      = isset($_POST['access_id']) && !empty($_POST['access_id'])         ? $_POST['access_id']     : $post_id;
    $create_access  = isset($_POST['create_access']) && !empty($_POST['create_access']) ? $_POST['create_access'] : 0;
    $has_access     = $this->uuid_access_content($gated_id, $uuid);

    $name = !empty($fname) ? $fname : '';
    $name .= !empty($lname) ? ' ' . $lname : '';

    //calculate the access until time/endtime
    $access_period = !empty(get_post_meta($gated_id, '_mta_leadgen_gated_access_period', true)) ? get_post_meta($gated_id, '_mta_leadgen_gated_access_period', true) : 86400;
    $access_until = $now_ts + ( $access_period );

    //get the gated content from the gated content post
    $gated_content = get_post_meta($gated_id, '_mta_leadgen_gated_private_content', true );
    $data = "<div class='gated-content-private'>$gated_content</div>";

    //this will create or update the uuid database entry (returns true if entry is created or updated)
    $uuid_entry = $this->create_uuid_entry($uuid, $fname, $lname, $cname, $phone, $email, $created_on);

    $name           = '';
    $company        = '';
    $post_title     = '';
    $analytics_data = array();
    $current_entry  = $this->uuid_exists($uuid, true);

    if(is_array($current_entry) && count($current_entry)) {
      $name       = !empty($current_entry[0]->fname)    ? $current_entry[0]->fname : '';
      $name      .= !empty($current_entry[0]->lname)    ? ' ' . $current_entry[0]->lname : '';
      $email      = !empty($current_entry[0]->email)    ? ' ( ' . $current_entry[0]->email . ' )' : '';
      $company    = !empty($current_entry[0]->company)  ? $current_entry[0]->company : '';
      $post_title = get_the_title($gated_id);

      $analytics_data = array(
        'name'          => $name,
        'email'         => $email,
        'company'       => $company,
        'post_title'    => $post_title,
        'has_access'    => $has_access,
        'view_tracking' => $view_tracking,
        'user_tracking' => $user_tracking
      );
    }

    //this will create or updated database access entry
    $uuid_entry_access = 0;
    if($create_access) {
      $uuid_entry_access = $this->create_uuid_access_entry($uuid, $gated_id, $access_id, $access_until);
    } elseif($has_access) {
      $uuid_entry_access = $this->create_uuid_access_entry($uuid, $gated_id, $access_id);
    }

    //check if the user has access (may have been created above or already existing)
    if($uuid_entry && $gated_id && ($uuid_entry_access || $has_access )) {
      $response = array(
        'type'      => 'success',
        'data'      => $data,
        'analytics' => $analytics_data
      );
    } else {
      $response = array(
        'type'      => 'fail',
        'data'      => $gated_id,
        'analytics' => $analytics_data
      );
    }

    //encode and echo the final response
    $response = json_encode($response);
    echo $response;
    die();
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_styles() {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Mta_leadgengated_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Mta_leadgengated_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/styles.min.css', array(), $this->version, 'all' );

  }

  /**
   * Register the JavaScript for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts() {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Mta_leadgengated_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Mta_leadgengated_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/scripts.min.js', array( 'jquery' ), $this->version, true );
    //wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'src/js/scripts.js', array( 'jquery' ), $this->version, true );
    wp_localize_script( $this->plugin_name, 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

  }

  function mta_leadgengated_body_classes( $classes ) {
    if( !isset($post_id) || empty($post_id) )
      $post_id = get_the_ID();

    if( isset($post_id) && !empty($post_id) ) {
      $layout = get_post_meta($post_id, '_mta_leadgengated_layout', true);

      //only add a class if _mta_leadgengated_layout is set
      if(!empty($layout)) {
        $classes[] = 'mta-leadgengated';
      }
    }
    return $classes;
  }


  /*
  function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }
  */

  /**
   * Extract the gated_content id form the body shortcode body of a post (id passed in)
   *
   * @since   1.0.0
   * @param   string    $id   Post ID.
   *
   * @return  string    $id   The gated content id extracted from the gated_content shortcode in a post body, return 0 if none found
   */
  /*
  function extract_gated_content_id($id) {
    $gated_id   = 0;

    //get the post content
    $post_object  = get_post( $id );
    $post_content = $post_object->post_content;

    //get all of the shortcodes in the post body (i.e. content betweeen [])
    preg_match('#\[(.*?)\]#', $post_content, $match);

    foreach($match as $key => $shortcode){
      //normalize all shortcodes to use '
      $shortcode = str_replace('"', '\'', $shortcode);

      //get the correct shortcode
      if (strlen(strstr($shortcode, "gated_id"))>0 && strlen(strstr($shortcode, "mta_gated_content"))>0)
        $gated_id = $this->get_string_between($shortcode, "gated_id='", "'");

      //if we found a match break out of the foreach loop
      if($gated_id)
        break;
    }

    return $gated_id;
  }
  */

}
