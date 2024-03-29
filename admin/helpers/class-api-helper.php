<?php
/**
 * Registers the API_Helper class.
 *
 * @package ES_Feeder\API_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers API helper functions.
 *
 * @package ES_Feeder\API_Helper
 * @since 3.0.0
 */
class API_Helper {

  /**
   * The name of the plugin-specific API endpoint.
   *
   * @var string $namespace
   *
   * @access protected
   * @since 3.0.0
   */
  protected $namespace;

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
    $this->namespace = ES_FEEDER_API_NAMESPACE;
    $this->plugin    = ES_FEEDER_NAME;
  }

  /**
   * Get the display name for a given post type.
   *
   * @param string $post_type    The post type for which to retrieve the display name.
   * @param string $display      The post type labels property to retrieve.
   * @return string              The requested property value as a lowercase string.
   *
   * @since 1.0.0
   */
  public function get_post_type_label( $post_type = 'post', $display = 'name' ) {
    $obj = get_post_type_object( $post_type );

    if ( is_object( $obj ) ) {
      $labels = $obj->labels;
    }

    return strtolower( isset( $labels ) ? $labels->$display : $post_type );
  }

  /**
   * Retrieves the image properties for a given attachment.
   *
   * @param int $id   The selected WordPress attachment id.
   * @return array    The array of image data.
   *
   * @since 1.0.0
   */
  public function get_featured_image( $id ) {
    $image = wp_prepare_attachment_for_js( $id );

    $data = array(
      'id'      => $image['id'],
      'title'   => $image['title'],
      'alt'     => $image['alt'],
      'caption' => $image['caption'],
      'mime'    => $image['mime'],
      'sizes'   => $image['sizes'],
    );

    return $data;
  }

  /**
   * Retrieves the image properties for a given post's featured image.
   *
   * @param int|false $id   The selected WordPress post's thumbnail id.
   * @return array          The array of image data.
   *
   * @since 3.0.0
   */
  public function get_image_metadata( $id ) {
    $image = wp_prepare_attachment_for_js( $id );

    // Define a fallback sizes array.
    $sizes = array(
      'small'  => null,
      'medium' => null,
      'large'  => null,
      'full'   => null,
    );

    if ( ! $image ) {
      return array( 'sizes' => $sizes );
    }

    foreach ( $image['sizes'] as $size ) {
      if ( $size['width'] < 400 ) {
        if ( ! $sizes['small'] || $size['width'] > $sizes['small']['width'] ) {
          $sizes['small'] = $size;
        }
      } elseif ( $size['width'] >= 400 && $size['width'] <= 900 ) {
        if ( ! $sizes['medium'] || $size['width'] > $sizes['medium']['width'] ) {
          $sizes['medium'] = $size;
        }
      } elseif ( $size['width'] > 900 && $size['width'] < 3000 ) {
        if ( ! $sizes['large'] || $size['width'] > $sizes['large']['width'] ) {
          $sizes['large'] = $size;
        }
      }
    }

    unset( $size );

    if ( $image['sizes']['full'] ) {
      $sizes['full'] = $image['sizes']['full'];
    }

    $meta = array(
      'name'     => $image['title'],
      'alt'      => $image['alt'],
      'caption'  => $image['caption'],
      'longdesc' => $image['description'],
      'sizes'    => $sizes,
    );

    return $meta;
  }

  /**
   * Retrieve language information of a post by its ID.
   *
   * @param int $id   The WordPress post id.
   * @return string   The
   *
   * @since 1.0.0
   */
  public function get_language( $id ) {
    global $sitepress;

    $language_helper = new \ES_Feeder\Admin\Helpers\Language_Helper();

    if ( $sitepress ) {
      $output           = apply_filters( 'wpml_post_language_details', null, $id );
      $output['locale'] = str_replace( '_', '-', $output['locale'] );

      if ( $output['locale'] ) {
        return $language_helper->get_language_by_code( $output['locale'] );
      }
    } elseif ( function_exists( 'pll_get_post_language' ) ) {
      $unformatted_locale = pll_get_post_language( $id, 'locale' );
      $locale             = strtolower( str_replace( '_', '-', $unformatted_locale ) );

      if ( $locale ) {
        return $language_helper->get_language_by_code( $locale );
      }
    } elseif ( get_post_type( $id ) === 'post' ) {
      $options       = get_option( $this->plugin );
      $use_post_lang = array_key_exists( 'es_post_language', $options ) && $options['es_post_language'] ? 1 : 0;
      $locale        = get_post_meta( $id, '_iip_language', true );

      if ( $use_post_lang && $locale ) {
        return $language_helper->get_language_by_code( $locale );
      }
    }

    return $language_helper->get_language_by_code( strtolower( str_replace( '_', '-', get_locale() ) ) );
  }

  /**
   * Retrieves the owner value for a given post.
   * If owner not set, it returns the site's blog name.
   *
   * @param int $id   The unique identifier for a given WordPress post.
   * @return string   The owner for the given post.
   *
   * @since 2.5.0
   */
  public function get_owner( $id ) {
    if ( get_post_type( $id ) === 'post' ) {
      $options        = get_option( $this->plugin );
      $use_post_owner = array_key_exists( 'es_post_owner', $options ) && $options['es_post_owner'] ? 1 : 0;
      $owner          = get_post_meta( $id, '_iip_owner', true );

      if ( $use_post_owner && $owner ) {
        return $owner;
      }
    }

    return get_bloginfo( 'name' );
  }

  /**
   * Get the current site name.
   *
   * @return string    The hostname of the current site.
   *
   * @since 2.0.0
   */
  public function get_site() {
    $opt  = get_option( $this->plugin );
    $url  = $opt['es_wpdomain'];
    $args = wp_parse_url( $url );

    // Parse our the host value from the WP Domain.
    if ( array_key_exists( 'host', $args ) ) {
      return $args['host'];
    } else {
      return str_ireplace( 'https://', '', str_ireplace( 'http://', '', $url ) );
    }
  }

  /**
   * Check whether the given post should be indexed.
   *
   * @param int $id   The unique identifier for a given WordPress post.
   * @return boolean  Whether or not to index the given post.
   *
   * @since 1.0.0
   */
  public function get_index_to_cdp( $id ) {
    $value = get_post_meta( $id, '_iip_index_post_to_cdp_option', true );

    return ( 'no' === $value ) ? false : true;
  }

  /**
   * Retrieve the list of ids, names, and slugs for each category assigned to a given post.
   *
   * @param int $id    A WordPress post id.
   * @return array     A list of category slug values.
   *
   * @since 1.0.0
   */
  public function get_categories( $id ) {
    $categories = wp_get_post_categories(
      $id,
      array( 'fields' => 'all' )
    );

    $output = array();

    if ( ! empty( $categories ) ) {
      foreach ( $categories as $category ) {
        $output[] = array(
          'id'   => (int) $category->term_id,
          'slug' => $category->slug,
          'name' => $category->name,
        );
      }

      unset( $category );
    }

    return $output;
  }

  /**
   * Retrieve the list of categories and tags assigned to a given post.
   *
   * @param int $id    A WordPress post id.
   * @return array     A taxonomy items.
   *
   * @since 3.0.0
   */
  public function get_site_taxonomies( $id ) {
    $custom_taxonomies = get_taxonomies( array( 'public' => true ) );
    $taxonomies        = get_post_taxonomies( $id );

    $output = array();

    if ( ! empty( $taxonomies ) ) {
      foreach ( $taxonomies as $taxonomy ) {
        if ( in_array( $taxonomy, $custom_taxonomies, true ) ) {
          $terms = wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'all' ) );

          if ( count( $terms ) ) {
            // Rename the key from WordPress defaults for categories and tags to align with what CDP expects.
            if ( 'category' === $taxonomy ) {
              $taxonomy = 'categories';
            } elseif ( 'post_tag' === $taxonomy ) {
              $taxonomy = 'tags';
            }

            $output[ $taxonomy ] = $this->remap_terms( $terms );
          }
        }
      }

      unset( $taxonomy );
    }

    return $output;
  }

  /**
   * Normalize taxonomy data.
   *
   * @param array $terms    List of taxonomy items.
   * @return array          Normalized list of taxonomy items.
   *
   * @since 1.0.0
   */
  private function remap_terms( $terms ) {
    $arr = array();

    foreach ( $terms as $term ) {
      $arr[] = array(
        'id'   => $term->term_id,
        'name' => $term->name,
      );
    }

    unset( $term );

    return $arr;
  }

  /**
   * Retrieve the list of slugs for each category assigned to a given post.
   *
   * @param int $id    A WordPress post id.
   * @return array     A list of category slug values.
   *
   * @since 1.0.0
   */
  public function get_categories_searchable( $id ) {
    $categories = wp_get_post_categories(
      $id,
      array( 'fields' => 'all' )
    );

    $output = array();

    if ( ! empty( $categories ) ) {
      foreach ( $categories as $category ) {
        $output[] = $category->slug;
      }

      unset( $category );
    }

    return $output;
  }

  /**
   * Retrieve the a post's tags.
   *
   * @param int $id   The unique identifier for a given WordPress post.
   * @return array    The id, name, and slug for each of the post's tags.
   *
   * @since 1.0.0
   */
  public function get_tags( $id ) {
    $tags = wp_get_post_tags( $id );

    $output = array();

    if ( ! empty( $tags ) ) {
      foreach ( $tags as $tag ) {
        $output[] = array(
          'id'   => $tag->term_id,
          'name' => $tag->name,
          'slug' => $tag->slug,
        );
      }

      unset( $tag );
    }

    return $output;
  }

  /**
   * Retrieve the slug for each of the post's tags.
   *
   * @param int $id   The unique identifier for a given WordPress post.
   * @return array    The slug for each of the post's tags.
   *
   * @since 1.0.0
   */
  public function get_tags_searchable( $id ) {
    $tags = wp_get_post_tags( $id );

    $output = array();

    if ( ! empty( $tags ) ) {
      foreach ( $tags as $tag ) {
        $output[] = $tag->slug;
      }

      unset( $tag );
    }

    return $output;
  }

  /**
   * Retrieve the a post author's information.
   *
   * @param int $id  The unique identifier for a given WordPress user.
   * @return array   The author's id and name.
   *
   * @since 1.0.0
   */
  public function get_author( $id ) {
    $user_data = array(
      'id'   => (int) $id,
      'name' => get_the_author_meta( 'display_name', $id ),
    );

    return $user_data;
  }

  /**
   * Render out the Visual Composer shortcodes if Visual Composer is present on the host site.
   *
   * @param object $post_object  A WordPress post object.
   * @return string              The content of the provided post.
   *
   * @since 1.0.0
   */
  public function render_vc_shortcodes( $post_object ) {
    // Short circuit is Visual Composer is not installed.
    if ( ! class_exists( 'WPBMap' ) ) {
      return apply_filters( 'the_content', $post_object->post_content );
    }

    // Apply the Visual Composer shortcodes.
    \WPBMap::addAllMappedShortcodes();

    $post   = get_post( $post_object->ID );
    $output = apply_filters( 'the_content', $post->post_content );

    return $output;
  }
}
