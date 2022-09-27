<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that contextual links are available for content translation.
 *
 * @group content_translation
 */
class ContentTranslationContextualLinksTest extends BrowserTestBase {

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The content type being tested.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * The 'translator' user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  /**
   * The enabled languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['content_translation', 'contextual', 'node'];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set up an additional language.
    $this->langcodes = [\Drupal::languageManager()->getDefaultLanguage()->getId(), 'es'];
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a content type.
    $this->bundle = $this->randomMachineName();
    $this->contentType = $this->drupalCreateContentType(['type' => $this->bundle]);

    // Add a field to the content type. The field is not yet translatable.
    FieldStorageConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_text',
      'bundle' => $this->bundle,
      'label' => 'Test text-field',
    ])->save();
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', $this->bundle)
      ->setComponent('field_test_text', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->save();

    // Create a translator user.
    $permissions = [
      'access contextual links',
      'administer nodes',
      "edit any $this->bundle content",
      'translate any entity',
    ];
    $this->translator = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests that a contextual link is available for translating a node.
   */
  public function testContentTranslationContextualLinks() {
    // Create a node.
    $title = $this->randomString();
    $this->drupalCreateNode(['type' => $this->bundle, 'title' => $title, 'langcode' => 'en']);
    $node = $this->drupalGetNodeByTitle($title);

    // Use a UI form submission to make the node type and field translatable.
    // This tests that caches are properly invalidated.
    $this->drupalLogin($this->rootUser);
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][' . $this->bundle . '][settings][language][language_alterable]' => TRUE,
      'settings[node][' . $this->bundle . '][translatable]' => TRUE,
      'settings[node][' . $this->bundle . '][fields][field_test_text]' => TRUE,
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogout();

    // Check that the link leads to the translate page.
    $this->drupalLogin($this->translator);
    $translate_link = 'node/' . $node->id() . '/translations';
    $this->drupalGet($translate_link);
    $this->assertSession()->pageTextContains('Translations of ' . $node->label());
  }

}
