<?php
/**
 * Registers the Gutenberg class.
 *
 * @package ES_Feeder\Gutenberg
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Adds indexing metaboxes available as a Gutenberg plugin when the new editor is enabled.
 *
 * @package ES_Feeder\Gutenberg
 * @since 3.0.0
 */
class Gutenberg {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $plugin     The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin ) {
    $this->plugin = $plugin;
  }

  /**
   * Adds custom settings fields to the Gutenberg Editor.
   *
   * @since 3.0.0
   */
  public function register_gutenberg_plugins() {
    $script_asset = require ES_FEEDER_DIR . 'admin/build/gpalab-feeder-gutenberg-plugin.asset.php';

    wp_register_script(
      'gpalab-feeder-gutenberg-plugin',
      ES_FEEDER_URL . 'admin/build/gpalab-feeder-gutenberg-plugin.js',
      $script_asset['dependencies'],
      $script_asset['version'],
      true
    );
  }

  /**
   * Enqueue the JavaScript required for customization of the Gutenberg Editor.
   *
   * @since 3.0.0
   */
  public function enqueue_gutenberg_plugin() {
    // Check if the Gutenberg editor is enabled.
    $is_gutenberg = get_current_screen()->is_block_editor();

    if ( $is_gutenberg ) {
      // Get list of indexable post types.
      $options   = get_option( $this->plugin );
      $indexable = ! empty( $options['es_post_types'] ) ? $options['es_post_types'] : array();

      // Get current post type.
      $post_type = get_current_screen()->post_type;

      // If current post type is indexable, enqueue the admin script.
      if ( array_key_exists( $post_type, $indexable ) ) {
        $this->localize_gutenberg_plugin();

        wp_enqueue_script( 'gpalab-feeder-gutenberg-plugin' );
      }
    }
  }

  /**
   * Pass required PHP values as variables to admin JS.
   *
   * @since 3.0.0
   */
  private function localize_gutenberg_plugin() {
    $language_helper = new Admin\Helpers\Language_Helper( $this->namespace, $this->plugin );
    $languages       = $language_helper->get_languages();
    $normalized      = array();

    foreach ( $languages as $lang ) {
      $item = array();

      $item['value'] = $lang->locale;
      $item['label'] = $lang->display_name;

      array_push( $normalized, $item );
    }

    wp_localize_script(
      'gpalab-feeder-gutenberg-plugin',
      'gpalabFeederAdmin',
      array(
        'languages' => $normalized,
      )
    );
  }
}
