<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin_test\Plugin\Annotation\PluginExample as AnnotationPluginExample;
use Drupal\plugin_test\Plugin\Attribute\PluginExample as AttributePluginExample;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the default plugin manager.
 *
 * @group Plugin
 * @group legacy
 */
class DefaultPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['plugin_test'];

  /**
   * Tests annotations and attributes on the default plugin manager.
   */
  public function testDefaultPluginManager(): void {
    $subdir = 'Plugin/plugin_test/custom_annotation';
    $base_directory = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => $base_directory]);
    $module_handler = $this->container->get('module_handler');

    // Ensure broken files exist as expected.
    try {
      $e = NULL;
      new \ReflectionClass('\Drupal\plugin_test\Plugin\plugin_test\custom_annotation\ExtendingNonInstalledClass');
    }
    catch (\Throwable $e) {
    } finally {
      $this->assertInstanceOf(\Throwable::class, $e);
      $this->assertSame('Class "Drupal\non_installed_module\NonExisting" not found', $e->getMessage());
    }
    // Ensure there is a class with the expected name. We cannot reflect on this
    // as it triggers a fatal error.
    $this->assertFileExists($base_directory . '/' . $subdir . '/UsingNonInstalledTraitClass.php');
    $this->expectDeprecation('Using @PluginExample annotation for plugin with ID example_1 is deprecated and is removed from drupal:13.0.0. Use a Drupal\plugin_test\Plugin\Attribute\PluginExample attribute instead. See https://www.drupal.org/node/3395575');

    // Annotation only.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_1', $definitions);
    $this->assertArrayHasKey('example_2', $definitions);
    $this->assertArrayNotHasKey('example_3', $definitions);
    $this->assertArrayNotHasKey('example_4', $definitions);
    $this->assertArrayNotHasKey('example_5', $definitions);
    // For the plugin class with both an annotation and attribute, the
    // annotation should be picked up.
    $this->assertArrayHasKey('example_annotation_not_attribute', $definitions);
    $this->assertArrayNotHasKey('example_attribute_not_annotation', $definitions);

    // Annotations and attributes together.
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class, AnnotationPluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayHasKey('example_1', $definitions);
    $this->assertArrayHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);
    $this->assertArrayHasKey('example_4', $definitions);
    $this->assertArrayHasKey('example_5', $definitions);
    // For the plugin class with both an annotation and attribute, the
    // attribute should be picked up.
    $this->assertArrayHasKey('example_attribute_not_annotation', $definitions);
    $this->assertArrayNotHasKey('example_annotation_not_attribute', $definitions);

    // Attributes only.
    // \Drupal\Component\Plugin\Discovery\AttributeClassDiscovery does not
    // support parsing classes that cannot be reflected. Therefore, we use VFS
    // to create a directory remove plugin_test's plugins and remove the broken
    // plugins.
    vfsStream::setup('plugin_test');
    $dir = vfsStream::create(['src' => ['Plugin' => ['plugin_test' => ['custom_annotation' => []]]]]);
    $plugin_directory = $dir->getChild('src/' . $subdir);
    vfsStream::copyFromFileSystem($base_directory . '/' . $subdir, $plugin_directory);
    $plugin_directory->removeChild('ExtendingNonInstalledClass.php');
    $plugin_directory->removeChild('UsingNonInstalledTraitClass.php');

    $namespaces = new \ArrayObject(['Drupal\plugin_test' => vfsStream::url('plugin_test/src')]);
    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AttributePluginExample::class);
    $definitions = $manager->getDefinitions();
    $this->assertArrayNotHasKey('example_1', $definitions);
    $this->assertArrayNotHasKey('example_2', $definitions);
    $this->assertArrayHasKey('example_3', $definitions);
    $this->assertArrayHasKey('example_4', $definitions);
    $this->assertArrayHasKey('example_5', $definitions);
    $this->assertArrayNotHasKey('extending_non_installed_class', $definitions);
    $this->assertArrayNotHasKey('using_non_installed_trait', $definitions);
    // For the plugin class with both an annotation and attribute, the
    // attribute should be picked up.
    $this->assertArrayHasKey('example_attribute_not_annotation', $definitions);
    $this->assertArrayNotHasKey('example_annotation_not_attribute', $definitions);
  }

  /**
   * Tests the deprecation message for using only annotations.
   */
  public function testDefaultPluginManagerAnnotationsOnly(): void {
    $subdir = 'Plugin/plugin_test/custom_annotation';
    $base_directory = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => $base_directory]);
    $module_handler = $this->container->get('module_handler');

    $this->expectDeprecation('Not supporting attribute discovery in Drupal\Core\Plugin\DefaultPluginManager is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Provide an Attribute class and an Annotation class for BC. See https://www.drupal.org/node/3395582');

    $manager = new DefaultPluginManager($subdir, $namespaces, $module_handler, NULL, AnnotationPluginExample::class);
    $manager->getDefinitions();
  }

}
