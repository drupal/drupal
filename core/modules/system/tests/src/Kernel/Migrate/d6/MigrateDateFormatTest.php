<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade date formats to core.date_format.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateDateFormatTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_date_formats');
  }

  /**
   * Tests the Drupal 6 date formats to Drupal 8 migration.
   */
  public function testDateFormats(): void {
    $short_date_format = DateFormat::load('short');
    $this->assertSame('\S\H\O\R\T m/d/Y - H:i', $short_date_format->getPattern());

    $medium_date_format = DateFormat::load('medium');
    $this->assertSame('\M\E\D\I\U\M D, m/d/Y - H:i', $medium_date_format->getPattern());

    $long_date_format = DateFormat::load('long');
    $this->assertSame('\L\O\N\G l, F j, Y - H:i', $long_date_format->getPattern());

    // Test that we can re-import using the EntityDateFormat destination.
    Database::getConnection('default', 'migrate')
      ->update('variable')
      ->fields(['value' => serialize('\S\H\O\R\T d/m/Y - H:i')])
      ->condition('name', 'date_format_short')
      ->execute();

    $migration = $this->getMigration('d6_date_formats');
    \Drupal::database()
      ->truncate($migration->getIdMap()->mapTableName())
      ->execute();

    $this->executeMigration($migration);

    $short_date_format = DateFormat::load('short');
    $this->assertSame('\S\H\O\R\T d/m/Y - H:i', $short_date_format->getPattern());

  }

}
