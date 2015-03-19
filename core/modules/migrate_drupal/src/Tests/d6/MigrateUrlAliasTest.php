<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateUrlAliasTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\Core\Database\Database;

/**
 * Url alias migration.
 *
 * @group migrate_drupal
 */
class MigrateUrlAliasTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_url_alias');
    $dumps = array(
      $this->getDumpDirectory() . '/UrlAlias.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the url alias migration.
   */
  public function testUrlAlias() {
    $migration = entity_load('migration', 'd6_url_alias');
    // Test that the field exists.
    $conditions = array(
      'source' => 'node/1',
      'alias' => 'alias-one',
      'langcode' => 'en',
    );
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertNotNull($path, "Path alias for node/1 successfully loaded.");
    $this->assertIdentical($migration->getIdMap()->lookupDestinationID(array($path['pid'])), array('1'), "Test IdMap");
    $conditions = array(
      'source' => 'node/2',
      'alias' => 'alias-two',
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

    db_update($migration->getIdMap()->mapTableName())
      ->fields(array('source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE))
      ->execute();
    $migration = entity_load_unchanged('migration', 'd6_url_alias');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $path = \Drupal::service('path.alias_storage')->load(array('pid' => $path['pid']));
    $this->assertIdentical('new-url-alias', $path['alias']);
  }

}
