<?php

namespace Drupal\Tests\language\Kernel\Migrate\d7;

use Drupal\Tests\language\Kernel\Migrate\MigrateDefaultLanguageTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests the default language variable migration.
 *
 * @group migrate_drupal_7
 */
class MigrateDefaultLanguageTest extends MigrateDrupal7TestBase {

  use MigrateDefaultLanguageTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * Tests language_default migration with a non-existing language.
   */
  public function testMigrationWithExistingLanguage() {
    $this->doTestMigration('is');
  }

  /**
   * Tests language_default migration with a non-existing language.
   */
  public function testMigrationWithNonExistentLanguage() {
    $this->doTestMigration('tv', FALSE);
  }

}
