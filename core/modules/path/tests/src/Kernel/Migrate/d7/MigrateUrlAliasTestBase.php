<?php

namespace Drupal\Tests\path\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests URL alias migration.
 *
 * @group path
 */
abstract class MigrateUrlAliasTestBase extends MigrateDrupal7TestBase {

  use PathAliasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'menu_ui',
    'node',
    'path',
    'path_alias',
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
    $path_alias = $this->loadPathAliasByConditions([
      'path' => '/taxonomy/term/4',
      'alias' => '/term33',
      'langcode' => 'und',
    ]);
    $this->assertSame('/taxonomy/term/4', $path_alias->getPath());
    $this->assertSame('/term33', $path_alias->getAlias());
    $this->assertSame('und', $path_alias->language()->getId());

    // Alias with no slash.
    $path_alias = $this->loadPathAliasByConditions(['alias' => '/source-noslash']);
    $this->assertSame('/admin', $path_alias->getPath());
    $this->assertSame('und', $path_alias->language()->getId());
  }

}
