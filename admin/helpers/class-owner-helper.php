<?php
/**
 * Registers the Owner_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Owner_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers owner helper functions.
 *
 * @package ES_Feeder\Admin\Helpers\Owner_Helper
 * @since 3.0.0
 */
class Owner_Helper {

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
   * Retrieves the allowed owner list from the API and populates an array for use
   * in the owner dropdown.
   *
   * @return array
   *
   * @since 2.5.0
   */
  public function get_owners() {
    $post_actions = new \ES_Feeder\Post_Actions();
    $logger       = new Log_Helper();

    $owners = array();

    // If in the process of an AJAX request return the stored owner values.
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      $stored = get_option( 'cdp_owners' );

      if ( $stored ) {
        $owners = $stored;
      }

      return $owners;
    }

    $args = array(
      'method' => 'GET',
      'url'    => 'owner',
    );

    // Request the list of owners from the API.
    $data = $post_actions->request( $args );

    // Look of errors in the form of an object.
    if ( $data && is_object( $data ) ) {
      if ( $data->error ) {
        $logger->log( $data->error );
      }
    }

    // If the response is an array, as expected...
    if ( $data && is_array( $data ) ) {
      // Make sure there are no errors...
      if ( $data['error'] ) {
        $logger->log( $data['error'] );
      } else {
        // An iterate through the array of owners.
        foreach ( $data as $owner ) {
          $owners[ $owner->name ] = $owner->name;
        }
      }
    }

    update_option( 'cdp_owners', $owners );

    return $owners;
  }
}
