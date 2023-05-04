<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the content translation language that is set.
 *
 * @group content_translation
 */
class ContentTranslationLanguageChangeTest extends NodeTestBase {

  use ImageFieldCreationTrait;
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
    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'article');
    $config->setDefaultLangcode(LanguageInterface::LANGCODE_SITE_DEFAULT);
    $config->setLanguageAlterable(TRUE);
    $config->save();

    $content_translation_manager = $this->container->get('content_translation.manager');
    $content_translation_manager->setEnabled('node', 'article', TRUE);
    $content_translation_manager->setBundleTranslationSettings('node', 'article', [
      'untranslatable_fields_hide' => FALSE,
    ]);

    $this->rebuildContainer();

    $this->createImageField('field_image_field', 'article');
  }

  /**
   * Tests that the source language is properly set when changing.
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
    $this->clickLink('Edit', 1);
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
   * Tests that title does not change on ajax call with new language value.
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
    $this->assertSession()->titleEquals('Edit Article english_title | Drupal');
    // Upload and image after changing the node language.
    $images = $this->drupalGetTestFiles('image')[1];
    $edit = [
      'langcode[0][value]' => 'de',
      'files[field_image_field_0]' => $images->uri,
    ];
    $this->submitForm($edit, 'Upload');
    // Test the expected title after triggering an ajax call with a new
    // language selected.
    $this->assertSession()->titleEquals('Edit Article english_title | Drupal');
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
