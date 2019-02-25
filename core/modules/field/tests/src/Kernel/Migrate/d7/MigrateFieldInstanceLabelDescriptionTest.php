<?php

namespace Drupal\Tests\field\Kernel\Migrate\d7;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate\Kernel\MigrateDumpAlterInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration field label and description i18n translations.
 *
 * @group migrate_drupal_7
 * @group legacy
 */
class MigrateFieldInstanceLabelDescriptionTest extends MigrateDrupal7TestBase implements MigrateDumpAlterInterface {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'config_translation',
    'datetime',
    'field',
    'file',
    'image',
    'language',
    'link',
    'locale',
    'menu_ui',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
    'node',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(static::$modules);

    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_widget_settings',
      'language',
      'd7_field_instance_label_description_translation',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function migrateDumpAlter(KernelTestBase $test) {
    $db = Database::getConnection('default', 'migrate');
    // Alter the database to test the migration is successful when a translated
    // field is deleted but the translation data for that field remains in both
    // the i18n_strings and locales_target tables.
    $db->delete('field_config_instance')
      ->condition('field_name', 'field_image')
      ->condition('bundle', 'article')
      ->execute();
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFieldInstanceLabelDescriptionTranslationMigration() {
    $language_manager = $this->container->get('language_manager');

    // Check that the deleted field with translations was skipped.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.article.field_image');
    $this->assertNull($config_translation->get('label'));
    $this->assertNull($config_translation->get('description'));

    // Tests fields on 'test_content_type' node type.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.test_content_type.field_email');
    $this->assertNull($config_translation->get('label'));
    $this->assertSame("fr - The email help text.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.field.node.test_content_type.field_email');
    $this->assertSame("is - Email", $config_translation->get('label'));
    $this->assertSame("is - The email help text.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.field.node.test_content_type.field_boolean');
    $this->assertSame("is - Some helpful text.", $config_translation->get('description'));

    // Tests fields on 'article' node type.
    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.article.body');
    $this->assertSame("fr - Body", $config_translation->get('label'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.node.article.field_link');
    $this->assertSame("fr - Link", $config_translation->get('label'));

    // Tests fields on 'test_vocabulary' vocabulary type.
    $config_translation = $language_manager->getLanguageConfigOverride('is', 'field.field.taxonomy_term.test_vocabulary.field_term_reference');
    $this->assertSame("is - Term Reference", $config_translation->get('label'));
  }

}
