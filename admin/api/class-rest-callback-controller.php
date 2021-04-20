<?php
/**
 * Registers the Elasticsearch callback API endpoint.
 *
 * @package ES_Feeder\Admin\API\REST_Callback_Controller
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#the-controller-pattern
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\API;

use WP_REST_Controller;

/**
 * Handles the callback from the ES API when the sync of a post completes or fails.
 *
 * @package ES_Feeder\Admin\API\REST_Callback_Controller
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#the-controller-pattern
 * @since 3.0.0
 */
class REST_Callback_Controller extends WP_REST_Controller {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin      The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin ) {
    $this->namespace = $namespace;
    $this->plugin    = $plugin;
  }

  /**
   * Registers a REST API routes to accept callback responses from Elasticsearch.
   *
   * @since 2.0.0
   */
  public function register_routes() {
    register_rest_route(
      $this->namespace,
      '/callback/(?P<uid>[0-9a-zA-Z]+)',
      array(
        array(
          'methods'             => \WP_REST_Server::ALLMETHODS,
          'callback'            => array( $this, 'process_response' ),
          'args'                => array(
            'uid' => array(
              'validate_callback' => function ( $param, $request, $key ) {
                return true;
              },
            ),
          ),
          'permission_callback' => '__return_true',
        ),
      )
    );
  }

  /**
   * @param $request WP_REST_Request
   * @return array
   *
   * @since 2.0.0
   */
  public function process_response( $request ) {
    global $wpdb;

    $logger      = new \ES_Feeder\Admin\Helpers\Log_Helper();
    $post_helper = new \ES_Feeder\Admin\Helpers\Post_Helper( $this->plugin );
    $sync_helper = new \ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin );
    $statuses    = $sync_helper->statuses;

    $data = $request->get_json_params();

    if ( ! $data ) {
      $data = $request->get_body_params();
    }

    $uid     = $request->get_param( 'uid' );
    $post_id = null;

    if ( array_key_exists( 'doc', $data ) ) {
      $post_id = $data['doc']['post_id'];
    } elseif ( array_key_exists( 'request', $data ) && array_key_exists( 'post_id', $data['request'] ) ) {
      $post_id = $data['request']['post_id'];
    } elseif ( array_key_exists( 'params', $data ) && array_key_exists( 'post_id', $data['params'] ) ) {
      $post_id = $data['params']['post_id'];
    }

    $sync_status = get_post_meta( $post_id, '_cdp_sync_status', true );

    if ( $logger->log_all ) {
      $logger->log( "INCOMING CALLBACK FOR UID: $uid, post_id: $post_id, sync_status: $sync_status\r\n" . print_r( $data, 1 ) . "\r\n", 'callback.log' );
      $logger->log( "Callback received with sync_status: $sync_status for: $post_id, uid: $uid", 'feeder.log' );
    }

    if ( $post_id == $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_uid' AND meta_value = '" . $wpdb->_real_escape( $uid ) . "'" ) ) {
      if ( ! $data['error'] ) {
        if ( $logger->log_all ) {
          $logger->log( "No error found for $post_id, sync_uid: $uid", 'feeder.log' );
        }
        if ( $statuses['SYNC_WHILE_SYNCING'] === $sync_status ) {
          $resyncs = get_post_meta( $post_id, '_cdp_resync_count', true ) ?: 0;
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['RESYNC'] );
          if ( $resyncs < 3 ) {
            $resyncs++;
            $logger->log( "Resyncing post: $post_id, resync #$resyncs", 'callback.log' );
            update_post_meta( $post_id, '_cdp_resync_count', $resyncs );
            $post = get_post( $post_id );
            if ( 'publish' === $post->post_status ) {
              $post_helper->post_sync_send( $post, false );
            } else {
              $post_helper->delete( $post );
            }
          }
        } else {
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['SYNCED'] );
          delete_post_meta( $post_id, '_cdp_resync_count' );
        }
      } elseif ( stripos( $data['message'], 'Document not found' ) === 0 ) {
        $post_status = $wpdb->get_var( "SELECT post_status FROM $wpdb->posts WHERE ID = $post_id" );
        $index_cdp   = get_post_meta( $post_id, '_iip_index_post_to_cdp_option', true ) ?: 'yes';
        if ( 'publish' === $post_status && 'no' !== $index_cdp ) {
          $resyncs = get_post_meta( $post_id, '_cdp_resync_count', true ) ?: 0;
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['RESYNC'] );
          if ( $resyncs < 3 ) {
            $resyncs++;
            $logger->log( "Resyncing post: $post_id, resync #$resyncs", 'callback.log' );
            update_post_meta( $post_id, '_cdp_resync_count', $resyncs );
            $post = get_post( $post_id );
            $post_helper->post_sync_send( $post, false );
          } else {
            update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
            delete_post_meta( $post_id, '_cdp_resync_count' );
          }
        } elseif ( 'publish' !== $post_status || 'no' === $index_cdp ) {
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['NOT_SYNCED'] );
          delete_post_meta( $post_id, '_cdp_resync_count' );
        } else {
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
          delete_post_meta( $post_id, '_cdp_resync_count' );
        }
      } else {
        $log = null;
        if ( $data['message'] ) {
          $log = "Incoming Callback: $uid - ID: $post_id - ";
          if ( $data['error'] ) {
            $log .= 'Error: ' . $data['message'];
          } else {
            $log .= 'Message: ' . $data['message'];
          }
        } elseif ( ! array_key_exists( 'request', $data ) ) {
          $log = array(
            'type'        => 'Incoming Callback',
            'uid'         => $uid,
            'post_id'     => $post_id,
            'sync_status' => $sync_status,
          );
          $log = array_merge( $log, $data );
        }
        $logger->log( $log, 'callback.log' );
        update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
        delete_post_meta( $post_id, '_cdp_resync_count' );
      }

      $wpdb->delete(
        $wpdb->postmeta,
        array(
          'meta_key'   => '_cdp_sync_uid',
          'meta_value' => $uid,
        )
      );

      if ( $logger->log_all ) {
        $logger->log( "Sync UID ($uid) deleted for: $post_id", 'feeder.log' );
      }
    } else {
      $logger->log( "UID ($uid) did not match post_id: $post_id\r\n\r\n", 'callback.log' );
    }

    return array( 'status' => 'ok' );
  }
}
