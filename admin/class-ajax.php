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
   * @param string $plugin     The plugin name.
   * @param string $version    The plugin version number.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin, $version ) {
    $this->plugin  = $plugin;
    $this->version = $version;
  }

  /**
   * Triggered by heartbeat AJAX event, added the sync status indicator HTML
   * if the data includes es_sync_status which contains a post ID and will be
   * converted to the sync status indicator HTML.
   * If the data includes es_sync_status_counts, send back an array of counts
   * for each status ID.
   *
   * @param $response
   * @param $data
   * @return mixed
   */
  public function heartbeat( $response, $data ) {
    $sync_helper = new \ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin );

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
}
