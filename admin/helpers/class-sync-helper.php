<?php
/**
 * Registers the Sync_Helper class.
 *
 * @package ES_Feeder\Sync_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers Sync helper functions.
 *
 * @package ES_Feeder\Sync_Helper
 * @since 3.0.0
 */
class Sync_Helper {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $plugin  The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin ) {
    $this->plugin = $plugin;
  }

  /**
   * Gets counts for each sync status
   *
   * @since 3.0.0
   */
  public function get_sync_status_counts() {
    global $wpdb;

    $opts       = get_option( $this->plugin );
    $post_types = ! empty( $opts['es_post_types'] ) ? $opts['es_post_types'] : array();
    $formats    = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

    $query = "SELECT IFNULL(ms.meta_value, 0) as status, COUNT(IFNULL(ms.meta_value, 0)) as total 
              FROM $wpdb->posts p 
              LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
              LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
              WHERE p.post_type IN ($formats) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no') GROUP BY IFNULL(ms.meta_value, 0)";

    $query  = $wpdb->prepare( $query, array_keys( $post_types ) );
    $totals = $wpdb->get_results( $query );
    $ret    = array();

    foreach ( $totals as $total ) {
      $ret[ $total->status ] = $total->total;
    }

    return $ret;
  }

  /**
   * Prints the appropriately colored sync status indicator dot given a status.
   *
   * @param int     $status_code       The numeric representation of a given status code.
   * @param boolean $text boolean      Whether or not to include indicator label text.
   * @param boolean $merge_publishes   Whether of not to bundle republishing attempts in publishing status.
   *
   * @since 3.0.0
   */
  public function sync_status_indicator( $status_code, $text = true, $merge_publishes = true ) {
    $status_data = $this->get_status_code_data( $status_code, $merge_publishes );

    ?>
      <div
        class="sync-status sync-status-<?php echo esc_attr( $status_data['color'] ); ?>"
        title="<?php echo esc_attr( $status_data['title'] ); ?>"
      ></div>

      <div class="sync-status-label">
        <?php echo $text ? esc_html( $status_data['title'] ) : ''; ?>
      </div>
      <?php
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
  private function get_status_code_data( $status_code, $merge_publishes = false ) {
    $color  = 'black';
    $status = 'NOT_SYNCED';
    $title  = 'Not Published';

    switch ( $status_code ) {
      case 1:
        $color  = 'yellow';
        $status = 'SYNCING';
        $title  = 'Publishing';
          break;
      case 2:
        $color  = 'yellow';
        $status = 'SYNC_WHILE_SYNCING';
        $title  = $merge_publishes ? 'Publishing' : 'Republish Attempted';
          break;
      case 3:
        $color  = 'green';
        $status = 'SYNCED';
        $title  = 'Published';
          break;
      case 4:
        $color  = 'orange';
        $status = 'RESYNC';
        $title  = 'Validation Required';
          break;
      case 5:
        $color  = 'red';
        $status = 'ERROR';
        $title  = 'Error';
          break;
    }

    return array(
      'color'  => $color,
      'status' => $status,
      'title'  => $title,
    );
  }

  /**
   * Returns true if the status is not representative of a syncing state.
   *
   * @param $status
   * @return bool
   */
  public static function sync_allowed( $status ) {
    switch ( $status ) {
      case self::SYNC_WHILE_SYNCING:
      case self::SYNCING:
          return false;
      default:
          return true;
    }
  }
}
