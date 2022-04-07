<?php
/**
 * Registers the Uninstall class.
 *
 * @package ES_Feeder\Uninstall
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Register all hooks to be run when the plugin is removed.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once site-wide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @package ES_Feeder\Uninstall
 * @since 3.0.0
 */
class Uninstall {

  /**
   * Run cleanup to delete plugin data upon uninstall.
   *
   * @since 3.0.0
   */
  public static function uninstall() {

    // Ensure user has the proper permissions.
    if ( ! current_user_can( 'delete_plugins' ) ) {
      return;
    }

    if ( ! is_multisite() ) {

      self::remove_options();
      self::clean_postmeta();

    } else {

      // For a multisite you have to iterate through all the blogs to run uninstall hooks.
      $sites_query_args = array(
        'fields' => 'ids',
      );

      $blog_ids     = get_sites( $sites_query_args );
      $current_blog = get_current_blog_id();

      // Iterate through all blogs running deactivation hooks.
      foreach ( $blog_ids as $id ) {
        switch_to_blog( $id );

        self::remove_options();
        self::clean_postmeta();
      }

      unset( $id );

      // Switch back to .
      switch_to_blog( $current_blog );
    }
  }

  /**
   * Delete the plugin's options from the options table in the database.
   *
   * @since 2.4.1
   */
  private static function remove_options() {
    $plugin_name = 'wp-es-feeder';

    delete_option( $plugin_name );
    delete_option( $plugin_name . '_syncable_posts' );
    delete_option( 'cdp_languages' );
    delete_option( 'cdp_owners' );
  }

  /**
   * Delete the plugin-specific post metadata from all posts.
   *
   * @since 2.0.0
   */
  private static function clean_postmeta() {
    global $wpdb;

    // Running plugin clean function can be slow.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
    // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_status' ) );
    $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_uid' ) );
    $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_queue' ) );
    // phpcs:enable
  }
}
