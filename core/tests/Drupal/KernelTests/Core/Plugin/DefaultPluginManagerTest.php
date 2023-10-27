<?php

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin_test\Plugin\Annotation\PluginExample as AnnotationPluginExample;
use Drupal\plugin_test\Plugin\Attribute\PluginExample as AttributePluginExample;

/**
 * Tests the default plugin manager.
 *
 * @group Plugin
 */
class DefaultPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['plugin_test'];

  /**
   * Tests annotations and attributes on the default plugin manager.
   */
  public function testDefaultPluginManager() {
    $subdir = 'Plugin/plugin_test/custom_annotation';
    $base_directory = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => $base_directory]);
    $module_handler = $this->container->get('module_handler');

    // Annotation only.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_1', $definitions);
    $this->assertArrayHasKey('example_2', $definitions);
    $this->assertArrayNotHasKey('example_3', $definitions);

    // Annotations and attributes together.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_1', $definitions);
    $this->assertArrayHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);

    // Attributes only.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayNotHasKey('example_1', $definitions);
    $this->assertArrayNotHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);
  }

}
