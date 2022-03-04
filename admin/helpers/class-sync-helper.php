<?php
/**
 * Registers the Sync_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Sync_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

use DateTime;

/**
 * Registers Sync helper functions.
 *
 * @package ES_Feeder\Admin\Helpers\Sync_Helper
 * @since 3.0.0
 */
class Sync_Helper {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @since 3.0.0
   */
  public function __construct() {
    $this->plugin       = ES_FEEDER_NAME;
    $this->statuses     = array(
      'NOT_SYNCED'         => 0,
      'SYNCING'            => 1,
      'SYNC_WHILE_SYNCING' => 2,
      'SYNCED'             => 3,
      'RESYNC'             => 4,
      'ERROR'              => 5,
    );
    $this->sync_limit   = 25;
    $this->sync_timeout = 10;
  }

  /**
   * Check to see how long a post has been syncing and update to
   * error status if it's been longer than SYNC_TIMEOUT.
   * Post modified and sync status can be supplied to save a database query or two.
   * Then return the status.
   *
   * @param int      $post_id  The unique identifier for a given WordPress post.
   * @param int|null $status   The numeric code representing the sync status of the given post.
   * @return int     The updated numeric code representing the sync status of the given post.
   *
   * @since 2.0.0
   */
  public function get_sync_status( $post_id, $status = null ) {
    if ( ! $status ) {
      $status = get_post_meta( $post_id, '_cdp_sync_status', true );
    }

    $error_statuses = array( $this->statuses['ERROR'], $this->statuses['RESYNC'] );

    if ( ! in_array( $status, $error_statuses, true ) && ! $this->sync_allowed( $status ) ) {

      // Check to see if we should resolve to error based on time since last sync.
      $last_sync = get_post_meta( $post_id, '_cdp_last_sync', true );

      if ( $last_sync ) {
        $last_sync = new DateTime( $last_sync );
      } else {
        $last_sync = new DateTime( 'now' );
      }

      $interval = date_diff( $last_sync, new DateTime( 'now' ) );
      $diff     = $interval->format( '%i' );

      if ( $diff >= $this->sync_timeout ) {
        $status = $this->statuses['RESYNC'];
        update_post_meta( $post_id, '_cdp_sync_status', $status );
      }
    }

    return $status;
  }

  /**
   * Gets counts for each sync status.
   *
   * @return array The count posts matching each sync status.
   *
   * @since 2.0.0
   */
  public function get_sync_status_counts() {
    global $wpdb;

    // Get the list of indexable post types.
    $config     = get_option( $this->plugin );
    $post_types = ! empty( $config['es_post_types'] ) ? $config['es_post_types'] : array();

    // Generate a string placeholder for each indexable post type.
    $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

    // Retrieve the list of indexable posts from cache.
    $cache_key    = 'sync_totals';
    $_sync_totals = wp_cache_get( $cache_key, 'gpalab_feeder' );

    if ( false === $_sync_totals ) {
      // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
      $_sync_totals = $wpdb->get_results(
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $wpdb->prepare(
          "SELECT IFNULL(ms.meta_value, 0) as status, COUNT(IFNULL(ms.meta_value, 0)) as total FROM $wpdb->posts p
          LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
          LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
          WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no') GROUP BY IFNULL(ms.meta_value, 0)",
          array_keys( $post_types )
        )
      );
      // phpcs:enable

      // Cache the results of the query.
      wp_cache_set( $cache_key, $_sync_totals, 'gpalab_feeder' );
    }

    $sync_count = array();

    foreach ( $_sync_totals as $total ) {
      $sync_count [ $total->status ] = $total->total;
    }

    unset( $total );

    return $sync_count;
  }

  /**
   * Get a list of indexable posts that are not in the process of being synced.
   *
   * @param int|null $limit   The maximum number of posts to return.
   * @return array            The list of
   *
   * @since 2.1.0
   */
  public function get_syncable_posts( $limit = null ) {
    global $wpdb;

    $config     = get_option( $this->plugin );
    $post_types = ! empty( $config['es_post_types'] ) ? $config['es_post_types'] : array();

    // Add a string placeholder to for each indexable post type.
    $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

    $query = "SELECT p.ID FROM $wpdb->posts p 
              LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
              LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
              WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no')
              AND ms.meta_value IS NULL ORDER BY p.post_date DESC";

    if ( $limit ) {
      $query .= " LIMIT $limit";
    }

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
    $post_ids = $wpdb->get_col(
      $wpdb->prepare( $query, array_keys( $post_types ) )
    );
    // phpcs:enable

    return ! empty( $post_ids ) ? $post_ids : array();
  }

  /**
   * Determines if a post can be synced or not. Syncable means that it is not in the process
   * of being synced. If it is not syncable, update the sync status to inform the user that
   * they needs to wait until the sync is complete and then resync.
   *
   * @param int $post_id    A WordPress post ID value.
   * @return bool           Whether or not the given post is syncable.
   *
   * @since 2.0.0
   */
  public function is_syncable( $post_id ) {
    global $wpdb;

    $log_helper    = new Log_Helper();
    $syncing       = $this->statuses['SYNCING'];
    $while_syncing = $this->statuses['SYNC_WHILE_SYNCING'];

    // Check sync status by attempting to update and if rows updated then sync is in progress.
    $rows = $wpdb->query(
      $wpdb->prepare(
        "UPDATE $wpdb->postmeta SET meta_value = %d WHERE post_id = %d AND meta_key = '_cdp_sync_status' AND meta_value IN (%d,%d)",
        $while_syncing,
        $post_id,
        $syncing,
        $while_syncing
      )
    );

    if ( $rows ) {
      // Get the sync id.
      $sync_uid = get_post_meta( $post_id, '_cdp_sync_uid', true );

      $log_helper->log(
        'Post not syncable so status updated to SYNC_WHILE_SYNCING: $post_id, sync_uid:' . ! empty( $sync_uid ) ? $sync_uid : 'none'
      );

      return false;
    }

    return true;
  }

