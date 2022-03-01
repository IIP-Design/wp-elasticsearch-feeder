<?php
/**
 * Registers the Post_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Post_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers post helper functions.
 *
 * @package ES_Feeder\Admin\Helpers\Post_Helper
 * @since 3.0.0
 */
class Post_Helper {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @since 3.0.0
   */
  public function __construct() {
    $this->namespace = ES_FEEDER_API_NAMESPACE;
    $this->plugin    = ES_FEEDER_NAME;
  }

  /**
   * Remove a given post from the CDP.
   *
   * @param WP_Post $post   A WordPress post object.
   *
   * @since 1.0.0
   */
  public function delete( $post ) {
    $post_actions = new \ES_Feeder\Post_Actions( $this->namespace, $this->plugin );
    $sync_helper  = new Sync_Helper();

    if ( ! $sync_helper->is_syncable( $post->ID ) ) {
      return;
    }

    $statuses = $sync_helper->statuses;

    update_post_meta( $post->ID, '_cdp_sync_status', $statuses['SYNCING'] );

    $uuid       = $this->get_uuid( $post );
    $delete_url = $this->get_post_type_label( $post->post_type ) . '/' . $uuid;

    $options = array(
      'url'    => $delete_url,
      'method' => 'DELETE',
      'print'  => false,
    );

    $response = $post_actions->request( $options );

    if ( ! $response ) {
      error_log( print_r( $this->error . 'add_or_update()[add] request failed', true ) );
      update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
    } elseif ( isset( $response->error ) && $response->error ) {
      update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
    } else {
      update_post_meta( $post->ID, '_cdp_sync_status', $statuses['NOT_SYNCED'] );
    }

    delete_post_meta( $post->ID, '_cdp_sync_uid' );
  }

    /**
     * Index a given post to the CDP.
     *
     * @param WP_Post $post                   The give WordPress post object.
     * @param boolean $print                  Whether or not to send the response as JSON.
     * @param boolean $callback_errors_only   Whether to only use callback for errors(?).
     * @param boolean $check_syncable         Whether or not check for sync-ability before updating.
     *
     * @since 1.0.0
     */
  public function add_or_update( $post, $print = true, $callback_errors_only = false, $check_syncable = true ) {
    $post_actions = new \ES_Feeder\Post_Actions( $this->namespace, $this->plugin );
    $api_helper   = new API_Helper( $this->namespace, $this->plugin );
    $log_helper   = new Log_Helper();
    $sync_helper  = new Sync_Helper();

    $statuses = $sync_helper->statuses;

    if ( $check_syncable && ! $sync_helper->is_syncable( $post->ID ) ) {
      $response = array(
        'error'   => 1,
        'message' => 'Could not publish while publish in progress.',
      );

      if ( $print ) {
        wp_send_json( $response );
      }

      return $response;
    }

    // Plural form of post type.
    $post_type_name = $api_helper->get_post_type_label( $post->post_type, 'name' );

    // API endpoint for wp-json.
    $wp_api_url   = '/' . $this->namespace . '/' . rawurlencode( $post_type_name ) . '/' . $post->ID;
    $request      = new \WP_REST_Request( 'GET', $wp_api_url );
    $api_response = rest_do_request( $request );
    $api_response = $api_response->data;

    if ( ! $api_response || isset( $api_response['code'] ) ) {
      error_log( print_r( $this->error . 'add_or_update() calling wp rest failed', true ) );
      $api_response['error'] = true;
      $api_response['url']   = $wp_api_url;

      if ( $print ) {
        wp_send_json( $api_response );
      }

      return $api_response;
    }

    // Create callback for this post.
    $callback = $this->create_callback( $post->ID );

    $options = array(
      'url'    => $this->get_post_type_label( $post->post_type ),
      'method' => 'POST',
      'body'   => $api_response,
      'print'  => $print,
    );

    $response = $post_actions->request( $options, $callback, $callback_errors_only );

    if ( $log_helper->log_all ) {
      $log_helper->log( "IMMEDIATE RESPONSE:\r\n" . print_r( $response, 1 ), 'callback.log' );
    }

    if ( ! $response ) {
      error_log( print_r( $this->error . 'add_or_update()[add] request failed', true ) );
      update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
      delete_post_meta( $post->ID, '_cdp_sync_uid' );
    } elseif ( isset( $response->error ) && $response->error ) {
      update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
      delete_post_meta( $post->ID, '_cdp_sync_uid' );

      if ( ! $log_helper->log_all && $response ) {
        $log_helper->log( "IMMEDIATE RESPONSE:\r\n" . print_r( $response, 1 ), 'callback.log' );
      }
    }

    return $response;
  }

