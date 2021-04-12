<?php
/**
 * Plugin Name: WP Elasticsearch Feeder
 * Plugin URI: https://github.com/IIP-Design/wp-elasticsearch-feeder
 * Description: Creates REST API endpoints for each post type and indexes them into Elasticsearch.
 * Version: v2.5.0
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

// Define constants.
define( 'ES_FEEDER_DIR', plugin_dir_path( dirname( __FILE__ ) ) . 'wp-elasticsearch-feeder/' );
define( 'ES_FEEDER_URL', plugin_dir_url( dirname( __FILE__ ) ) . 'wp-elasticsearch-feeder/' );

// Load the ES_Feeder class.
require plugin_dir_path( __FILE__ ) . 'includes/class-es-feeder.php';

/**
 * Begin execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
$feeder = new ES_Feeder();
$feeder->run();
