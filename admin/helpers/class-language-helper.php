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
   * The list of available languages.
   *
   * @var array $languages  All languages.
   *
   * @access protected
   * @since 3.0.0
   */
  protected $languages;

  /**
   * Initializes the class with the default language object.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin   The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin ) {
    $this->default_lang = (object) array(
      'language_code'  => 'en',
      'locale'         => 'en-us',
      'text_direction' => 'ltr',
      'display_name'   => 'English',
      'native_name'    => 'English',
    );
    $this->languages    = get_option( 'cdp_languages' );
    $this->namespace    = $namespace;
    $this->plugin       = $plugin;
  }

  /**
   * Return the full language object for a given language code.
   *
   * @param string $code   A given ISO language code.
   * @return object        The language object corresponding to the provided code.
   *
   * @since 2.0.0
   */
  public function get_language_by_code( $code ) {
    $code = strtolower( $code );

    // Normalize two different versions of the English code.
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
   * Return the full language object based on a given metadata field.
   *
   * @param int    $id         A given WordPress post id.
   * @param string $meta_key   A given ISO language code.
   * @return object            The language object corresponding to the provided code.
   *
   * @since 1.0.0
   */
  public function get_language_by_meta_field( $id, $meta_key ) {
    $locale = get_post_meta( $id, $meta_key, true );
    $locale = empty( $locale ) ? 'en' : $locale;

    return $this->get_language_by_code( $locale );
  }

  /**
   * Get the available languages and save them to the cdp_languages option.
   *
   * @since 2.0.0
   */
  public function load_languages() {
    $post_actions = new \ES_Feeder\Post_Actions( $this->namespace, $this->plugin );

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      $this->languages = get_option( 'cdp_languages' );
      if ( $this->languages ) {
        return;
      }
    }

    $args = array(
      'method' => 'GET',
      'url'    => 'language',
    );

    $data = $post_actions->request( $args );
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
   * Get the list of available.
   *
   * @return array    List of available languages.
   *
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
   * Converts the list of translations from the format used
   * by the given translation plugin to a common format.
   *
   * @param array  $results  The translations mappings.
   * @param int    $post_id  The post in question.
   * @param string $plugin   The translation plugin in use.
   * @return array           A list of a posts translations.
   *
   * @since 3.0.0
   */
  public function normalize_translations( $results, $post_id, $plugin ) {
    // Initialize list of translations with empty array.
    $translations = array();

    foreach ( $results as $key => $result ) {
      // Get the required properties from the translation mappings.
      $code = 'WPML' === $plugin ? $result->language_code : $key;
      $id   = 'WPML' === $plugin ? $result->element_id : $result;

      // Polylang includes the provided post_id along with all translations.
      // Therefore we must omit it during the loop.
      if ( 'Polylang' === $plugin && $result === $post_id ) {
        continue;
      }

      $lang = $this->get_language_by_code( $code );

      if ( ! $lang ) {
        continue;
      }

      $status = get_post_status( $id );

      if ( 'publish' !== $status ) {
        continue;
      }

      $sync = get_post_meta( $id, '_iip_index_post_to_cdp_option', true );

      if ( 'no' === $sync ) {
        continue;
      }

      $translations[] = array(
        'post_id'  => $id,
        'language' => $lang,
      );
    }

    return $translations;
  }

  /**
   * Get the translated version of a given post when using WPML.
   *
   * @param int $post_id    The ID of the post in question.
   * @return array          A list of a posts translations.
   *
   * @since 3.0.0
   */
  public function get_wpml_translations( $post_id ) {
    global $wpdb;

    // Short circuit if required WPML function isn't present.
    if ( ! function_exists( 'icl_object_id' ) ) {
      return array();
    }

    $vars = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT trid, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id = %d",
        $post_id
      )
    );

    if ( ! $vars || ! $vars->trid || ! $vars->element_type ) {
      return array();
    }

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid = %d AND element_type = %s AND element_id != %d",
        $vars->trid,
        $vars->element_type,
        $post_id
      )
    );

    $translations = $this->normalize_translations( $results, $post_id, 'WPML' );

    return $translations;
  }

  /**
   * Get the translated version of a given post when using Polylang.
   *
   * @param int $post_id    The post in question.
   * @return array          A list of a posts translations.
   *
   * @since 3.0.0
   */
  public function get_polylang_translations( $post_id ) {
    // Short circuit if required Polylang functions isn't present.
    if ( ! function_exists( 'pll_get_post_translations' ) ) {
      return array();
    }

    $results = pll_get_post_translations( $post_id );

    $translations = $this->normalize_translations( $results, $post_id, 'Polylang' );

    return $translations;
  }

  /**
   * Get the translated versions for a given post.
   *
   * @param int $post_id   A given WordPress post id.
   * @return array         A list of a posts translations.
   *
   * @since 2.0.0
   */
  public function get_translations( $post_id ) {
    if ( is_plugin_active( 'polylang/polylang.php' ) ) {

      // Get translations if Polylang is installed.
      return $this->get_polylang_translations( $post_id );

    } elseif ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {

      // Get translations if WPML is installed.
      return $this->get_wpml_translations( $post_id );

    } else {
      return array();
    }
  }
}
