<?php
/**
 * Plugin Name: Content Commons Feeder
 * Plugin URI: https://github.com/IIP-Design/wp-elasticsearch-feeder
 * Description: Creates REST API endpoints for each post type and indexes them into Elasticsearch.
 * Version: v3.0.1
 * Author: U.S. Department of State, Bureau of Global Public Affairs Digital Lab <gpa-lab@america.gov>
 * Author URI: https://lab.america.gov
 * License: GNU General Public License v2.0
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * Text Domain: gpalab-feeder
 *
 * @package  ES_Feeder
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

// Define plugin constants.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-constants.php';

ES_Feeder\Constants::define_constants();

/**
 * Autoload plugin's class to make them available without require statements.
 *
 * @param string $class_name   The name of the class getting called.
 *
 * @since 3.0.0
 */
function feeder_autoloader( $class_name ) {
  // Plugin-specific namespace prefix.
  $prefix = 'ES_Feeder\\';

  /**
   * Check if the class use the namespace prefix.
   * If not, move to the next registered autoloader.
   */
  $len = strlen( $prefix );
  if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
    return;
  }

  // Get the relative class name.
  $relative_class = str_replace( '_', '-', substr( $class_name, $len ) );
  $exploded_class = explode( '\\', strtolower( $relative_class ) );

  // Build file path to class file.
  $class_file = 'class-' . end( $exploded_class ) . '.php';
  $remainder  = array_slice( $exploded_class, 0, -1, false );
  $filepath   = implode( DIRECTORY_SEPARATOR, $remainder ) . DIRECTORY_SEPARATOR . $class_file;
  $file       = ES_FEEDER_DIR . $filepath;

  // If the file exists, require it.
  if ( file_exists( $file ) ) {
    require $file;
  }
};

// Register our autoload function with SPL.
spl_autoload_register( 'feeder_autoloader' );

/**
 * Clean up site when the plugin is deleted.
 *
 * @since 3.0.0
 */
function gpalab_es_feeder_uninstall() {
  require_once plugin_dir_path( __FILE__ ) . 'includes/class-uninstall.php';

  ES_Feeder\Uninstall::uninstall();
}
register_uninstall_hook( __FILE__, 'gpalab_es_feeder_uninstall' );

// Load the ES_Feeder class.
require plugin_dir_path( __FILE__ ) . 'includes/class-es-feeder.php';

/**
 * Begin execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 3.0.0
 */
function run_gpalab_feeder() {
  $feeder = new ES_Feeder();
  $feeder->run();
}

run_gpalab_feeder();
