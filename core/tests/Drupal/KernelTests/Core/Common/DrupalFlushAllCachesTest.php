<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\KernelTests\KernelTestBase;

/**
 * @covers ::drupal_flush_all_caches
 * @group Common
 */
class DrupalFlushAllCachesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that drupal_flush_all_caches() uses core.extension properly.
   */
  public function testDrupalFlushAllCachesModuleList() {
    $core_extension = \Drupal::configFactory()->getEditable('core.extension');
    $module = $core_extension->get('module');
    $module['system_test'] = -10;
    $core_extension->set('module', module_config_sort($module))->save();

    drupal_flush_all_caches();

    $this->assertSame(['system_test', 'system'], array_keys($this->container->getParameter('container.modules')));
  }

}
