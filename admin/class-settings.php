<?php
/**
 * Registers the Settings class.
 *
 * @package ES_Feeder\Settings
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Add plugin settings page.
 *
 * The Settings class adds a settings page allowing site admins to configure the plugin.
 *
 * @package ES_Feeder\Settings
 * @since 3.0.0
 */
class Settings {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $plugin     The plugin name.
   * @param string $version    The plugin version number.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin, $version ) {
    $this->plugin  = $plugin;
    $this->version = $version;
  }

  /**
   * Register the administration menu.
   *
   * @since 3.0.0
   */
  public function add_plugin_admin_menu() {
    add_options_page(
      'WP Elasticsearch Feeder Settings',
      'WP ES Feeder',
      'manage_options',
      $this->plugin,
      array( $this, 'display_plugin_setup_page' )
    );
  }

  /**
   * Render the settings page for this plugin.
   *
   * @since 3.0.0
   */
  public function display_plugin_setup_page() {
    include_once ES_FEEDER_DIR . 'admin/partials/settings-view.php';
  }
}
