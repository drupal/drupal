<?php

namespace Drupal\Tests\editor\Kernel;

use Drupal\editor\Ajax\GetUntransformedTextCommand;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the class \Drupal\editor\Ajax\GetUntransformedTextCommand deprecation.
 *
 * @group editor
 * @group legacy
 */
class EditorGetUntransformedTextCommandTest extends KernelTestBase {

  /**
   * Tests class \Drupal\editor\Ajax\GetUntransformedTextCommand deprecation.
   */
  public function testGetUntransformedTextCommandDeprecation() {
    $this->expectDeprecation('The Drupal\editor\Ajax\GetUntransformedTextCommand is deprecated in drupal:9.5.0 and is removed from drupal:10.0.0. There is no replacement. See https://www.drupal.org/node/3271653');
    new GetUntransformedTextCommand('');
  }

}
