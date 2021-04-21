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
    $is_gutenberg = get_current_screen()->is_block_editor();

    if ( $is_gutenberg ) {
      wp_enqueue_script( 'gpalab-feeder-gutenberg-plugin' );
    }
  }
}
