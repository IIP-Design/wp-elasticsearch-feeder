<?php
/**
 * Registers the Language_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Language_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers language helper functions.
 *
 * @package ES_Feeder\Admin\Helpers\Language_Helper
 * @since 3.0.0
 */
class Language_Helper {

  public $languages;

  /**
   * An English language object used as the fallback value.
   *
   * @var object $default_lang  The default language properties.
   *
   * @access protected
   * @since 3.0.0
   */
  protected $default_lang;

  /**
   * Initializes the class with the default language object.
   *
   * @since 3.0.0
   */
  public function __construct() {
    $this->default_lang = (object) array(
      'language_code'  => 'en',
      'locale'         => 'en-us',
      'text_direction' => 'ltr',
      'display_name'   => 'English',
      'native_name'    => 'English',
    );
  }

  /**
   * @since 2.0.0
   */
  public function get_language_by_code( $code ) {
    $code = strtolower( $code );

    if ( 'en' === $code ) {
      $code = 'en-us';
    }

    if ( ! $this->languages ) {
      $this->load_languages();
    }

    if ( ! $this->languages || ! count( $this->languages ) ) {
      if ( 'en-us' === $code ) {
        return $this->default_lang;
      }
      return null;
    }

    $code_match   = null;
    $locale_match = null;
    foreach ( $this->languages as $lang ) {
      if ( strtolower( $lang->locale ) === $code ) {
        $locale_match = $lang;
      }
      if ( strtolower( $lang->language_code ) === $code ) {
        $code_match = $lang;
      }
    }
    if ( $locale_match ) {
      return $locale_match;
    }
    return $code_match;
  }

  /**
   * @since 1.0.0
   */
  public function get_language_by_meta_field( $id, $meta_field ) {
    $locale = get_post_meta( $id, $meta_field, true );   // '
    $locale = empty( $locale ) ? 'en' : $locale;
    return $this->get_language_by_code( $locale );
  }

  /**
   * @since 2.0.0
   */
  public function load_languages() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      $this->languages = get_option( 'cdp_languages' );
      if ( $this->languages ) {
        return;
      }
    }
    global $feeder;
    if ( ! $feeder ) {
      return;
    }
    $args = array(
      'method' => 'GET',
      'url'    => 'language',
    );
    $data = $feeder->es_request( $args );
    if ( $data && count( $data ) && ! is_string( $data )
        && ( ! is_array( $data ) || ( is_array( $data ) && ( ! array_key_exists( 'error', $data ) || ! $data['error'] ) )
        && ( ! is_object( $data ) || ( is_object( $data ) && ! $data->error ) ) ) ) {
      $this->languages = array();
      foreach ( $data as $lang ) {
        $this->languages[ $lang->locale ] = $lang;
      }
    }
    update_option( 'cdp_languages', $this->languages );
  }

  /**
   * @since 2.0.0
   */
  public function get_languages() {
    if ( ! $this->languages ) {
      $this->load_languages();
    }

    if ( ! $this->languages || ! count( $this->languages ) ) {
      return array(
        'en'    => $this->default_lang,
        'en-us' => $this->default_lang,
      );
    }

    return $this->languages;
  }

  /**
   * @since 2.0.0
   */
  public function get_translations( $post_id ) {
    global $wpdb;
    if ( ! function_exists( 'icl_object_id' ) ) {
      return array();
    }
    $query = "SELECT trid, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id = $post_id";
    $vars  = $wpdb->get_row( $query );
    if ( ! $vars || ! $vars->trid || ! $vars->element_type ) {
      return array();
    }
    $query        = "SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid = $vars->trid AND element_type = '$vars->element_type' AND element_id != $post_id";
    $results      = $wpdb->get_results( $query );
    $translations = array();
    foreach ( $results as $result ) {
      $lang = $this->get_language_by_code( $result->language_code );
      if ( ! $lang ) {
        continue;
      }
      $status = get_post_status( $result->element_id );
      if ( $status !== 'publish' ) {
        continue;
      }
      $sync = get_post_meta( $result->element_id, '_iip_index_post_to_cdp_option', true );
      if ( 'no' === $sync ) {
        continue;
      }
      $translations[] = array(
        'post_id'  => $result->element_id,
        'language' => $lang,
      );
    }
    return $translations;
  }
}
