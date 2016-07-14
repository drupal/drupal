<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the migration manager plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\MigratePluginManager
 * @group migrate
 */
class MigrationPluginListTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate',
    // Test with all modules containing Drupal migrations.
    'action',
    'aggregator',
    'ban',
    'block',
    'block_content',
    'book',
    'comment',
    'contact',
    'dblog',
    'field',
    'file',
    'filter',
    'forum',
    'image',
    'language',
    'locale',
    'menu_link_content',
    'menu_ui',
    'node',
    'path',
    'search',
    'shortcut',
    'simpletest',
    'statistics',
    'syslog',
    'system',
    'taxonomy',
    'text',
    'tracker',
    'update',
    'user',
  ];

  /**
   * Tests MigratePluginManager::getDefinitions()
   *
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    // Make sure retrieving all the core migration plugins does not throw any
    // errors.
    $migration_plugins = $this->container->get('plugin.manager.migration')->getDefinitions();
  }

}
