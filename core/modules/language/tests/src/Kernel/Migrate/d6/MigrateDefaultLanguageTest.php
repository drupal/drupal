<?php

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\Tests\language\Kernel\Migrate\MigrateDefaultLanguageTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the default language variable migration.
 *
 * @group migrate_drupal_6
 */
class MigrateDefaultLanguageTest extends MigrateDrupal6TestBase {

  use MigrateDefaultLanguageTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * Tests language_default migration with an existing language.
   */
  public function testMigrationWithExistingLanguage() {
    $this->doTestMigration('fr');
  }

  /**
   * Tests language_default migration with a non-existing language.
   */
  public function testMigrationWithNonExistentLanguage() {
    $this->doTestMigration('tv', FALSE);
  }

  /**
   * Tests language_default migration with unset variable.
   */
  public function testMigrationWithUnsetVariable() {
    $this->doTestMigrationWithUnsetVariable();
  }

}
