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
use ES_Feeder\Admin\Helpers\Log_Helper as Logger;
use ES_Feeder\Admin\Helpers\Post_Helper as Poster;
use ES_Feeder\Admin\Helpers\Sync_Helper as Sync;
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
    $this->logger    = new Logger();
    $this->poster    = new Poster();
    $this->sync      = new Sync();
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
              'required'          => true,
              'validate_callback' => function ( $param ) {
                return 'string' === gettype( $param );
              },
            ),
          ),
          'permission_callback' => '__return_true',
        ),
      )
    );
  }

  /**
   * Handle data received by the callback endpoint.
   *
   * @param WP_REST_Request $request   The request received by the callback endpoint.
   * @return array                     A response to be sent back.
   *
   * @since 2.0.0
   */
  public function process_response( $request ) {
    $params = $request->get_params();

    $uid = $params['uid'];
    $id  = null;

    // Retrieve the post id from the Elasticsearch document in the API response.
    // TODO: Unsure why the second and third checks are necessary.
    if ( array_key_exists( 'doc', $params ) ) {
      $id = $params['doc']['post_id'];
    } elseif ( array_key_exists( 'request', $params ) && array_key_exists( 'post_id', $params['request'] ) ) {
      $id = $params['request']['post_id'];
    } elseif ( array_key_exists( 'params', $params ) && array_key_exists( 'post_id', $params['params'] ) ) {
      $id = $params['params']['post_id'];
    }

    if ( null === $id ) {
      $this->logger->log( 'CDP Sync Error - No id provided in the request for sync id' . $uid );

      exit;
    }

    $sync_status  = get_post_meta( $id, '_cdp_sync_status', true );
    $sync_decoded = $this->sync->get_status_code_data( $sync_status );
    $post_type    = get_post_type( $id );

    $this->logger->log(
      "Callback with the unique id $uid received from the CDP API for $post_type with id $id. Current sync status: " . $sync_decoded['title']
    );

    // Abort early if the provided unique id does not match that saved for the post.
    if ( get_post_meta( $id, '_cdp_sync_uid', true ) !== $uid ) {
      $this->logger->log( "The provided UID ($uid) did not match the stored sync id for $post_type #$id." );

      exit;
    }

    // Handle error cases.
    if ( $params['error'] ) {
      $this->handle_errors( $id, $params );

      exit;
    }

    // Log that there are no errors.
    $this->logger->log( "The sync id ($uid) matches $post_type #$id, proceeding with update." );

    // Initiate the sync/resync.
    if ( $this->sync->statuses['SYNC_WHILE_SYNCING'] === $sync_status ) {
      $this->resync_post( $id );
    } else {
      $this->set_status( $id, 'SYNCED' );
    }

    $this->logger->log( "Sync of $post_type #$id successful, deleting sync id ($uid)." );

    // Clear the sync id record.
    delete_post_meta( $id, '_cdp_sync_uid' );

    return array( 'status' => 'ok' );
  }

  /**
   * Logs any syncing errors.
   *
   * @param int   $id        A given post id.
   * @param array $params    The parameters passed in the CDP API response.
   * @return void
   *
   * @since 3.0.0
   */
  private function handle_errors( $id, $params ) {
    // General error if no error message received.
    if ( ! $params['message'] ) {
      $this->log_sync_error( 'Unspecified error.' );

      exit;
    }

    // Handle the case in which the post is not found in the CDP API.
    if ( stripos( $params['message'], 'Document not found' ) === 0 ) {
      $this->log_sync_error( 'Elasticsearch document not found in the CDP API.' );

      $post_status  = get_post_status( $id );
      $is_indexable = get_post_meta( $id, '_iip_index_post_to_cdp_option', true );
      $index_to_cdp = ! empty( $is_indexable ) ? $is_indexable : 'yes';

      if ( 'no' === $index_to_cdp ) {
        // If the post not meant to be index set the status to not synced.
        $this->set_status( $id, 'NOT_SYNCED' );
      } elseif ( 'publish' === $post_status ) {
        // If post indexable and published attempt resync.
        $count = $this->resync_post( $id );

        // Set sync status to error if the maximum of
        // three resync attempts is exceeded.
        if ( 3 === $count ) {
          $this->set_status( $id, 'ERROR' );
        }
      } else {
        // If the post is not published on the WordPress site, set sync status to error.
        $this->set_status( $id, 'ERROR' );
      }
    } else {
      // Handle the case in which the post is not found in the CDP API.
      $this->log_sync_error( $params['message'] );
      $this->set_status( $id, 'ERROR' );
    }
  }

  /**
   * Start all error messages with the same prefix.
   *
   * @param string $msg The message to be prefixed with a standard error message.
   * @return void
   *
   * @since 3.0.0
   */
  private function log_sync_error( $msg ) {
    $prefix = 'CDP Sync Error - ';

    $this->logger->log( $prefix . $msg );
  }

  /**
   * Attempt to resync the post.
   *
   * @param int $id     A given post id.
   * @return int        The number of resync attempts.
   *
   * @since 3.0.0
   */
  private function resync_post( $id ) {
    // Check for previous resync attempts.
    $stored_count = get_post_meta( $id, '_cdp_resync_count', true );
    $resync_count = ! empty( $stored_count ) ? $stored_count : 0;

    $this->set_status( $id, 'RESYNC', false );

    // Only attempt resync if there were 3 or fewer previous attempts.
    if ( $resync_count < 3 ) {
      // Increment the number of resync attempts.
      $resync_count++;

      $post = get_post( $id );

      $this->logger->log( "Attempting to re-syncing $post->post_type #$id, resync attempt #$resync_count." );

      update_post_meta( $id, '_cdp_resync_count', $resync_count );

      if ( 'publish' === $post->post_status ) {
        $this->poster->post_sync_send( $post, false );
      } else {
        $this->poster->delete( $post );
      }
    }

    return $resync_count;
  }

  /**
   * Updates the sync status for a given post.
   *
   * @param int    $id           A given post id.
   * @param string $status       The CDP sync status that the post should be set to.
   * @param bool   $clear_count  Whether or not to clear the resync counter.
   * @return void
   *
   * @since 3.0.0
   */
  private function set_status( $id, $status, $clear_count = true ) {
    update_post_meta( $id, '_cdp_sync_status', $this->sync->statuses[ $status ] );

    if ( $clear_count ) {
      delete_post_meta( $id, '_cdp_resync_count' );
    }
  }
}
