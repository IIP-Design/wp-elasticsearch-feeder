<?php
/**
 * Registers the Taxonomy_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Taxonomy_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers helper functions to fetch and manipulate custom taxonomies.
 *
 * @package ES_Feeder\Admin\Helpers\Taxonomy_Helper
 * @since 3.0.0
 */
class Taxonomy_Helper {

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
   * Fetch all the available taxonomy terms.
   *
   * @since 2.0.0
   */
  public function get_taxonomy() {
    $post_actions = new \ES_Feeder\Post_Actions();
    $log_helper   = new Log_Helper();

    $taxonomy = array();

    $args = array(
      'method' => 'GET',
      'url'    => 'taxonomy?tree',
    );

    // Request the taxonomy from the API.
    $data = $post_actions->request( $args );

    if ( $data ) {
      // Look of errors in the form of an object.
      if ( is_object( $data ) && $data->error ) {
        $log_helper->log( $data->error );
      }

      // If the response is an array, as expected...
      if ( is_array( $data ) ) {
        // Make sure there are no errors...
        if ( array_key_exists( 'error', $data ) && $data['error'] ) {
          $log_helper->log( $data['error'] );
        } else {
          $taxonomy = $data;
        }
      }
    }

    return $taxonomy;
  }
}
