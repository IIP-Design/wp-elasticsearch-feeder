<?php
/**
 * Registers the API class.
 *
 * @package ES_Feeder\API
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Generates a REST API endpoint for each public post type.
 *
 * @package ES_Feeder\API
 * @since 3.0.0
 */
class API {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin      The plugin name.
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
   * Creates API endpoints for all public post types.
   *
   * @since 1.0.0
   */
  public function register_elasticsearch_rest_routes() {
    // Get list of public post types.
    $post_types = get_post_types(
      array( 'public' => true )
    );

    if ( is_array( $post_types ) && count( $post_types ) > 0 ) {
      // Register a new route for each public post type.
      foreach ( $post_types as $type ) {
        $this->register_post_types( $type );
      }
    }

    $controller = new \WP_ES_FEEDER_Callback_Controller();
    $controller->register_routes();
  }

  /**
   * Create an API endpoint for the provided post type.
   *
   * If you have a custom post-type, you must follow the class
   * convention "WP_ES_FEEDER_EXT_{TYPE}_Controller" if you want
   * to customize the output. If no class convention is found,
   * plugin will create default API routes for custom post types.
   *
   * @param string $type   Name of a public post type.
   *
   * @since 1.0.0
   */
  private function register_post_types( $type ) {
    // Standard indexable post types.
    $base_types = array(
      'post'       => true,
      'page'       => true,
      'attachment' => true,
    );

    $is_base_type = array_key_exists( $type, $base_types );

    if ( (int) $is_base_type ) {
      $controller = new Admin\API\REST_Controller( $this->namespace, $this->plugin, $type );
      $controller->register_routes();

      return;
    } elseif ( ! $is_base_type && ! class_exists( 'WP_ES_FEEDER_EXT_' . strtoupper( $type ) . '_Controller' ) ) {
      $controller = new Admin\API\REST_Controller( $this->namespace, $this->plugin, $type );
      $controller->register_routes();

      return;
    }
  }
}
