<?php
if ( ! class_exists( 'ES_API_HELPER' ) ) {

  class ES_API_HELPER {

    const PLUGIN_NAME  = 'wp-es-feeder';
    const NAME_SPACE   = 'elasticsearch/v1';
    const SYNC_TIMEOUT = 10; // minutes

    public static function get_post_type_label( $post_type = 'post', $display = 'name' ) {
      $obj = get_post_type_object( $post_type );
      if ( is_object( $obj ) ) {
        $labels = $obj->labels;
      }
      return strtolower( isset( $labels ) ? $labels->$display : $post_type );
    }

    public static function get_featured_image( $id ) {
      $image = wp_prepare_attachment_for_js( $id );
      $data  = array(
        'id'      => $image['id'],
        'title'   => $image['title'],
        'alt'     => $image['alt'],
        'caption' => $image['caption'],
        'mime'    => $image['mime'],
        'sizes'   => $image['sizes'],
      );
      return $data;
    }

    public static function get_image_size_array( $id ) {
      $image = wp_prepare_attachment_for_js( $id );
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

    public static function get_language( $id ) {
      global $feeder, $sitepress;
      if ( $sitepress ) {
        $output           = apply_filters( 'wpml_post_language_details', null, $id );
        $output['locale'] = str_replace( '_', '-', $output['locale'] );
        if ( $output['locale'] ) {
          return self::get_language_by_locale( $output['locale'] );
        }
      } elseif ( get_post_type( $id ) === 'post' ) {
        $options       = get_option( $feeder->get_plugin_name() );
        $use_post_lang = array_key_exists( 'es_post_language', $options ) && $options['es_post_language'] ? 1 : 0;
        $locale        = get_post_meta( $id, '_iip_language', true );
        if ( $use_post_lang && $locale ) {
          return self::get_language_by_locale( $locale );
        }
      }
      return self::get_language_by_locale( strtolower( str_replace( '_', '-', get_locale() ) ) );
    }

    public static function get_owner( $id ) {
      global $feeder;
      if ( get_post_type( $id ) === 'post' ) {
        $options        = get_option( $feeder->get_plugin_name() );
        $use_post_owner = array_key_exists( 'es_post_owner', $options ) && $options['es_post_owner'] ? 1 : 0;
        $owner          = get_post_meta( $id, '_iip_owner', true );
        if ( $use_post_owner && $owner ) {
          return $owner;
        }
      }
      return get_bloginfo( 'name' );
    }

    public static function get_index_to_cdp( $id ) {
      $value = get_post_meta( $id, '_iip_index_post_to_cdp_option', true );

      return ( $value === 'no' ) ? false : true;
    }

    public static function get_language_by_locale( $locale ) {
      global $cdp_language_helper;
      return $cdp_language_helper->get_language_by_code( $locale );
    }

    public static function get_language_by_meta_field( $id, $meta_field ) {
      global $cdp_language_helper;
      return $cdp_language_helper->get_language_by_meta_field( $id, $meta_field );
    }

    public static function get_related_translated_posts( $id, $post_type ) {
      global $cdp_language_helper;
      return $cdp_language_helper->get_translations( $id );
    }

    public static function get_categories( $id ) {
      $categories = wp_get_post_categories(
           $id,
          array(
         'fields' => 'all',
		  )
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
      }
      return $output;
    }


    public static function get_site_taxonomies( $id ) {
      $custom_taxonomies = get_taxonomies( array( 'public' => true ) );
      $taxonomies        = get_post_taxonomies( $id );

      $output = array();

      if ( ! empty( $taxonomies ) ) {
        foreach ( $taxonomies as $taxonomy ) {
          if ( in_array( $taxonomy, $custom_taxonomies ) ) {
            $terms = wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'all' ) );
            if ( count( $terms ) ) {
              // rename the key from WordPress defaults for categories and tags to align with what CDP expects
              if ( $taxonomy === 'category' ) {
                $taxonomy = 'categories';
              } elseif ( $taxonomy === 'post_tag' ) {
                $taxonomy = 'tags';
              }
              $output[ $taxonomy ] = self::remap_terms( $terms );
            }
          }
        }
      }
      return $output;
    }

    public static function remap_terms( $terms ) {
      $arr = array();
      foreach ( $terms as $term ) {
        $arr[] = array(
          'id'   => $term->term_id,
        // 'slug' => $term->slug,
          'name' => $term->name,
        );
      }
      return $arr;
    }

    public static function get_categories_searchable( $id ) {
      $categories = wp_get_post_categories(
           $id,
          array(
         'fields' => 'all',
		  )
          );

      $output = array();

      if ( ! empty( $categories ) ) {
        foreach ( $categories as $category ) {
          $output[] = $category->slug;
        }
      }
      return $output;
    }

    public static function get_tags( $id ) {
      $tags = wp_get_post_tags( $id );

      $output = array();

      if ( ! empty( $tags ) ) {
        foreach ( $tags as $tag ) {
          $output[] = array(
            'id'   => $tag->term_id,
            'slug' => $tag->slug,
            'name' => $tag->name,
          );
        }
      }
      return $output;
    }

    public static function get_tags_searchable( $id ) {
      $tags = wp_get_post_tags( $id );

      $output = array();

      if ( ! empty( $tags ) ) {
        foreach ( $tags as $tag ) {
          $output[] = $tag->slug;
        }
      }
      return $output;
    }

    public static function get_author( $id ) {
      $data = array(
        'id'   => (int) $id,
       // 'name' => get_the_author_meta( 'nicename', $id ),
        'name' => get_the_author_meta( 'display_name', $id ),
      );
      return $data;
    }

    /**
     * Renders Visual Composer shortcodes if Visual Composer is turned on
     *
     * @param [type] $object
     * @return string
     */
    public static function render_vs_shortcodes( $object ) {
      if ( ! class_exists( 'WPBMap' ) ) { // VC Class
        return apply_filters( 'the_content', $object->post_content );
      }

      WPBMap::addAllMappedShortcodes();

      global $post;
      $post   = get_post( $object->ID );
      $output = apply_filters( 'the_content', $post->post_content );

      return $output;
    }
  }
}
