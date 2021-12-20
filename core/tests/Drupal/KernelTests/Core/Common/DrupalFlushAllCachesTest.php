<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers ::drupal_flush_all_caches
 * @group Common
 */
class DrupalFlushAllCachesTest extends KernelTestBase {

  /**
   * Stores the number of container builds.
   *
   * @var int
   */
  protected $containerBuilds = 0;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that drupal_flush_all_caches() uses core.extension properly.
   */
  public function testDrupalFlushAllCachesModuleList() {
    $this->assertFalse(function_exists('system_test_help'));
    $core_extension = \Drupal::configFactory()->getEditable('core.extension');
    $module = $core_extension->get('module');
    $module['system_test'] = -10;
    $core_extension->set('module', module_config_sort($module))->save();
    $this->containerBuilds = 0;
    drupal_flush_all_caches();
    $module_list = ['system_test', 'system'];
    $database_module = \Drupal::database()->getProvider();
    if ($database_module !== 'core') {
      $module_list[] = $database_module;
    }
    sort($module_list);
    $container_modules = array_keys($this->container->getParameter('container.modules'));
    sort($container_modules);
    $this->assertSame($module_list, $container_modules);
    $this->assertSame(1, $this->containerBuilds);
    $this->assertTrue(function_exists('system_test_help'));

    $core_extension->clear('module.system_test')->save();
    $this->containerBuilds = 0;
    drupal_flush_all_caches();
    $module_list = ['system'];
    if ($database_module !== 'core') {
      $module_list[] = $database_module;
    }
    sort($module_list);
    $container_modules = array_keys($this->container->getParameter('container.modules'));
    sort($container_modules);
    $this->assertSame($module_list, $container_modules);
    $this->assertSame(1, $this->containerBuilds);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $this->containerBuilds++;
  }

}
