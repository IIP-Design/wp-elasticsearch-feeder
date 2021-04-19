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
   * Initializes the class with the plugin name and version.
   *
   * @param string $plugin   The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin ) {
    $this->plugin = $plugin;
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
    $post_actions = new \ES_Feeder\Post_Actions( $this->plugin );

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      $owners = get_option( 'cdp_owners' );

      if ( $owners ) {
        return $owners;
      }
    }

    $owners = array();
    $args   = array(
      'method' => 'GET',
      'url'    => 'owner',
    );
    $data   = $post_actions->request( $args );

    if ( $data && count( $data ) && ! is_string( $data )
      && ( ! is_array( $data ) || ! array_key_exists( 'error', $data ) || ! $data['error'] )
      && ( ! is_object( $data ) || ! $data->error ) ) {
      foreach ( $data as $owner ) {
        $owners[ $owner->name ] = $owner->name;
      }
    }

    update_option( 'cdp_owners', $owners );

    return $owners;
  }
}
