<?php
class ES_API_HELPER {
  public static function get_post_type_label( $post_type = 'post', $display = 'name' ) {
    $obj = get_post_type_object( $post_type );
    if ( is_object( $obj ) ) {
      $labels = $obj->labels;
    }
    return strtolower( isset( $labels ) ? $labels->$display : $post_type );
  }
}
