<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the content translation language that is set.
 *
 * @group content_translation
 */
class ContentTranslationLanguageChangeTest extends NodeTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'content_translation_test',
    'node',
    'block',
    'field_ui',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $langcodes = ['de', 'fr'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    $this->drupalPlaceBlock('local_tasks_block');
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($user);

    // Enable translation for article.
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');

    // Add an image field.
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $edit = [
      'new_storage_type' => 'image',
      'field_name' => 'image_field',
      'label' => 'image_field',
    ];
    $this->submitForm($edit, 'Save and continue');
    $this->submitForm([], 'Save field settings');
    $this->submitForm([], 'Save settings');
  }

  /**
   * Test that the source language is properly set when changing.
   */
  public function testLanguageChange() {
    // Create a node in English.
    $this->drupalGet('node/add/article');
    $edit = [
      'title[0][value]' => 'english_title',
    ];
    $this->submitForm($edit, 'Save');

    // Create a translation in French.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $this->submitForm([], 'Save (this translation)');
    $this->clickLink('Translate');

    // Edit English translation.
    $this->clickLink('Edit');
    // Upload and image after changing the node language.
    $images = $this->drupalGetTestFiles('image')[1];
    $edit = [
      'langcode[0][value]' => 'de',
      'files[field_image_field_0]' => $images->uri,
    ];
    $this->submitForm($edit, 'Upload');
    $this->submitForm(['field_image_field[0][alt]' => 'alternative_text'], 'Save (this translation)');

    // Check that the translation languages are correct.
    $node = $this->getNodeByTitle('english_title');
    $translation_languages = $node->getTranslationLanguages();
    $this->assertArrayHasKey('fr', $translation_languages);
    $this->assertArrayHasKey('de', $translation_languages);
  }

  /**
   * Test that title does not change on ajax call with new language value.
   */
  public function testTitleDoesNotChangesOnChangingLanguageWidgetAndTriggeringAjaxCall() {
    // Create a node in English.
    $this->drupalGet('node/add/article', ['query' => ['test_field_only_en_fr' => 1]]);
    $edit = [
      'title[0][value]' => 'english_title',
      'test_field_only_en_fr' => 'node created',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertEquals('node created', \Drupal::state()->get('test_field_only_en_fr'));

    // Create a translation in French.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $this->submitForm([], 'Save (this translation)');
    $this->clickLink('Translate');

    // Edit English translation.
    $node = $this->getNodeByTitle('english_title');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Test the expected title when loading the form.
    $this->assertRaw('<title>Edit Article english_title | Drupal</title>');
    // Upload and image after changing the node language.
    $images = $this->drupalGetTestFiles('image')[1];
    $edit = [
      'langcode[0][value]' => 'de',
      'files[field_image_field_0]' => $images->uri,
    ];
    $this->submitForm($edit, 'Upload');
    // Test the expected title after triggering an ajax call with a new
    // language selected.
    $this->assertRaw('<title>Edit Article english_title | Drupal</title>');
    $edit = [
      'langcode[0][value]' => 'en',
      'field_image_field[0][alt]' => 'alternative_text',
    ];
    $this->submitForm($edit, 'Save (this translation)');

    // Check that the translation languages are correct.
    $node = $this->getNodeByTitle('english_title');
    $translation_languages = $node->getTranslationLanguages();
    $this->assertArrayHasKey('fr', $translation_languages);
    $this->assertArrayNotHasKey('de', $translation_languages);
  }

}
