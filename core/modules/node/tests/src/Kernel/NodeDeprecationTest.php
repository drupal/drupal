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
    $this->expectDeprecation("node_mark() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There's no replacement for this function. See https://www.drupal.org/node/3037203");
    node_mark(123, time());
  }

}
