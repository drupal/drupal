<?php

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Database\Database;
use Drupal\Tests\migrate\Kernel\MigrateDumpAlterInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of field label and description translations.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldInstanceLabelDescriptionTest extends MigrateDrupal6TestBase implements MigrateDumpAlterInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'locale',
    'language',
    'menu_ui',
    'node',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateFields();

    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('system', ['sequences']);
    $this->executeMigration('language');
    $this->executeMigration('d6_field_instance_label_description_translation');
  }

  /**
   * {@inheritdoc}
   */
  public static function migrateDumpAlter(KernelTestBase $test) {
    $db = Database::getConnection('default', 'migrate');
    // Alter the database to test the migration is successful when a translated
    // field is deleted but the translation data for that field remains in both
    // the i18n_strings and locales_target tables.
    $db->delete('content_node_field_instance')
      ->condition('field_name', 'field_test')
      ->condition('type_name', 'story')
      ->execute();
  }

  /**
   * Tests migration of field label and description translations.
   */
  public function testFieldInstanceLabelDescriptionTranslationMigration() {
    $language_manager = $this->container->get('language_manager');

    // Tests fields on 'story' node type.
    // Check that the deleted field with translations was skipped.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test');
    $this->assertNull($config_translation->get('label'));
    $this->assertNull($config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_two');
    $this->assertSame("fr - Integer Field", $config_translation->get('label'));
    $this->assertSame("fr - An example integer field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_four');
    $this->assertSame("fr - Float Field", $config_translation->get('label'));
    $this->assertSame("fr - An example float field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_email');
    $this->assertSame("fr - Email Field", $config_translation->get('label'));
    $this->assertSame("fr - An example email field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_imagefield');
    $this->assertSame("fr - Image Field", $config_translation->get('label'));
    $this->assertSame("fr - An example image field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('zu', 'field.field.node.story.field_test_imagefield');
    $this->assertSame("zu - Image Field", $config_translation->get('label'));
    $this->assertSame("zu - An example image field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_filefield');
    $this->assertSame("fr - File Field", $config_translation->get('label'));
    $this->assertSame("fr - An example file field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_link');
    $this->assertSame("fr - Link Field", $config_translation->get('label'));
    $this->assertSame("fr - An example link field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_date');
    $this->assertSame("fr - Date Field", $config_translation->get('label'));
    $this->assertSame("fr - An example date field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_datetime');
    $this->assertSame("fr - Datetime Field", $config_translation->get('label'));
    $this->assertSame("fr - An example datetime field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_datestamp');
    $this->assertSame("fr - Date Stamp Field", $config_translation->get('label'));
    $this->assertSame("fr - An example date stamp field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_date');
    $this->assertSame("fr - Date Field", $config_translation->get('label'));
    $this->assertSame("fr - An example date field.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.story.field_test_phone');
    $this->assertSame("fr - Phone Field", $config_translation->get('label'));
    $this->assertSame("fr - An example phone field.", $config_translation->get('description'));

    // Tests fields on 'test_page' node type.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.test_page.field_test');
    $this->assertSame("Champ de texte", $config_translation->get('label'));
    $this->assertSame("fr - An example text field.", $config_translation->get('description'));

    // Tests fields on 'test_planet' node type.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.test_planet.field_multivalue');
    $this->assertSame("fr - Decimal Field", $config_translation->get('label'));
    $this->assertSame("Un exemple plusieurs valeurs champ décimal.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.test_planet.field_test_text_single_checkbox');
    $this->assertNull($config_translation->get('label'));
    $this->assertSame('fr - An example text field using a single on/off checkbox.', $config_translation->get('description'));
  }

}
