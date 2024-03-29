<?php
/**
 * Registers the Ajax class.
 *
 * @package ES_Feeder\Ajax
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Handles AJAX calls needed to persist data on the server.
 *
 * @package ES_Feeder\Ajax
 * @since 3.0.0
 */
class Ajax {

  /**
   * The unique identifier this plugin.
   *
   * @var string $plugin
   *
   * @access protected
   * @since 3.0.0
   */
  protected $plugin;

  /**
   * Initializes the class with the plugin name and version.
   *
   * @since 3.0.0
   */
  public function __construct() {
    $this->plugin = ES_FEEDER_NAME;
  }

  /**
   * Triggered by heartbeat AJAX event, added the sync status indicator HTML if the data includes
   * es_sync_status which contains a post ID and will be converted to the sync status indicator HTML.
   *
   * If the data includes gpalab_feeder_count, send back an array of counts for each status ID.
   *
   * @param array $response   The response returned to the server from the heartbeat.
   * @param array $data       The $_POST data sent.
   * @return array            The augmented response array.
   *
   * @since 2.0.0
   */
  public function heartbeat( $response, $data ) {
    $sync_helper = new Admin\Helpers\Sync_Helper();

    if ( ! empty( $data['gpalab_feeder_post_id'] ) ) {
      $post_id      = $data['gpalab_feeder_post_id'];
      $status       = $sync_helper->get_sync_status( $post_id );
      $status_codes = $sync_helper->get_status_code_data( $status );

      $response['gpalab_feeder_sync_status'] = $status;
      $response['gpalab_feeder_sync_codes']  = $status_codes;
    }

    if ( ! empty( $data['gpalab_feeder_count'] ) ) {
      $response['gpalab_feeder_count'] = $sync_helper->get_sync_status_counts();
    }

    return $response;
  }

  /**
   * Triggered via AJAX, clears out old sync data and initiates a new sync process.
   * If sync_errors is present, we will only initiate a sync for posts with a sync error.
   *
   * @since 2.0.0
   */
  public function initiate_sync() {
    global $wpdb;

    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );

    // Sanitize the API data pulled off of the settings page form.
    $sync_errors = $verification->sanitize_init_sync_data( $_POST );
    // phpcs:enable

    // Load the sync status helper.
    $sync_helper = new Admin\Helpers\Sync_Helper();

    if ( $sync_errors ) { // Only resync posts in error.
      // Find all posts in an error state.
      $errors   = $sync_helper->check_sync_errors();
      $post_ids = $errors['ids'];

      // If none found, short circuit.
      if ( ! count( $post_ids ) ) {
        wp_send_json(
          array(
            'done'    => true,
            'message' => 'No posts found to be in error.',
          )
        );

        exit;
      } else {
        // If posts found in the error, clear out their sync status.
        foreach ( $post_ids as $id ) {
          delete_post_meta( $id, '_cdp_sync_status' );
        }

        unset( $id );
      }

      // Get sync total values.
      $results = $sync_helper->get_resync_totals();

      // Send back list of results.
      wp_send_json( $results );
    } else { // Resync all posts.
      // Delete the sync status of all posts in order to make them all syncable.
      // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
      // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
      // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
      $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_status' ) );
      // phpcs:enable

      // Find all syncable posts.
      $post_ids = $sync_helper->get_syncable_posts();

      // If none found, short circuit.
      if ( ! count( $post_ids ) ) {
        wp_send_json(
          array(
            'done'    => true,
            'message' => 'No syncable posts found.',
          )
        );

        exit;
      }

      // Send back list of results.
      wp_send_json(
        array(
          'done'     => 0,
          'response' => null,
          'results'  => null,
          'total'    => count( $post_ids ),
          'complete' => 0,
        )
      );
    }

