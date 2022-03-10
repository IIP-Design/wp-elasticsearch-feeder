<?php
/**
 * The following snippets uses `PLUGIN` to prefix
 * the constants and class names. You should replace
 * it with something that matches your plugin name.
 *
 * @package ES_Feeder\Lab_Monkey\Bootstrap
 */

// Define test environment.
define( 'ES_FEEDER_PHPUNIT', true );

if ( ! defined( 'ES_FEEDER_NAME' ) ) {
  define( 'ES_FEEDER_NAME', 'wp-es-feeder' );
}

// Define fake ABSPATH.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() );
}

// Define fake PLUGIN_ABSPATH.
if ( ! defined( 'ES_FEEDER_ABSPATH' ) ) {
	define( 'ES_FEEDER_ABSPATH', sys_get_temp_dir() . '/wp-content/plugins/wp-elasticsearch-feeder/' );
}

require_once __DIR__ . './../../vendor/autoload.php';
require_once __DIR__ . './../class-lab-monkey.php';
