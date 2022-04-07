<?php
/**
 * Augment the PHPUnit TestCase class with BrainMonkey.
 * This is useful to mock out interactions with WordPress.
 *
 * @package ES_Feeder\Lab_Monkey
 */

namespace ES_Feeder;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Initialize tests cases with Brain Monkey mocking.
 *
 * @since 3.0.0
 */
class Lab_Monkey extends TestCase {

    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

  /**
   * Set up function.
   *
   * @since 3.0.0
   */
  protected function setUp(): void {
      parent::setUp();
      Monkey\setUp();
  }

  /**
   * Tear down function.
   *
   * @since 3.0.0
   */
  protected function tearDown(): void {
      Monkey\tearDown();
      parent::tearDown();
  }
}
