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
    $language_opts = $this->get_language_options();
    $owner_opts    = $this->get_owners_options();
    $sync_status   = $this->get_status();

    wp_localize_script(
      'gpalab-feeder-gutenberg-plugin',
      'gpalabFeederAdmin',
      array(
        'languages'  => $language_opts,
        'owners'     => $owner_opts,
        'syncStatus' => $sync_status,
      )
    );
  }

  /**
   * Retrieve the available languages normalized to be populate a
   * dropdown menu with an array of option values and labels.
   *
   * @return array   List of value/label pairs for each language.
   *
   * @since 3.0.0
   */
  private function get_language_options() {
    $language_helper = new Admin\Helpers\Language_Helper( $this->namespace, $this->plugin );
    $languages       = $language_helper->get_languages();
    $normalized      = array();

    foreach ( $languages as $lang ) {
      $item = array();

      $item['value'] = $lang->locale;
      $item['label'] = $lang->display_name;

      array_push( $normalized, $item );
    }

    return $normalized;
  }

  /**
   * Retrieve the available post owners normalized to be populate a
   * dropdown menu with an array of option values and labels.
   *
   * @return array   List of value/label pairs for each language.
   *
   * @since 3.0.0
   */
  private function get_owners_options() {
    $owner_helper = new Admin\Helpers\Owner_Helper( $this->namespace, $this->plugin );
    $owners       = $owner_helper->get_owners();
    $sitename     = get_bloginfo( 'name' );

    // Initialize the owners list with at current site name, which serves as the default owner.
    $normalized = array(
      array(
        'value' => $sitename,
        'label' => $sitename,
      ),
    );

    foreach ( $owners as $own ) {
      $item = array();

      $item['value'] = $own;
      $item['label'] = $own;

      array_push( $normalized, $item );
    }

    return $normalized;
  }

  /**
   * Retrieve the current post's coded publication status.
   *
   * @return array   The properties (color, title, & status text) of the current status.
   *
   * @since 3.0.0
   */
  private function get_status() {
    global $post;

    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    $sync_status = get_post_meta( $post->ID, '_cdp_sync_status', true );
    $status      = ! empty( $sync_status ) ? $sync_status : 'Never synced';

    return $sync_helper->get_status_code_data( $status, true );
  }
}
