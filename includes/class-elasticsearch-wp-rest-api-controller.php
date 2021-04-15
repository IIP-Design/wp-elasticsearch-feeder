<?php
/**
 * Class WP_ES_FEEDER_Callback_Controller
 *
 * Handles the callback from the ES API when the sync of a post completes or fails.
 *
 * @since 2.0.0
 */
class WP_ES_FEEDER_Callback_Controller {

  /**
   * @since 2.0.0
   */
  public function register_routes() {
    register_rest_route(
      $this->namespace,
      '/callback/(?P<uid>[0-9a-zA-Z]+)',
      array(
        array(
          'methods'             => WP_REST_Server::ALLMETHODS,
          'callback'            => array(
            $this,
            'processResponse',
          ),
          'args'                => array(
            'uid' => array(
              'validate_callback' => function ( $param, $request, $key ) {
                return true;
              },
            ),
          ),
          'permission_callback' => array(
            $this,
            'get_items_permissions_check',
          ),
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
  public function processResponse( $request ) {
    global $wpdb, $feeder;

    $logger      = new ES_Feeder\Admin\Helpers\Log_Helper();
    $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin );
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

    if ( ES_FEEDER::LOG_ALL ) {
      $logger->log( "INCOMING CALLBACK FOR UID: $uid, post_id: $post_id, sync_status: $sync_status\r\n" . print_r( $data, 1 ) . "\r\n", 'callback.log' );
      $logger->log( "Callback received with sync_status: $sync_status for: $post_id, uid: $uid", 'feeder.log' );
    }

    if ( $post_id == $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_uid' AND meta_value = '" . $wpdb->_real_escape( $uid ) . "'" ) ) {
      if ( ! $data['error'] ) {
        if ( ES_FEEDER::LOG_ALL ) {
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
              $feeder->post_sync_send( $post, false );
            } else {
              $feeder->delete( $post );
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
            $feeder->post_sync_send( $post, false );
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
        // $feeder->log( "INCOMING CALLBACK FOR UID: $uid, post_id: $post_id, sync_status: $sync_status\r\n" . print_r( $data, 1 ) . "\r\n", 'callback.log' );
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

      if ( ES_FEEDER::LOG_ALL ) {
        $logger->log( "Sync UID ($uid) deleted for: $post_id", 'feeder.log' );
      }
    } else {
      $logger->log( "UID ($uid) did not match post_id: $post_id\r\n\r\n", 'callback.log' );
    }

    return array( 'status' => 'ok' );
  }

  /**
   * @since 2.0.0
   */
  public function get_items_permissions_check( $request ) {
    return true;
  }

  /**
   * @since 2.0.0
   */
  public function get_item_permissions_check( $request ) {
    return true;
  }
}

// Add cdp-rest support for the base post type.
add_post_type_support( 'post', 'cdp-rest' );
