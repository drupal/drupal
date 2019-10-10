<?php

namespace Drupal\Tests\path\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests URL alias migration.
 *
 * @group path
 */
abstract class MigrateUrlAliasTestBase extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'menu_ui',
    'node',
    'path',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installConfig('node');
    $this->installSchema('node', ['node_access']);

    $this->migrateUsers(FALSE);
    $this->migrateContentTypes();
    $this->executeMigrations([
      'language',
      'd7_node',
    ]);
  }

  /**
   * Test the URL alias migration.
   */
  public function testUrlAlias() {
    $alias_storage = $this->container->get('path.alias_storage');

    $path = $alias_storage->load([
      'source' => '/taxonomy/term/4',
      'alias' => '/term33',
      'langcode' => 'und',
    ]);
    $this->assertIdentical('/taxonomy/term/4', $path['source']);
    $this->assertIdentical('/term33', $path['alias']);
    $this->assertIdentical('und', $path['langcode']);

    // Alias with no slash.
    $path = $alias_storage->load(['alias' => '/source-noslash']);
    $this->assertSame('/admin', $path['source']);
    $this->assertSame('und', $path['langcode']);
  }

}