  /**
   * Get a list of indexable post types.
   *
   * @return array    List of post types.
   *
   * @since 2.0.0
   */
  public function get_allowed_post_types() {
    $settings = get_option( $this->plugin );
    $types    = array();

    if ( $settings && $settings['es_post_types'] ) {
      foreach ( $settings['es_post_types'] as $post_type => $val ) {
        if ( $val ) {
          $types[] = $post_type;
        }
      }
    }

    return $types;
  }

  /**
   * Construct UUID which is site domain delimited by dashes and not periods, underscore, and post ID.
   *
   * @param WP_Post $post   A WordPress post object.
   * @return string         Unique id value.
   *
   * @since 2.0.0
   */
  public function get_uuid( $post ) {
    $post_id = $post;

    if ( ! is_numeric( $post_id ) ) {
      $post_id = $post->ID;
    }

    $opt  = get_option( $this->plugin );
    $url  = $opt['es_wpdomain'];
    $args = wp_parse_url( $url );
    $host = $url;

    if ( array_key_exists( 'host', $args ) ) {
      $host = $args['host'];
    } else {
      $host = str_ireplace( 'https://', '', str_ireplace( 'http://', '', $host ) );
    }

    return "{$host}_{$post_id}";
  }

  /**
   * Route the post updates to the correct post action.
   *
   * @param WP_Post $post    A WordPress post object.
   * @param boolean $print   Whether or not to send the response as JSON.
   *
   * @since 2.1.0
   */
  public function post_sync_send( $post, $print = true ) {
    $index_meta   = get_post_meta( $post->ID, '_iip_index_post_to_cdp_option', true );
    $should_index = ! empty( $index_meta ) ? $index_meta : 'yes';

    if ( 'no' === $should_index ) {
      $this->delete( $post );
    } else {
      $this->add_or_update( $post, $print );
    }
  }

  /**
   * Retrieves the singular post type label for use in API end points.
   * Some post types are registered as plural but we want to use singular end point URLs.
   *
   * @param string $post_type    A given WordPress post type.
   * @return string              The singular label assigned to the given post type.
   *
   * @since 2.0.0
   */
  public function get_post_type_label( $post_type ) {
    $obj = get_post_type_object( $post_type );

    if ( ! $obj ) {
      return $post_type;
    }

    return $obj->labels->singular_name;
  }

  /**
   * Generates the a callback endpoint URL.
   *
   * @param int $post_id   A given WordPress post id.
   * @return string        The callback url for failed requests.
   *
   * @since 2.1.0
   */
  public function create_callback( $post_id = null ) {
    $log_helper  = new Log_Helper();
    $sync_helper = new Sync_Helper();

    $statuses = $sync_helper->statuses;

    $options = get_option( $this->plugin );
    $domain  = $options['es_wpdomain'] ? $options['es_wpdomain'] : site_url();

    if ( ! $post_id ) {
      return $domain . '/wp-json/' . $this->namespace . '/callback/noop';
    }

    // What is this trying to do?
    // do {
    // $uid   = uniqid();
    // $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_uid' AND meta_value = '$uid'";
    // } while ( $wpdb->get_var( $query ) );

    $uid = uniqid();

    // Create callback for this post.
    $callback = $domain . '/wp-json/' . $this->namespace . '/callback/' . $uid;

    $log_helper->log( "Created callback for: $post_id with UID: $uid" );

    update_post_meta( $post_id, '_cdp_sync_uid', $uid );
    update_post_meta( $post_id, '_cdp_sync_status', $statuses['SYNCING'] );
    update_post_meta( $post_id, '_cdp_last_sync', gmdate( 'Y-m-d H:i:s' ) );

    return $callback;
  }
}
