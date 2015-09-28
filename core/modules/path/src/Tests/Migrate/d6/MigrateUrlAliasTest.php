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
   * Test the url alias migration.
   */
  public function testUrlAlias() {
    $id_map = Migration::load('d6_url_alias')->getIdMap();
    // Test that the field exists.
    $conditions = array(
      'source' => '/node/1',
      'alias' => '/alias-one',
      'langcode' => 'en',
    );
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertNotNull($path, "Path alias for node/1 successfully loaded.");
    $this->assertIdentical($id_map->lookupDestinationID(array($path['pid'])), array('1'), "Test IdMap");
    $conditions = array(
      'source' => '/node/2',
      'alias' => '/alias-two',
      'langcode' => 'en',
    );
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertNotNull($path, "Path alias for node/2 successfully loaded.");

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
    $this->assertIdentical('/new-url-alias', $path['alias']);
  }

}
