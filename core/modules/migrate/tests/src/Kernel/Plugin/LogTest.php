<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Tests the Log process plugin.
 *
 * @group migrate
 */
class LogTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate'];

  /**
   * Test the Log plugin.
   */
  public function testLog() {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('log');
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row();
    $log_message = "Testing the log message";

    // Ensure the log is getting saved.
    $saved_message = $plugin->transform($log_message, $executable, $row, 'buffalo');
    $this->assertSame($log_message, $saved_message);
  }

}
