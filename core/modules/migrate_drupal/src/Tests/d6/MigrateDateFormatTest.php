<?php

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\Core\Database\Database;

/**
 * Upgrade date formats to system.date_format.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateDateFormatTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_date_formats');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6DateFormat.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests the Drupal 6 date formats to Drupal 8 migration.
   */
  public function testDateFormats() {
    $short_date_format = entity_load('date_format', 'short');
    $this->assertEqual('\S\H\O\R\T m/d/Y - H:i', $short_date_format->getPattern());

    $medium_date_format = entity_load('date_format', 'medium');
    $this->assertEqual('\M\E\D\I\U\M D, m/d/Y - H:i', $medium_date_format->getPattern());

    $long_date_format = entity_load('date_format', 'long');
    $this->assertEqual('\L\O\N\G l, F j, Y - H:i', $long_date_format->getPattern());

    // Test that we can re-import using the EntityDateFormat destination.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(array('value' => serialize('\S\H\O\R\T d/m/Y - H:i')))
      ->condition('name', 'date_format_short')
      ->execute();
    db_truncate(entity_load('migration', 'd6_date_formats')->getIdMap()->mapTableName())->execute();
    $migration = entity_load_unchanged('migration', 'd6_date_formats');
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $short_date_format = entity_load('date_format', 'short');
    $this->assertEqual('\S\H\O\R\T d/m/Y - H:i', $short_date_format->getPattern());

  }

}
