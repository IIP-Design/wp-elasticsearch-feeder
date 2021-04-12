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
 * @link       http://github.com/MaxOrelus
 * @since      1.0.0
 *
 * @package    ES_Feeder
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function wp_es_clear_data() {
  $PLUGIN_NAME = 'wp-es-feeder';
  delete_option( $PLUGIN_NAME );
  delete_option( $PLUGIN_NAME . '_syncable_posts' );
  delete_option( 'cdp_languages' );
  delete_option( 'cdp_owners' );

  global $wpdb;
  $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_status' ) );
  $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_uid' ) );
  $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_queue' ) );
}


if ( is_multisite() ) {
  $current  = get_current_blog_id();
  $site_ids = get_sites( array( 'fields' => 'ids' ) );
  foreach ( $site_ids as $site_id ) {
    switch_to_blog( $site_id );
    wp_es_clear_data();
  }
  switch_to_blog( $current );
} else {
  wp_es_clear_data();
}
