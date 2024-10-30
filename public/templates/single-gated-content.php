<?php
/**
 * The template for displaying gated_content single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @since 1.0.0
 * @version 1.0.0
 */

get_header(); ?>

<?php
$is_admin     = current_user_can( 'manage_options' ) ? 1 : 0;
$admin_class  = $is_admin ? 'single-gated-content-admin' : ''
?>

<div class="wrap">
  <div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
      <?php
      /* Start the Loop */
      while ( have_posts() ) : the_post();
        $public_content   = get_post_meta( $post->ID, '_mta_leadgen_gated_public_content', true );
        $private_content  = get_post_meta( $post->ID, '_mta_leadgen_gated_private_content', true );
        $gform_id         = get_post_meta($id, '_mta_leadgen_gated_gform_id', true);
        $gform_exists = GFAPI::form_id_exists($gform_id);
        $gform = GFFormDisplay::get_form($gform_id, false, false, false, array(), true, 999);
        ?>

        <div id="gated_content_wrapper" class="<?php print $admin_class ?>">
          <?php if($public_content) { ?>
            <?php ($is_admin) ? print "<h3 class='admin-headline public'>" . __( "Public content displayed on page load.", "mta-leadgengated" ) ."</h3>" : ""; ?>
            <div class="gated-content-public">
              <?php print $public_content; ?>
            </div>
          <?php }?>

          <?php if($gform_exists) {
            print $gform;
          }
          else {
            print "<div>Please select a valid form.</div>";
          }?>

          <?php ($is_admin) ? print "<h3 class='admin-headline private'>" . __( "Private content displayed after successful form submission.", "mta-leadgengated" ) ."</h3>" : ""; ?>
          <div class="gated-content-private">
            <?php print $private_content; ?>
          </div>
        </div>

      <?php endwhile; // End of the loop. ?>
    </main><!-- #main -->
  </div><!-- #primary -->
  <?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();