  /**
   * Get the data on what posts have been been successfully re-synced.
   *
   * @return array   The current resync data.
   *
   * @since 2.1.0
   */
  public function get_resync_totals() {
    global $wpdb;

    $opts  = get_option( $this->plugin );
    $types = ! empty( $opts['es_post_types'] ) ? $opts['es_post_types'] : array();

    // Add a string placeholder to for each indexable post type.
    $placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
    $ph_values    = array_keys( $types );

    $query = "SELECT COUNT(*) as total, SUM(IF(ms.meta_value IS NOT NULL, 1, 0)) as complete FROM $wpdb->posts p 
              LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id 
              LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id 
              WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no')";

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
    $row = $wpdb->get_row(
      $wpdb->prepare( $query, $ph_values )
    );
    // phpcs:enable

    return array(
      'done'     => $row->total === $row->complete ? 1 : 0,
      'response' => null,
      'results'  => null,
      'total'    => $row->total,
      'complete' => $row->complete,
    );
  }

  /**
   * Prints the appropriately colored sync status indicator dot given a status.
   *
   * @param int     $status_code       The numeric representation of a given status code.
   * @param boolean $text boolean      Whether or not to include indicator label text.
   * @param boolean $merge_publishes   Whether of not to bundle republishing attempts in publishing status.
   *
   * @since 2.0.0
   */
  public function sync_status_indicator( $status_code, $text = true, $merge_publishes = true ) {
    $status_data = $this->get_status_code_data( $status_code, $merge_publishes );

    ?>
    <div
      class="sync-status sync-status-<?php echo esc_attr( $status_data['color'] ); ?>"
      title="<?php echo esc_attr( $status_data['title'] ); ?>"
    ></div>
    <?php

    if ( true === $text ) {
      echo '<div class="sync-status-label">' . esc_html( $status_data['title'] ) . '</div>';
    }
  }

  /**
   * Iterate over posts in a syncing or erroneous state. If syncing for longer than
   * the SYNC_TIMEOUT time, escalate to error status.
   * Return stats on total errors (if any).
   *
   * @since 2.0.0
   */
  public function check_sync_errors() {
    global $wpdb;

    $result = array(
      'errors' => 0,
      'ids'    => array(),
    );

    $statuses = array(
      $this->statuses['ERROR'],
      $this->statuses['SYNCING'],
      $this->statuses['SYNC_WHILE_SYNCING'],
    );

    $ph_values = array_values( $statuses );

    $rows = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT p.ID, p.post_type, m.meta_value as sync_status FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE m.meta_key = '_cdp_sync_status' AND m.meta_value IN (%s,%s,%s)",
        $ph_values
      )
    );

    foreach ( $rows as $row ) {
      $status = $this->get_sync_status( $row->ID, $row->sync_status );

      if ( $this->statuses['ERROR'] === (int) $status ) {

        $result['errors']++;

        if ( ! array_key_exists( $row->post_type, $result ) ) {
          $result[ $row->post_type ] = 0;
        }

        $result[ $row->post_type ]++;
        $result['ids'][] = $row->ID;
      }
    }

    unset( $row );

    return $result;
  }

  /**
   * Transforms a numeric status code into English string version of status.
   *
   * @param int     $status_code      The numeric representation of a given status code.
   * @param boolean $merge_publishes  Whether of not to bundle republishing attempts in publishing status.
   * @return array  The provided status code's associated color, title, and status text.
   *
   * @since 3.0.0
   */
  public function get_status_code_data( $status_code, $merge_publishes = false ) {
    $color = '';
    $title = '';

    switch ( $status_code ) {
      case $this->statuses['SYNCING']:
        $color = 'yellow';
        $title = 'Publishing';
          break;
      case $this->statuses['SYNC_WHILE_SYNCING']:
        $color = 'yellow';
        $title = $merge_publishes ? 'Publishing' : 'Republish Attempted';
          break;
      case $this->statuses['SYNCED']:
        $color = 'green';
        $title = 'Published';
          break;
      case $this->statuses['RESYNC']:
        $color = 'orange';
        $title = 'Status Undetermined';
          break;
      case $this->statuses['ERROR']:
        $color = 'red';
        $title = 'Error';
          break;
      case $this->statuses['NOT_SYNCED']:
        $color = 'black';
        $title = 'Not Synced';
          break;
      default:
        $color = 'black';
        $title = 'Not Published';
    }

    return array(
      'color' => $color,
      'title' => $title,
    );
  }

  /**
   * Checks whether a post is eligible for syncing.
   * Syncing is allowed only if the current status is not one of the syncing statues.
   *
   * @param int $status  This current post status.
   * @return bool        Whether or not the current status is a syncing status.
   *
   * @since 2.0.0
   */
  private function sync_allowed( $status ) {
    switch ( $status ) {
      case $this->statuses['SYNC_WHILE_SYNCING']:
      case $this->statuses['SYNCING']:
          return false;
      default:
          return true;
    }
  }
}
