<?php
if ( ! class_exists( 'ES_API_HELPER' ) ) {

  class ES_API_HELPER {

    const PLUGIN_NAME = 'wp-es-feeder';

    public static function get_post_type_label( $post_type = 'post', $display = 'name' ) {
      $obj = get_post_type_object( $post_type );
      if ( is_object( $obj ) ) {
        $labels = $obj->labels;
      }
      return strtolower( isset( $labels ) ? $labels->$display : $post_type );
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
  }
}
