<?php

namespace Drupal\Tests\layout_discovery\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Layout functionality.
 *
 * @group Layout
 */
class LayoutDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'layout_discovery',
    'layout_deprecation_test',
  ];

  /**
   * Test plugin deprecation.
   *
   * @group legacy
   */
  public function testLayoutDeprecation() {
    $this->expectDeprecation('foo');
    $this->container->get('plugin.manager.core.layout')
      ->createInstance('layout_deprecation_test', []);
  }

}
