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
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin   The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin ) {
    $this->namespace = $namespace;
    $this->plugin    = $plugin;
  }

  /**
   * Fetch all the available taxonomy terms.
   *
   * @since 2.0.0
   */
  public function get_taxonomy() {
    $post_actions = new \ES_Feeder\Post_Actions( $this->namespace, $this->plugin );

    $args = array(
      'method' => 'GET',
      'url'    => 'taxonomy?tree',
    );

    $data = $post_actions->request( $args );

    if ( $data ) {
      if ( is_object( $data ) && $data->error ) {
        return array();
      }

      if ( is_array( $data ) && array_key_exists( 'error', $data ) && $data['error'] ) {
        return array();
      } elseif ( is_array( $data ) ) {
        return $data;
      }
    }

    return array();
  }
}
