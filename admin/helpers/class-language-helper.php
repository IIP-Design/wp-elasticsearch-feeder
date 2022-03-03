<?php
/**
 * Registers the Language_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Language_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers the helper functions used to manage post translations.
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
   * The unique identifier this plugin.
   *
   * @var string $plugin
   *
   * @access protected
   * @since 3.0.0
   */
  protected $plugin;

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
    $this->languages    = get_option( 'cdp_languages' );
    $this->plugin       = ES_FEEDER_NAME;
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

    unset( $lang );

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
    $post_actions = new \ES_Feeder\Post_Actions();
    $log_helper   = new Log_Helper();

    $languages = array();

    // If in the process of an AJAX request return the stored owner values.
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      $stored = $this->languages;

      if ( $stored ) {
        $languages = $stored;
      }

      return $languages;
    }

    $args = array(
      'method' => 'GET',
      'url'    => 'language',
    );

    // Request the list of languages from the API.
    $data = $post_actions->request( $args );

    if ( $data ) {
      // Look of errors in the form of an object.
      if ( is_object( $data ) && $data->error ) {
        $log_helper->log( $data->error );
      }

      // If the response is an array, as expected...
      if ( is_array( $data ) ) {
        // Make sure there are no errors...
        if ( array_key_exists( 'error', $data ) && $data['error'] ) {
          $log_helper->log( $data['error'] );
        } else {
          // And iterate through the array of languages.
          foreach ( $data as $lang ) {
            $languages[ $lang->locale ] = $lang;
          }

          unset( $lang );
        }
      }
    }

    update_option( 'cdp_languages', $languages );

    return $languages;
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

      // Skip if the language cannot be detected.
      $lang = $this->get_language_by_code( $code );

      if ( ! $lang ) {
        continue;
      }

      // Skip if the post id not published in WP.
      $status = get_post_status( $id );

      if ( 'publish' !== $status ) {
        continue;
      }

      // Skip if the post is set to not be indexed.
      $sync = get_post_meta( $id, '_iip_index_post_to_cdp_option', true );

      if ( 'no' === $sync ) {
        continue;
      }

      // Construct the translations array.
      $translations[] = array(
        'post_id'  => $id,
        'language' => $lang,
      );
    }

    unset( $result );

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

    // Short circuit if WPML isn't present.
    if ( ! class_exists( 'SitePress' ) ) {
      return array();
    }

    // Retrieve the post's translations information from cache.
    $post_cache_key  = 'wpml_properties';
    $wpml_properties = wp_cache_get( $post_cache_key, 'gpalab_feeder' );

    /**
     * Get the WPML translation ID and the element type for the given post.
     *
     * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
     */
    if ( false === $wpml_properties ) {
      $wpml_properties = $wpdb->get_row(
        $wpdb->prepare(
          "SELECT trid, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id = %d",
          $post_id
        )
      );
      // phpcs:enable

      // Cache the results of the query.
      wp_cache_set( $post_cache_key, $wpml_properties, 'gpalab_feeder' );
    }

    // If post not found in translations table return.
    if ( ! $wpml_properties || ! $wpml_properties->trid || ! $wpml_properties->element_type ) {
      return array();
    }

    // Retrieve the list translations for the given post from cache.
    $trans_cache_key = 'wpml_translations';
    $trans_results   = wp_cache_get( $trans_cache_key, 'gpalab_feeder' );

    /**
     * Get the post id and language code for all other posts with the
     * same WPML translation ID and element type as the initial post.
     *
     * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
     */
    if ( false === $trans_results ) {
      $trans_results = $wpdb->get_results(
        $wpdb->prepare(
          "SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid = %d AND element_type = %s AND element_id != %d",
          $wpml_properties->trid,
          $wpml_properties->element_type,
          $post_id
        )
      );
      // phpcs:enable

      // Cache the results of the query.
      wp_cache_set( $trans_cache_key, $trans_results, 'gpalab_feeder' );
    }

    $translations = $this->normalize_translations( $trans_results, $post_id, 'WPML' );

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
    $log_helper = new Log_Helper();

    if ( ! function_exists( 'is_plugin_active' ) ) {
      include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( is_plugin_active( 'polylang/polylang.php' ) ) {

      // Get translations if Polylang is installed.
      return $this->get_polylang_translations( $post_id );

    } elseif ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {

      // Get translations if WPML is installed.
      return $this->get_wpml_translations( $post_id );

    } else {
      $log_helper->log( 'Translation support not detected. The WPML or PolyLang plugins are required to add translations.' );

      return array();
    }
  }
}
