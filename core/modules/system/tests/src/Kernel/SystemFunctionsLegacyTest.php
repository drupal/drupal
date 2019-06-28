<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\Core\Extension\Extension;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated system module functions.
 *
 * @group system
 * @group legacy
 */
class SystemFunctionsLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * @expectedDeprecation system_rebuild_module_data() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Instead, you should use \Drupal::service("extension.list.module")->getList(). See https://www.drupal.org/node/2709919
   * @see system_rebuild_module_data()
   */
  public function testSystemRebuildModuleDataDeprecation() {
    $list = system_rebuild_module_data();
    $this->assertInstanceOf(Extension::class, $list['system']);
  }

}
