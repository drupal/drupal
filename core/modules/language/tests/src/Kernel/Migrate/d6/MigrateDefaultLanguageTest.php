<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Migrate\d6;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the default language variable migration.
 *
 * @group migrate_drupal_6
 */
class MigrateDefaultLanguageTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * Tests language_default migration with an existing language.
   */
  public function testMigrationWithExistingLanguage(): void {
    $this->setDefaultLanguage('fr');
    $this->startCollectingMessages();
    $this->executeMigrations(['language', 'default_language']);

    // Tests the language is loaded and is the default language.
    $default_language = ConfigurableLanguage::load('fr');
    $this->assertNotNull($default_language);
    $this->assertSame('fr', $this->config('system.site')->get('default_langcode'));
  }

  /**
   * Tests language_default migration with a non-existing language.
   */
  public function testMigrationWithNonExistentLanguage(): void {
    $this->setDefaultLanguage('tv');
    $this->startCollectingMessages();
    $this->executeMigrations(['language', 'default_language']);

    // Tests the migration log contains an error message.
    $messages = $this->migration->getIdMap()->getMessages();
    $count = 0;
    foreach ($messages as $message) {
      $count++;
      $this->assertSame("The language 'tv' does not exist on this site.", $message->message);
      $this->assertSame(MigrationInterface::MESSAGE_ERROR, (int) $message->level);
    }
    $this->assertSame(1, $count);
  }

  /**
   * Tests language_default migration with unset default language variable.
   */
  public function testMigrationWithUnsetVariable(): void {
    // Delete the language_default variable.
    $this->sourceDatabase->delete('variable')
      ->condition('name', 'language_default')
      ->execute();
    $this->startCollectingMessages();
    $this->executeMigrations(['language', 'default_language']);

    $messages = $this->migration->getIdMap()->getMessages()->fetchAll();
    // Make sure there's no migration exceptions.
    $this->assertEmpty($messages);
    // Make sure the default langcode is 'en', as it was the default on D6 & D7.
    $this->assertSame('en', $this->config('system.site')->get('default_langcode'));
  }

  /**
   * Helper method to test the migration.
   *
   * @param string $langcode
   *   The langcode of the default language.
   */
  protected function setDefaultLanguage($langcode): void {
    // The default language of the test fixture is English. Change it to
    // something else before migrating, to be sure that the source site
    // default language is migrated.
    $value = 'O:8:"stdClass":11:{s:8:"language";s:2:"' . $langcode . '";s:4:"name";s:6:"French";s:6:"native";s:6:"French";s:9:"direction";s:1:"0";s:7:"enabled";i:1;s:7:"plurals";s:1:"0";s:7:"formula";s:0:"";s:6:"domain";s:0:"";s:6:"prefix";s:0:"";s:6:"weight";s:1:"0";s:10:"javascript";s:0:"";}';
    $this->sourceDatabase->update('variable')
      ->fields(['value' => $value])
      ->condition('name', 'language_default')
      ->execute();
  }

}
