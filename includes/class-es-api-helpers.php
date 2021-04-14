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

    public static function get_language_by_meta_field( $id, $meta_field ) {
      $language_helper = new \ES_Feeder\Admin\Helpers\Language_Helper();

      return $language_helper->get_language_by_meta_field( $id, $meta_field );
    }

    public static function get_related_translated_posts( $id, $post_type ) {
      $language_helper = new \ES_Feeder\Admin\Helpers\Language_Helper();

      return $language_helper->get_translations( $id );
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
  }
}
