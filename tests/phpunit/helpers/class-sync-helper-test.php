<?php
/**
 * Register tests for the plugin's Sync_Helper class.
 *
 * @package ES_Feeder\Tests\Sync_Helper_Test
 */

namespace ES_Feeder\Tests;

use ES_Feeder\Lab_Monkey;
use ES_Feeder\Admin\Helpers\Sync_Helper;
use Brain\Monkey;

/**
 * Test the sync helper functions.
 *
 * @since 3.0.0
 */
final class Sync_Helper_Test extends Lab_Monkey {

  /**
   * Provides the parameters passed into the status code test.
   */
  public function status_code_provider() {
    return array(
      'syncing'    => array(
        2,
        false,
        array(
          'color' => 'yellow',
          'title' => 'Republish Attempted',
        ),
      ),
      'sync-merge' => array(
        2,
        true,
        array(
          'color' => 'yellow',
          'title' => 'Publishing',
        ),
      ),
      'synced'     => array(
        3,
        false,
        array(
          'color' => 'green',
          'title' => 'Published',
        ),
      ),
      'error'      => array(
        5,
        false,
        array(
          'color' => 'red',
          'title' => 'Error',
        ),
      ),
    );
  }

  /**
   * Test the mapping of numerics status codes to a status array.
   *
   * @dataProvider status_code_provider
   */
  public function test_get_status_code_data( int $code, bool $merge, array $expected ) {
    $sync = new Sync_Helper();

    $this->assertSame(
      $expected,
      $sync->get_status_code_data( $code, $merge )
    );
  }
}
