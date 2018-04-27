<?php

global $cdp_language_helper;
$cdp_language_helper = new Language_Helper();

class Language_Helper {

  public $languages;

  public $backup_lang;

  public function __construct() {
    $this->backup_lang = (object) array(
      'language_code' => 'en',
      'locale' => 'en-us',
      'text_direction' => 'ltr',
      'display_name' => 'English',
      'native_name' => 'English'
    );
  }

  public function get_language_by_locale( $locale ) {
    $locale = strtolower($locale);
    if ( !$this->languages ) $this->load_languages();
    if ( !$this->languages || !count($this->languages)) {
      if ( $locale == 'en' || $locale == 'en-us' )
        return $this->backup_lang;
      return null;
    }
    return $this->languages[strtolower($locale)];
  }

  public function get_language_by_code( $code ) {
    $code = strtolower($code);
    if ($code === 'en') $code = 'en-us';
    if ( !$this->languages ) $this->load_languages();
    if ( !$this->languages || !count($this->languages)) {
      if ( $code == 'en-us' ) return $this->backup_lang;
      return null;
    }
    $code_match = null;
    $locale_match = null;
    foreach ($this->languages as $lang) {
      if (strtolower($lang->locale) === $code)
        $locale_match = $lang;
      if (strtolower($lang->language_code) === $code)
        $code_match = $lang;
    }
    if ($locale_match)
      return $locale_match;
    return $code_match;
  }

  public function get_language_by_meta_field( $id, $meta_field ) {
    $locale = get_post_meta( $id, $meta_field, true );   //'
    $locale = empty( $locale ) ? 'en' : $locale;
    if ( !$this->languages ) $this->load_languages();
    return $this->languages[strtolower($locale)];
  }

  public function load_languages() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
      $this->languages = get_option('cdp_languages');
      if ($this->languages) return;
    }
    global $feeder;
    if ( !$feeder ) return;
    $args = [
      'method' => 'GET',
      'url' => 'language'
    ];
    $data = $feeder->es_request($args);
    if ( $data && count( $data )
        && (!is_array( $data ) || !array_key_exists( 'error', $data ) || !$data[ 'error' ])
        && (!is_object( $data ) || !$data->error) ) {
      $this->languages = [];
      foreach ( $data as $lang ) {
        $this->languages[$lang->locale] = $lang;
      }
    }
    update_option('cdp_languages', $this->languages);
  }

  public function get_languages() {
    if ( !$this->languages ) $this->load_languages();
    if ( !$this->languages || !count($this->languages)) return ['en' => $this->backup_lang, 'en-us' => $this->backup_lang];
    return $this->languages;
  }

  public function get_translations($post_id) {
    global $wpdb;
    if ( !function_exists( 'icl_object_id' ) ) return [];
    $query = "SELECT trid, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id = $post_id";
    $vars = $wpdb->get_row($query);
    if (!$vars || !$vars->trid || !$vars->element_type) return [];
    $query = "SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid = $vars->trid AND element_type = '$vars->element_type' AND element_id != $post_id";
    $results = $wpdb->get_results($query);
    $translations = [];
    foreach ($results as $result) {
      $lang = $this->get_language_by_code($result->language_code);
      if (!$lang) continue;
      $status = get_post_status($result->element_id);
      if ($status !== 'publish') continue;
      $sync = get_post_meta($result->element_id, '_iip_index_post_to_cdp_option', true);
      if ($sync !== 'yes') continue;
      $translations[] = [
        'post_id' => $result->element_id,
        'language' => $lang
      ];
    }
    return $translations;
  }

}