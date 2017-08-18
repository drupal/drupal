<?php

namespace Drupal\config_translation\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Translate settings and entities to various languages.
 *
 * @group config_translation
 */
class ConfigTranslationCacheTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'config_translation',
    'config_translation_test',
    'contact',
    'contact_test',
    'contextual',
    'entity_test',
    'field_test',
    'field_ui',
    'filter',
    'filter_test',
    'node',
    'views',
    'views_ui',
  ];

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $langcodes = ['fr', 'ta'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Translator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translatorUser;

  /**
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  protected function setUp() {
    parent::setUp();
    $translator_permissions = [
      'translate configuration',
    ];

    /** @var \Drupal\filter\FilterFormatInterface $filter_test_format */
    $filter_test_format = FilterFormat::load('filter_test');
    /** @var \Drupal\filter\FilterFormatInterface $filtered_html_format */
    $filtered_html_format = FilterFormat::load('filtered_html');
    /** @var \Drupal\filter\FilterFormatInterface $full_html_format */
    $full_html_format = FilterFormat::load('full_html');

    $admin_permissions = array_merge($translator_permissions, [
      'administer languages',
      'administer site configuration',
      'link to any page',
      'administer contact forms',
      'administer filters',
      $filtered_html_format->getPermissionName(),
      $full_html_format->getPermissionName(),
      $filter_test_format->getPermissionName(),
      'access site-wide contact form',
      'access contextual links',
      'administer account settings',
      'administer themes',
      'bypass node access',
      'administer content types',
      'translate interface',
      'administer entity_test fields',
    ]);
    // Create and login user.
    $this->translatorUser = $this->drupalCreateUser($translator_permissions);
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the translation of field and field storage configuration.
   */
  public function testFieldConfigTranslation() {
    // Add a test field which has a translatable field setting and a
    // translatable field storage setting.
    $field_name = strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);

    $translatable_storage_setting = $this->randomString();
    $field_storage->setSetting('translatable_storage_setting', $translatable_storage_setting);
    $field_storage->save();

    $bundle = strtolower($this->randomMachineName());
    entity_test_create_bundle($bundle);
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'bundle' => $bundle,
    ]);

    $translatable_field_setting = $this->randomString();
    $field->setSetting('translatable_field_setting', $translatable_field_setting);
    $field->save();

    $this->drupalLogin($this->translatorUser);

    $this->drupalGet("/entity_test/structure/$bundle/fields/entity_test.$bundle.$field_name/translate");
    $this->clickLink('Add');

    $this->assertText('Translatable field setting');
    $this->assertEscaped($translatable_field_setting);
    $this->assertText('Translatable storage setting');
    $this->assertEscaped($translatable_storage_setting);

    // Add translation for label.
    $field_label_fr = $this->randomString();
    $edit = [
      "translation[config_names][field.field.entity_test.$bundle.$field_name][label]" => $field_label_fr,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save translation');
    $this->drupalLogout();

    // Check if the translated label appears.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("/fr/entity_test/structure/$bundle/fields");
    $this->assertEscaped($field_label_fr);

    // Clear cache on French version and check for translated label.
    $this->drupalPostForm('/fr/admin/config/development/performance', [], 'Clear all caches');
    $this->drupalGet("/fr/entity_test/structure/$bundle/fields");
    // Check if the translation is still there.
    $this->assertEscaped($field_label_fr);

    // Clear cache on default version and check for translated label.
    $this->drupalPostForm('/admin/config/development/performance', [], 'Clear all caches');
    $this->drupalGet("/fr/entity_test/structure/$bundle/fields");
    // Check if the translation is still there.
    $this->assertEscaped($field_label_fr);
  }

}
