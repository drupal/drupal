<?php

namespace Drupal\Tests\path\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests URL alias migration.
 *
 * @group path
 */
class MigrateUrlAliasTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['path'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_url_alias');
  }

  /**
   * Test the URL alias migration.
   */
  public function testUrlAlias() {
    $path = \Drupal::service('path.alias_storage')->load([
      'source' => '/taxonomy/term/4',
      'alias' => '/term33',
      'langcode' => 'und',
    ]);
    $this->assertIdentical('/taxonomy/term/4', $path['source']);
    $this->assertIdentical('/term33', $path['alias']);
    $this->assertIdentical('und', $path['langcode']);
  }

}
