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
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin     The plugin name.
   * @param string $version    The plugin version number.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin, $version ) {
    $this->namespace = $namespace;
    $this->plugin    = $plugin;
    $this->version   = $version;
  }

  /**
   * Triggered by heartbeat AJAX event, added the sync status indicator HTML if the data includes
   * es_sync_status which contains a post ID and will be converted to the sync status indicator HTML.
   *
   * If the data includes es_sync_status_counts, send back an array of counts for each status ID.
   *
   * @param array $response   The response returned to the server from the heartbeat.
   * @param array $data       The $_POST data sent.
   * @return array            The augmented response array.
   *
   * @since 2.0.0
   */
  public function heartbeat( $response, $data ) {
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    if ( ! empty( $data['es_sync_status'] ) ) {
      $post_id = $data['es_sync_status'];
      $status  = $sync_helper->get_sync_status( $post_id );
      ob_start();
      $sync_helper->sync_status_indicator( $status );
      $response['es_sync_status'] = ob_get_clean();
    }

    if ( ! empty( $data['es_sync_status_counts'] ) ) {
      $response['es_sync_status_counts'] = $sync_helper->get_sync_status_counts();
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

    // The following rules are handled by the slo_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );

    $sync_errors = isset( $_POST['sync_errors'] ) && $_POST['sync_errors'];
    // phpcs:enable

    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    if ( $sync_errors ) {
      $errors   = $sync_helper->check_sync_errors();
      $post_ids = $errors['ids'];

      if ( count( $post_ids ) ) {
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status' AND post_id IN (" . implode( ',', $post_ids ) . ')' );
      } else {
        echo wp_json_encode(
          array(
            'error'   => true,
            'message' => 'No posts found.',
          )
        );
        exit;
      }
      $results = $sync_helper->get_resync_totals();

      wp_send_json( $results );
    } else {
      $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_status' ) );
      $post_ids = $sync_helper->get_syncable_posts();

      if ( ! count( $post_ids ) ) {
        echo wp_json_encode(
          array(
            'error'   => true,
            'message' => 'No posts found.',
          )
        );

        exit;
      }

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
    // The following rules are handled by the slo_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    global $wpdb;

    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    $statuses   = $sync_helper->statuses;
    $sync_limit = $sync_helper->sync_limit;

    while ( get_option( $this->plugin . '_syncable_posts' ) !== false );
    update_option( $this->plugin . '_syncable_posts', 1, false );
    set_time_limit( 120 );
    $post_ids = $sync_helper->get_syncable_posts( $sync_limit );

    if ( ! count( $post_ids ) ) {
      delete_option( $this->plugin . '_syncable_posts' );

      $results = $sync_helper->get_resync_totals();

      wp_send_json(
        array(
          'done'     => 1,
          'total'    => $results['total'],
          'complete' => $results['complete'],
        )
      );

      exit;
    } else {
      $results = array();
      $vals    = array();
      $query   = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES";

      foreach ( $post_ids as $post_id ) {
        $vals[] = "($post_id, '_cdp_sync_status', '1')";
      }

      $query .= implode( ',', $vals );
      $wpdb->query( $query );
      delete_option( $this->plugin . '_syncable_posts' );

      foreach ( $post_ids as $post_id ) {
        update_post_meta( $post_id, '_cdp_last_sync', gmdate( 'Y-m-d H:i:s' ) );
        $post = get_post( $post_id );
        $resp = $post_helper->add_or_update( $post, false, true, false );

        $wpdb->update(
          $wpdb->posts,
          array( 'post_status' => 'publish' ),
          array( 'ID' => $post_id )
        );

        if ( ! $resp ) {
          $results[] = array(
            'title'   => $post->post_title,
            'post_id' => $post->ID,
            'message' => 'ERROR: Connection failed.',
            'error'   => true,
          );

          update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
        } elseif ( ! is_object( $resp ) || 'Sync in progress.' !== $resp->message ) {
          $results[] = array(
            'title'    => $post->post_title,
            'post_id'  => $post->ID,
            'response' => $resp,
            'message'  => 'See error response.',
            'error'    => true,
          );

          update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
        }
      }

      $totals            = $sync_helper->get_resync_totals();
      $totals['done']    = 0;
      $totals['results'] = $results;
      wp_send_json( $totals );
    }
    exit;
  }

  /**
   * Wrapper for Ajax call to send an indexing request.
   *
   * @param array $request — Options to be used when sending the AJAX request.
   *
   * @since 1.0.0
   */
  public function es_request( $request ) {
    // The following rules are handled by the slo_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    $post_actions = new Post_Actions( $this->namespace, $this->plugin );

    $post_actions->request( $request );
  }

  /**
   * Validate the Ajax requests.
   *
   * @since 2.0.0
   */
  public function validate_sync() {
    // The following rules are handled by the slo_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    set_time_limit( 600 );
    global $wpdb;

    $api_helper   = new Admin\Helpers\API_Helper( $this->namespace, $this->plugin );
    $post_actions = new Post_Actions( $this->namespace, $this->plugin );
    $sync_helper  = new Admin\Helpers\Sync_Helper( $this->plugin );

    $statuses = $sync_helper->statuses;

    $size      = 500;
    $result    = null;
    $modifieds = array();
    $stats     = array(
      'updated'    => 0,
      'es_missing' => 0,
      'wp_missing' => 0,
      'mismatched' => 0,
    );

    $request = array(
      'url'    => 'search',
      'method' => 'POST',
      'body'   => array(
        'query'   => 'site:' . $api_helper->get_site(),
        'include' => array( 'post_id', 'modified' ),
        'size'    => $size,
        'from'    => 0,
        'scroll'  => '60s',
      ),
      'print'  => false,
    );

    do {
      $result = $post_actions->request( $request );
      if ( $result && $result->hits && count( $result->hits->hits ) ) {
        foreach ( $result->hits->hits as $hit ) {
          $modifieds[ $hit->_source->post_id ] = $hit->_source->modified;
        }
      }
      $request = array(
        'url'    => 'search/scroll',
        'method' => 'POST',
        'body'   => array(
          'scrollId' => $result->_scroll_id,
          'scroll'   => '60s',
        ),
        'print'  => false,
      );
    } while ( $result && $result->hits && count( $result->hits->hits ) );

    if ( count( $modifieds ) ) {
      $opts       = get_option( $this->plugin );
      $post_types = $opts['es_post_types'];
      $formats    = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
      $query      = "SELECT p.ID, p.post_modified, ms.meta_value as sync_status 
                     FROM $wpdb->posts p 
                     LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id 
                     LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id 
                     WHERE p.post_type IN ($formats) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no') AND ms.meta_value IS NOT NULL";

      $rows = $wpdb->get_results(
        $wpdb->prepare( $query, array_keys( $post_types ) )
       );

      $update_errors = array();
      $update_synced = array();

      foreach ( $rows as $row ) {
        if ( array_key_exists( $row->ID, $modifieds ) ) {

          if ( mysql2date( 'c', $row->post_modified ) === $modifieds[ $row->ID ] ) {
            if ( $statuses['SYNCED'] != $row->sync_status ) {
              $update_synced[] = $row->ID;
              $stats['updated']++;
            }
          } else {
            $stats['mismatched']++;

            if ( $statuses['ERROR'] != $row->sync_status ) {
              $update_errors[] = $row->ID;
              $stats['updated']++;
            }
          }

          unset( $modifieds[ $row->ID ] );
        } else {
          $stats['es_missing']++;

          if ( $statuses['ERROR'] != $row->sync_status ) {
            $update_errors[] = $row->ID;
            $stats['updated']++;
          }
        }
      }

      if ( count( $update_synced ) ) {
        $wpdb->query(
          $wpdb->prepare(
            "UPDATE $wpdb->postmeta SET meta_value = %d WHERE meta_key = '_cdp_sync_status' AND post_id IN (%s)",
            $statuses['SYNCED'],
            implode( ',', $update_synced )
          )
        );
      }

      if ( count( $update_errors ) ) {
        $wpdb->query(
          $wpdb->prepare(
            "UPDATE $wpdb->postmeta SET meta_value = %d WHERE meta_key = '_cdp_sync_status' AND post_id IN (%s)",
            $statuses['ERROR'],
            implode( ',', $update_errors )
          )
        );
      }

      $stats['wp_missing'] = count( $modifieds );
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      wp_send_json( $stats );
      exit;
    }

    return $stats;
  }
}
