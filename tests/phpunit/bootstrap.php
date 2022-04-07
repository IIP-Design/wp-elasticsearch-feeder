<?php
/**
 * Loads all the classes necessary to run tests.
 *
 * @package ES_Feeder\Tests\Bootstrap
 */

// Indicate that we are in a test environment.
define( 'ES_FEEDER_PHPUNIT', true );

// Define fake ABSPATH for the tests.
if ( ! defined( 'ABSPATH' ) ) {
  define( 'ABSPATH', sys_get_temp_dir() );
}

// Define the plugin-relevant constants.
require_once __DIR__ . './../../includes/class-constants.php';

ES_Feeder\Constants::define_constants();

// Load the plugin/vendor classes.
require_once __DIR__ . './../../vendor/autoload.php';

// Load in the BrainMonkey extension to PHPUnit's test case class.
require_once __DIR__ . './../class-lab-monkey.php';
