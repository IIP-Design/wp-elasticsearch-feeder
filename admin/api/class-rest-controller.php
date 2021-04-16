<?php
/**
 * Registers an API endpoint for a given public post type.
 *
 * @package ES_Feeder\Admin\API\REST_Controller
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#the-controller-pattern
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\API;

use WP_REST_Controller;

/**
 * Registers an API endpoint for a given public post type.
 *
 * @package ES_Feeder\Admin\API\REST_Controller
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#the-controller-pattern
 * @since 3.0.0
 */
class REST_Controller extends WP_REST_Controller {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin      The plugin name.
   * @param string $post_type   The given post type to add to the API.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin, $post_type ) {
    $api_helper = new \ES_Feeder\Admin\Helpers\API_Helper( $plugin );

    $this->namespace = $namespace;
    $this->plugin    = $plugin;
    $this->resource  = $api_helper->get_post_type_label( $post_type, 'name' );
    $this->type      = $post_type;
  }

  /**
   * Registers a REST API routes accessed by Elasticsearch to index the site's content.
   *
   * @since 1.0.0
   */
  public function register_routes() {
    // Add endpoint for all instances of the given post type.
    register_rest_route(
      $this->namespace,
      '/' . rawurlencode( $this->resource ),
      array(
        array(
          'methods'             => \WP_REST_Server::READABLE,
          'callback'            => array(
            $this,
            'get_items',
          ),
          'args'                => array(
            'per_page' => array(
              'validate_callback' => function ( $param, $request, $key ) {
                return is_numeric( $param );
              },
            ),
            'page'     => array(
              'validate_callback' => function ( $param, $request, $key ) {
                return is_numeric( $param );
              },
            ),
          ),
          'permission_callback' => array(
            $this,
            'get_items_permissions_check',
          ),
        ),
      )
    );

    // Add endpoint for a single instance of the given post type by post ID.
    register_rest_route(
      $this->namespace,
      '/' . rawurlencode( $this->resource ) . '/(?P<id>[\d]+)',
      array(
        array(
          'methods'             => \WP_REST_Server::READABLE,
          'callback'            => array(
            $this,
            'get_item',
          ),
          'args'                => array(
            'id' => array(
              'validate_callback' => function ( $param, $request, $key ) {
                return is_numeric( $param );
              },
            ),
          ),
          'permission_callback' => array(
            $this,
            'get_item_permissions_check',
          ),
        ),
      )
    );
  }

  /**
   * Fetch a all instances of a given post type.
   *
   * @param WP_REST_Request $request    Full data about the request.
   * @return WP_Error|WP_REST_Response  The requested data or an error if not found.
   *
   * @since 1.0.0
   */
  public function get_items( $request ) {
    $args['post_type'] = $this->type;
    $page              = (int) $request->get_param( 'page' );
    $per_page          = (int) $request->get_param( 'per_page' );

    if ( $per_page ) {
      $args['posts_per_page'] = $per_page;
    } else {
      $args['posts_per_page'] = 25;
    }

    if ( is_numeric( $page ) ) {
      if ( 1 === $page ) {
        $args['offset'] = 0;
      } elseif ( $page > 1 ) {
        $args['offset'] = ( $page * $args['posts_per_page'] ) - $args['posts_per_page'];
      }
    }

    $args['meta_query'] = array(
      'relation' => 'OR',
      array(
        'key'     => '_iip_index_post_to_cdp_option',
        'compare' => 'NOT EXISTS',
      ),
      array(
        'key'     => '_iip_index_post_to_cdp_option',
        'value'   => 'no',
        'compare' => '!=',
      ),
    );

    $posts = get_posts( $args );

    if ( empty( $posts ) ) {
      return rest_ensure_response( array() );
    }

    foreach ( $posts as $post ) {
      $response = $this->prepare_item_for_response( $post, $request );
      $data[]   = $this->prepare_response_for_collection( $response );
    }

    return rest_ensure_response( $data );
  }

  /**
   * Fetch a single instance of a post type by it's post id.
   *
   * @param WP_REST_Request $request    Full data about the request.
   * @return WP_Error|WP_REST_Response  The requested data or an error if not found.
   *
   * @since 1.0.0
   */
  public function get_item( $request ) {
    $api_helper = new \ES_Feeder\Admin\Helpers\API_Helper( $this->plugin );

    $id       = (int) $request['id'];
    $response = array();

    $post = get_post( $id );

    if ( empty( $post ) ) {
      return rest_ensure_response( array() );
    }

    if ( $this->shouldIndex( $post ) ) {
      $response = $this->prepare_item_for_response( $post, $request );
      $data     = $response->get_data();

      $site_taxonomies = $api_helper->get_site_taxonomies( $post->ID );

      if ( count( $site_taxonomies ) ) {
        $data['site_taxonomies'] = $site_taxonomies;
      }

      $categories = get_post_meta( $id, '_iip_taxonomy_terms', true ) ?: array();
      $cat_ids    = array();
      foreach ( $categories as $cat ) {
        $args = explode( '<', $cat );
        if ( ! in_array( $args[0], $cat_ids ) ) {
          $cat_ids[] = $args[0];
        }
      }
      $data['categories'] = $cat_ids;
      $response->set_data( $data );
    }

    return $response;
  }

  /**
   * Normalize the data provided by the API request for multiple items.
   *
   * @param WP_Error|WP_REST_Response $response   The data returned by the API request.
   * @return array                                The normalized data.
   *
   * @since 1.0.0
   */
  public function prepare_response_for_collection( $response ) {
    if ( ! ( $response instanceof \WP_REST_Response ) ) {
      return $response;
    }

    $data   = (array) $response->get_data();
    $server = rest_get_server();

    if ( method_exists( $server, 'get_compact_response_links' ) ) {
      $links = call_user_func(
           array(
        $server,
        'get_compact_response_links',
           ),
          $response
          );
    } else {
      $links = call_user_func(
           array(
        $server,
        'get_response_links',
           ),
          $response
          );
    }

    if ( ! empty( $links ) ) {
      $data['_links'] = $links;
    }

    return $data;
  }

  /**
   * Normalize the data provided by the API request for a single item.
   *
   * @param WP_Post                   $post       The WordPress post data for a given post.
   * @param WP_Error|WP_REST_Response $request   The data returned by the API request.
   * @return array                                The normalized data.
   *
   * @since 1.0.0
   */
  public function prepare_item_for_response( $post, $request ) {
    return rest_ensure_response( $this->baseline( $post, $request ) );
  }

  /**
   * @since 1.0.0
   */
  public function baseline( $post, $request ) {
    $api_helper = new \ES_Feeder\Admin\Helpers\API_Helper( $this->plugin );
    $post_data  = array();

    // If the post is an attachment return right away.
    if ( 'attachment' === $post->post_type ) {
      $post_data         = wp_prepare_attachment_for_js( $post->ID );
      $post_data['site'] = $this->get_site();
      return rest_ensure_response( $post_data );
    }

    // We are also renaming the fields to more understandable names.
    if ( isset( $post->ID ) ) {
      $post_data['post_id'] = (int) $post->ID;
    }

    $post_data['type'] = $this->type;

    $post_data['site'] = $this->get_site();

    $post_data['owner'] = $api_helper->get_owner( $post->ID );

    if ( isset( $post->post_date ) ) {
      $post_data['published'] = get_the_date( 'c', $post->ID );
    }

    if ( isset( $post->post_modified ) ) {
      $post_data['modified'] = get_the_modified_date( 'c', $post->ID );
    }

    if ( isset( $post->post_author ) ) {
      $post_data['author'] = $api_helper->get_author( $post->post_author );
    }

    // Pre-approved.
    $opt               = get_option( $this->plugin );
    $opt_url           = $opt['es_wpdomain'];
    $post_data['link'] = str_replace( site_url(), $opt_url, get_permalink( $post->ID ) );

    if ( isset( $post->post_title ) ) {
      $post_data['title'] = $post->post_title;
    }

    if ( isset( $post->post_name ) ) {
      $post_data['slug'] = $post->post_name;
    }

    if ( isset( $post->post_content ) ) {
      $post_data['content'] = $api_helper->render_vc_shortcodes( $post );
    }

    if ( isset( $post->post_excerpt ) ) {
      $post_data['excerpt'] = $post->post_excerpt;
    }

    $post_data['language']  = $api_helper->get_language( $post->ID );
    $post_data['languages'] = $api_helper->get_related_translated_posts( $post->ID, $post->post_type ) ?: array();

    if ( ! array_key_exists( 'tags', $post_data ) ) {
      $post_data['tags'] = array();
    }
    if ( ! array_key_exists( 'categories', $post_data ) ) {
      $post_data['categories'] = array();
    }

    $post_data['thumbnail'] = $api_helper->get_image_metadata( get_post_thumbnail_id( $post->ID ) );

    if ( isset( $post->comment_count ) ) {
      $post_data['comment_count'] = (int) $post->comment_count;
    }

    return $post_data;
  }

  /**
   * @since 1.0.0
   */
  public function get_items_permissions_check( $request ) {
    return true;
  }

  /**
   * @since 1.0.0
   */
  public function get_item_permissions_check( $request ) {
    return true;
  }

  /**
   * @since 1.0.0
   */
  public function authorization_status_code() {
    $status = 401;

    if ( is_user_logged_in() ) {
      $status = 403;
    }

    return $status;
  }

  /**
   * @since 2.0.0
   */
  public function get_site() {
    $opt  = get_option( $this->plugin );
    $url  = $opt['es_wpdomain'];
    $args = wp_parse_url( $url );
    $host = $url;

    if ( array_key_exists( 'host', $args ) ) {
      $host = $args['host'];
    } else {
      $host = str_ireplace( 'https://', '', str_ireplace( 'http://', '', $host ) );
    }

    return $host;
  }

  /**
   * Check whether the given post should be indexed.
   *
   * @param object $post  A WordPress post object.
   * @return boolean      Whether or not to index the given post.
   *
   * @since 1.0.0
   */
  private function shouldIndex( $post ) {
    $api_helper = new \ES_Feeder\Admin\Helpers\API_Helper( $this->plugin );

    return $api_helper->get_index_to_cdp( $post->ID );
  }
}
