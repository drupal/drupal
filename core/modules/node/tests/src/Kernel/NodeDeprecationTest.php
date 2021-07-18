<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group node
 * @group legacy
 */
class NodeDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * @see node_mark()
   */
  public function testNodeMarkDeprecation() {
    $this->expectDeprecation("Calling drupal_static_reset() with 'node_mark' as argument is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3037203");
    drupal_static_reset('node_mark');
  }

}
