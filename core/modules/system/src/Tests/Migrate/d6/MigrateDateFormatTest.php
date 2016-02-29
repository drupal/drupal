<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateDateFormatTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\migrate\Entity\Migration;
use Drupal\Core\Database\Database;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade date formats to core.date_format.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateDateFormatTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_date_formats');
  }

  /**
   * Tests the Drupal 6 date formats to Drupal 8 migration.
   */
  public function testDateFormats() {
    $short_date_format = DateFormat::load('short');
    $this->assertIdentical('\S\H\O\R\T m/d/Y - H:i', $short_date_format->getPattern());

    $medium_date_format = DateFormat::load('medium');
    $this->assertIdentical('\M\E\D\I\U\M D, m/d/Y - H:i', $medium_date_format->getPattern());

    $long_date_format = DateFormat::load('long');
    $this->assertIdentical('\L\O\N\G l, F j, Y - H:i', $long_date_format->getPattern());

    // Test that we can re-import using the EntityDateFormat destination.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(array('value' => serialize('\S\H\O\R\T d/m/Y - H:i')))
      ->condition('name', 'date_format_short')
      ->execute();

    \Drupal::database()
      ->truncate(Migration::load('d6_date_formats')->getIdMap()->mapTableName())
      ->execute();

    $migration = \Drupal::entityManager()
      ->getStorage('migration')
      ->loadUnchanged('d6_date_formats');
    $this->executeMigration($migration);

    $short_date_format = DateFormat::load('short');
    $this->assertIdentical('\S\H\O\R\T d/m/Y - H:i', $short_date_format->getPattern());

  }

}
