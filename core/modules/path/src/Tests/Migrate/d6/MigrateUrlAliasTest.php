<?php

/**
 * @file
 * Contains \Drupal\path\Tests\Migrate\d6\MigrateUrlAliasTest.
 */

namespace Drupal\path\Tests\Migrate\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * URL alias migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUrlAliasTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('path');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['url_alias']);
    $this->executeMigration('d6_url_alias');
  }

  /**
   * Assert a path.
   *
   * @param string pid
   *   The path id.
   * @param array $conditions
   *   The path conditions.
   * @param array $path
   *   The path.
   */
  private function assertPath($pid, $conditions, $path) {
    $this->assertTrue($path, "Path alias for " . $conditions['source'] . " successfully loaded.");
    $this->assertIdentical($conditions['alias'], $path['alias']);
    $this->assertIdentical($conditions['langcode'], $path['langcode']);
    $this->assertIdentical($conditions['source'], $path['source']);
  }

  /**
   * Test the url alias migration.
   */
  public function testUrlAlias() {
    $id_map = Migration::load('d6_url_alias')->getIdMap();
    $conditions = array(
      'source' => '/node/1',
      'alias' => '/alias-one',
      'langcode' => 'af',
    );
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertPath('1', $conditions, $path);
    $this->assertIdentical($id_map->lookupDestinationID(array($path['pid'])), array('1'), "Test IdMap");

    $conditions = array(
      'source' => '/node/2',
      'alias' => '/alias-two',
      'langcode' => 'en',
    );
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertPath('2', $conditions, $path);

    // Test that we can re-import using the UrlAlias destination.
    Database::getConnection('default', 'migrate')
      ->update('url_alias')
      ->fields(array('dst' => 'new-url-alias'))
      ->condition('src', 'node/2')
      ->execute();

    \Drupal::database()
      ->update($id_map->mapTableName())
      ->fields(array('source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE))
      ->execute();
    $migration = \Drupal::entityManager()
      ->getStorage('migration')
      ->loadUnchanged('d6_url_alias');
    $this->executeMigration($migration);

    $path = \Drupal::service('path.alias_storage')->load(array('pid' => $path['pid']));
    $conditions['alias'] = '/new-url-alias';
    $this->assertPath('2', $conditions, $path);

    $conditions = array(
      'source' => '/node/3',
      'alias' => '/alias-three',
      'langcode' => 'und',
    );
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertPath('3', $conditions, $path);
  }

}
