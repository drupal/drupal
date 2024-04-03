<?php

namespace Drupal\Tests\migrate\Kernel\Plugin\source;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests discovery of source plugins with annotations.
 *
 * Migrate source plugins use a specific discovery class to accommodate multiple
 * providers. This is a backwards compatibility test that discovery for plugin
 * classes that have annotations still works even after all core plugins have
 * been converted to attributes.
 *
 * @group migrate
 */
class MigrateSourceAnnotationDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate'];

  /**
   * @covers \Drupal\migrate\Plugin\MigrateSourcePluginManager::getDefinitions
   */
  public function testGetDefinitions(): void {
    // First, test attribute-only discovery.
    $expected = ['embedded_data', 'empty'];
    $source_plugins = $this->container->get('plugin.manager.migrate.source')->getDefinitions();
    ksort($source_plugins);
    $this->assertSame($expected, array_keys($source_plugins));

    // Next, test discovery of both attributed and annotated plugins. The
    // annotated plugin with multiple providers depends on migrate_drupal and
    // should not be discovered with it uninstalled.
    $expected = ['annotated', 'embedded_data', 'empty'];
    $this->enableModules(['migrate_source_annotation_bc_test']);
    $source_plugins = $this->container->get('plugin.manager.migrate.source')->getDefinitions();
    ksort($source_plugins);
    $this->assertSame($expected, array_keys($source_plugins));

    // Install migrate_drupal and now the annotated plugin that depends on it
    // should be discovered.
    $expected = [
      'annotated',
      'annotated_multiple_providers',
      'embedded_data',
      'empty',
    ];
    $this->enableModules(['migrate_drupal']);
    $source_plugins = $this->container->get('plugin.manager.migrate.source')->getDefinitions();
    // Confirming here the that the source plugins that migrate and
    // migrate_source_annotation_bc_test are discovered. There are additional
    // plugins provided by migrate_drupal, but they do not need to be enumerated
    // here.
    $this->assertSame(array_diff($expected, array_keys($source_plugins)), []);
  }

}
