<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin\source;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests discovery of source plugins with annotations.
 *
 * Migrate source plugins use a specific discovery class to accommodate multiple
 * providers. This tests that the backwards compatibility of discovery for
 * plugin classes using annotations still works, even after all core plugins
 * have been converted to attributes.
 *
 * @group migrate
 */
class MigrateSourceDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate'];

  /**
   * @covers \Drupal\migrate\Plugin\MigrateSourcePluginManager::getDefinitions
   */
  public function testGetDefinitions(): void {
    // First, check the expected plugins are provided by migrate only.
    $expected = ['config_entity', 'embedded_data', 'empty'];
    $source_plugins = \Drupal::service('plugin.manager.migrate.source')->getDefinitions();
    ksort($source_plugins);
    $this->assertSame($expected, array_keys($source_plugins));

    // Next, install the file module, which has 4 migrate source plugins, all of
    // which depend on migrate_drupal. Since migrate_drupal is not installed,
    // none of the source plugins from file should be discovered. However, the
    // content_entity source for the file entity type should be discovered.
    $expected = ['config_entity', 'content_entity:file', 'embedded_data', 'empty'];
    $this->enableModules(['file']);
    $source_plugins = \Drupal::service('plugin.manager.migrate.source')->getDefinitions();
    ksort($source_plugins);
    $this->assertSame($expected, array_keys($source_plugins));

    // Install migrate_drupal and now the source plugins from the file modules
    // should be found.
    $expected = [
      'config_entity',
      'd6_file',
      'd6_upload',
      'd6_upload_instance',
      'd7_file',
      'embedded_data',
      'empty',
    ];
    $this->enableModules(['migrate_drupal']);
    $source_plugins = \Drupal::service('plugin.manager.migrate.source')->getDefinitions();
    $this->assertSame(array_diff($expected, array_keys($source_plugins)), []);
  }

  /**
   * @covers \Drupal\migrate\Plugin\MigrateSourcePluginManager::getDefinitions
   */
  public function testAnnotationGetDefinitionsBackwardsCompatibility(): void {
    // First, test attribute-only discovery.
    $expected = ['config_entity', 'embedded_data', 'empty'];
    $source_plugins = \Drupal::service('plugin.manager.migrate.source')->getDefinitions();
    ksort($source_plugins);
    $this->assertSame($expected, array_keys($source_plugins));

    // Next, test discovery of both attributed and annotated plugins. The
    // annotated plugin with multiple providers depends on migrate_drupal and
    // should not be discovered with it uninstalled.
    $expected = ['annotated', 'config_entity', 'embedded_data', 'empty'];
    $this->enableModules(['migrate_source_annotation_bc_test']);
    $source_plugins = \Drupal::service('plugin.manager.migrate.source')->getDefinitions();
    ksort($source_plugins);
    $this->assertSame($expected, array_keys($source_plugins));

    // Install migrate_drupal and now the annotated plugin that depends on it
    // should be discovered.
    $expected = [
      'annotated',
      'annotated_multiple_providers',
      'config_entity',
      'embedded_data',
      'empty',
    ];
    $this->enableModules(['migrate_drupal']);
    $source_plugins = \Drupal::service('plugin.manager.migrate.source')->getDefinitions();
    // Confirming here the that the source plugins that migrate and
    // migrate_source_annotation_bc_test are discovered. There are additional
    // plugins provided by migrate_drupal, but they do not need to be enumerated
    // here.
    $this->assertSame(array_diff($expected, array_keys($source_plugins)), []);
  }

}
