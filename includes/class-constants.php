<?php
/**
 * Registers the Constants class.
 *
 * @package ES_Feeder\Constants
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Define all the constant values needed to configure the plugin.
 *
 * @package ES_Feeder\Constants
 * @since 3.0.0
 */
class Constants {

  /**
   * Define plugin constants.
   *
   * @since 3.0.0
   */
  public static function define_constants() {

    /*--- The test env runs outside of the WordPress context so some constants must be defined differently ---*/

    // Check if in running unit tests.
    $testing = defined( 'ES_FEEDER_PHPUNIT' ) ? ES_FEEDER_PHPUNIT : false;

    // Set plugin directory.
    if ( ! defined( 'ES_FEEDER_DIR' ) ) {
      if ( ! $testing ) {
        define( 'ES_FEEDER_DIR', plugin_dir_path( dirname( __FILE__, 2 ) ) . 'wp-elasticsearch-feeder/' );
      } else {
        define( 'ES_FEEDER_DIR', dirname( __FILE__, 2 ) );
      }
    }

    // Set plugin URL.
    if ( ! defined( 'ES_FEEDER_URL' ) ) {
      if ( ! $testing ) {
        define( 'ES_FEEDER_URL', plugin_dir_url( dirname( __FILE__, 2 ) ) . 'wp-elasticsearch-feeder/' );
      } else {
        define( 'ES_FEEDER_URL', dirname( __FILE__, 2 ) );
      }
    }

    // Set the plugin version.
    if ( ! defined( 'ES_FEEDER_VERSION' ) ) {
      if ( ! $testing ) {
        if ( ! function_exists( 'get_plugin_data' ) ) {
          require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data( __FILE__, 2 );

        // Dynamically pull the plugin version from the plugin data.
        define( 'ES_FEEDER_VERSION', $plugin_data['Version'] );
      } else {
        define( 'ES_FEEDER_VERSION', 'dev' );
      }
    }

    /*--- The following constants are the same regardless of environment ---*/

    // Set the plugin name.
    if ( ! defined( 'ES_FEEDER_NAME' ) ) {
      define( 'ES_FEEDER_NAME', 'wp-es-feeder' );
    }

    // Set the plugin's WP API namespace.
    if ( ! defined( 'ES_FEEDER_API_NAMESPACE' ) ) {
      define( 'ES_FEEDER_API_NAMESPACE', 'gpalab-cdp/v1' );
    }
  }
}