    exit;
  }

  /**
   * Grabs the next post in the queue and sends it to the API.
   * Updates the postmeta indicating that this post has been synced.
   * Returns a JSON object containing the API response for the current post
   * as well as stats on the sync queue.
   *
   * @since 2.0.0
   */
  public function process_next() {
    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    $post_helper = new Admin\Helpers\Post_Helper();
    $sync_helper = new Admin\Helpers\Sync_Helper();
    $logger      = new Admin\Helpers\Log_Helper();

    // Load the sync status helper.
    $statuses   = $sync_helper->statuses;
    $sync_limit = $sync_helper->sync_limit;

    while ( get_option( $this->plugin . '_syncable_posts' ) !== false );
    update_option( $this->plugin . '_syncable_posts', 1, false );
    set_time_limit( 120 );

    // Get ids of posts that can be indexed to the CDP.
    $post_ids = $sync_helper->get_syncable_posts( $sync_limit );

    // If no syncable post end processing.
    if ( ! count( $post_ids ) ) {
      delete_option( $this->plugin . '_syncable_posts' );

      $results = $sync_helper->get_resync_totals();

      wp_send_json(
        array(
          'done'     => true,
          'total'    => $results['total'],
          'complete' => $results['complete'],
        )
      );

      exit;
    } else {
      $results = array();

      delete_option( $this->plugin . '_syncable_posts' );

      // Loop through all syncable posts updating each one.
      foreach ( $post_ids as $id ) {
        update_post_meta( $id, '_cdp_sync_status', $statuses['SYNCING'] );
        update_post_meta( $id, '_cdp_last_sync', gmdate( 'Y-m-d H:i:s' ) );

        $post = get_post( $id );
        $resp = $post_helper->add_or_update( $post, false, false, false );

        // Format the response object depending on the API request results.
        if ( ! $resp ) {
          // No response implies a failed connection.
          $results[] = $this->set_result_object( $post, 'ERROR: Connection failed.' );

          update_post_meta( $id, '_cdp_sync_status', $statuses['ERROR'] );
        } elseif ( ! is_object( $resp ) ) {
          // The response is not an object, which is not expected and may indicate an error.
          $msg = null;

          // Pull the message from the response if it exists.
          if ( is_array( $resp ) && isset( $resp['message'] ) ) {
            $msg = $resp['message'];
            unset( $resp['message'] );
          }

          $results[] = $this->set_result_object( $post, $msg, $resp );

          update_post_meta( $id, '_cdp_sync_status', $statuses['ERROR'] );
        } elseif ( 'Sync in progress.' !== $resp->message ) {
          // Response is received as expected, but indicates that the sync was unsuccessful.
          $msg = null;

          // Pull the message from the response if it exists.
          if ( isset( $resp->message ) ) {
            $msg = $resp->message;
            unset( $resp->message );
          }

          $results[] = $this->set_result_object( $post, $msg, $resp );

          update_post_meta( $id, '_cdp_sync_status', $statuses['ERROR'] );
        } else {
          // Sync successfully initiated.
          $results[] = $this->set_result_object( $post, $resp->message, null, false );
        }

        // Generate the response object.
        $totals            = $sync_helper->get_resync_totals();
        $totals['done']    = false;
        $totals['results'] = $results;

        wp_send_json( $totals );
      }

      unset( $id );
    }

    exit;
  }

  /**
   * Generates an API response when processing posts for syncing to the CDP.
   *
   * @param WP_Post $post    WordPress post object.
   * @param string  $msg     The message to send with the response.
   * @param mixed   $resp    The body from the API that is getting passed on.
   * @param bool    $error   Whether or not the response is of type error.
   * @return array
   *
   * @since 3.0.0
   */
  private function set_result_object( $post, $msg = null, $resp = null, $error = true ) {
    $result = array(
      'title'   => $post->post_title,
      'post_id' => $post->ID,
    );

    if ( $error ) {
      $result['error'] = true;
    }

    if ( null !== $msg ) {
      $result['message'] = $msg;
    }

    if ( null !== $resp ) {
      $result['response'] = $resp;
    }

    return $result;
  }

  /**
   * Forward the Ajax call to the CDP API.
   *
   * @since 1.0.0
   */
  public function test_connection() {
    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );

    // Sanitize the API data pulled off of the settings page form.
    $request = $verification->sanitize_test_connect_data( $_POST );
    // phpcs:enable

    $indexing = new Indexing();

    // Forward request to the CDP API.
    $indexing->request( $request, null, false, false );
  }

  /**
   * Forward the Ajax call to the CDP API.
   *
   * @since 3.0.0
   */
  public function debug_post() {
    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );

    // Sanitize the API data pulled off of the settings page form.
    $request = $verification->sanitize_test_connect_data( $_POST );
    // phpcs:enable

    $indexing = new Indexing();

    // Forward request to the CDP API.
    $indexing->request( $request, null, false, false );
  }

  /**
   * Validate the Ajax requests.
   *
   * @since 2.0.0
   */
  public function validate_sync() {
    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    set_time_limit( 600 );

    // Load the sync status helper.
    $sync_helper = new Admin\Helpers\Sync_Helper();
    $statuses    = $sync_helper->statuses;

    // Initialize the relevant variables.
    $stats = array(
      'up_to_date'      => 0,
      'mismatched_date' => 0,
      'missing_from_es' => 0,
      'missing_from_wp' => 0,
    );

    // Get the posts that have been indexed to the API.
    $indexed_posts = $this->get_indexed_posts();

    if ( count( $indexed_posts ) ) {
      // Get the posts that should be indexed.
      $indexable_posts = $this->get_indexable_posts();

      // Iterate through the indexable posts updating their status.
      foreach ( $indexable_posts as $indexable ) {
        $id = $indexable->ID;

        // Check if post has been indexed.
        if ( array_key_exists( $id, $indexed_posts ) ) {

          // Compare the dates on the indexed version and the current version.
          if ( mysql2date( 'c', $indexable->post_modified ) === $indexed_posts[ $id ] ) {
            // If dates match but the post not set to 'synced' add to the update synced array.
            if ( $statuses['SYNCED'] !== $indexable->sync_status ) {
              update_post_meta( $id, '_cdp_sync_status', $statuses['SYNCED'] );
            }

            // Increment the number of posts that are up to date.
            $stats['up_to_date']++;
          } else {
            // If the dates don't match, increment the count of mismatched posts.
            $stats['mismatched_date']++;

            // If the post is not already in a state of error,
            // update it's sync status to error.
            if ( $statuses['ERROR'] !== $indexable->sync_status ) {
              update_post_meta( $id, '_cdp_sync_status', $statuses['ERROR'] );
            }
          }

          // Remove from array the array that is being processed.
          unset( $indexed_posts[ $id ] );
        } else {
          // If the post has not been indexed, increment the count
          // of posts missing from Elasticsearch.
          $stats['missing_from_es']++;

          // If the post is not already in a state of error,
          // update it's sync status to not synced.
          if ( $statuses['ERROR'] !== $indexable->sync_status ) {
            update_post_meta( $id, '_cdp_sync_status', $statuses['NOT_SYNCED'] );
          }
        }
      }

      unset( $indexable );

      // Any posts remaining in in the indexed_posts array are present in the
      // Elasticsearch index but cannot be found in the WordPress database.
      $stats['missing_from_wp'] = count( $indexed_posts );
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      wp_send_json( $stats );
      exit;
    }

    return $stats;
  }

  /**
   * Retrieve a list of posts that have been indexed into the CDP
   * along with the date on which they were last modified.
   *
   * @return array The list of posts that are present in the API.
   * @since 3.0.0
   */
  private function get_indexed_posts() {
    // Load helper functions.
    $api_helper = new Admin\Helpers\API_Helper();
    $indexing   = new Indexing();

    // Initialize the relevant variables.
    $query_size    = 500;
    $result        = null;
    $indexed_posts = array();

    // Build the Elasticsearch query.
    $request = array(
      'url'    => 'search',
      'method' => 'POST',
      'body'   => array(
        'query'  => 'site:' . $api_helper->get_site(),
        'source' => array( 'post_id', 'modified' ),
        'size'   => $query_size,
        'from'   => 0,
        'scroll' => '60s',
      ),
      'print'  => false,
	);

    // Query the API and use the results to fill the indexed_posts array with a
    // list of all posts and the date they were last modified in the API.
    // Continues querying in batches of 500 until there are no more results.
    do {
      $result = $indexing->request( $request );

      if ( $result && $result->hits && count( $result->hits->hits ) ) {
        foreach ( $result->hits->hits as $hit ) {
          $indexed_posts[ $hit->_source->post_id ] = $hit->_source->modified;
        }

        unset( $hit );
      }

      // Get next set of posts.
      $request = array(
        'url'    => 'search/scroll',
        'method' => 'POST',
        'body'   => array(
          'scrollId' => $result->_scroll_id,
          'scroll'   => '60s',
        ),
        'print'  => false,
      );
    } while ( $result && $result->hits && $result->hits->hits );

    return $indexed_posts;
  }

  /**
   * Retrieve a list of posts that should be index to the CDP
   * along with their current indexed status.
   *
   * @return array The list of posts that should be indexed.
   *
   * @since 3.0.0
   */
  private function get_indexable_posts() {
    global $wpdb;

    // Get the post types that should be indexed.
    $config     = get_option( $this->plugin );
    $post_types = ! empty( $config['es_post_types'] ) ? $config['es_post_types'] : array();

    // Generate a string placeholder for each indexable post type.
    $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

    // Retrieve the list of indexable posts from cache.
    $cache_key       = 'indexable_posts';
    $indexable_posts = wp_cache_get( $cache_key, 'gpalab_feeder' );

    /**
     * Get the current sync status for all posts that
     * are published and set to be indexed into the CDP.
     *
     * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
     * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     * phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
     */
    if ( false === $indexable_posts ) {
      $indexable_posts = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT p.ID, p.post_modified, ms.meta_value as sync_status FROM $wpdb->posts p 
           LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
           LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
           WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no') AND ms.meta_value IS NOT NULL",
          array_keys( $post_types )
        )
      );
      // phpcs:enable

      // Cache the results of the query.
      wp_cache_set( $cache_key, $indexable_posts, 'gpalab_feeder' );
    }

    return $indexable_posts;
  }
}
