<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests multilingual support for fields.
 *
 * @group node
 */
class NodeFieldMultilingualTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Setup users.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'administer content types',
      'access administration pages',
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($admin_user);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Set "Basic page" content type to use multilingual support.
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("The content type Basic page has been updated.");

    // Make node body translatable.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();
  }

  /**
   * Tests whether field languages are correctly set through the node form.
   */
  public function testMultilingualNodeForm(): void {
    // Create "Basic page" content.
    $langcode = language_get_default_langcode('node', 'page');
    $title_key = 'title[0][value]';
    $title_value = $this->randomMachineName(8);
    $body_key = 'body[0][value]';
    $body_value = $this->randomMachineName(16);

    // Create node to edit.
    $edit = [];
    $edit[$title_key] = $title_value;
    $edit[$body_key] = $body_value;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->assertNotEmpty($node, 'Node found in database.');
    $this->assertSame($langcode, $node->language()->getId());
    $this->assertSame($body_value, $node->body->value);

    // Change node language.
    $langcode = 'it';
    $this->drupalGet("node/{$node->id()}/edit");
    $edit = [
      $title_key => $this->randomMachineName(8),
      'langcode[0][value]' => $langcode,
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit[$title_key], TRUE);
    $this->assertNotEmpty($node, 'Node found in database.');
    $this->assertSame($langcode, $node->language()->getId());
    $this->assertSame($body_value, $node->body->value);

    // Enable content language URL detection.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_CONTENT, [LanguageNegotiationUrl::METHOD_ID => 0]);

    // Test multilingual field language fallback logic.
    $this->drupalGet("it/node/{$node->id()}");
    // Verify that body is correctly displayed using Italian as requested
    // language.
    $this->assertSession()->pageTextContains($body_value);

    $this->drupalGet("node/{$node->id()}");
    // Verify that body is correctly displayed using English as requested
    // language.
    $this->assertSession()->pageTextContains($body_value);
  }

  /**
   * Tests multilingual field display settings.
   */
  public function testMultilingualDisplaySettings(): void {
    // Create "Basic page" content.
    $title_key = 'title[0][value]';
    $title_value = $this->randomMachineName(8);
    $body_key = 'body[0][value]';
    $body_value = $this->randomMachineName(16);

    // Create node to edit.
    $edit = [];
    $edit[$title_key] = $title_value;
    $edit[$body_key] = $body_value;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->assertNotEmpty($node, 'Node found in database.');

    // Check if node body is showed.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementTextEquals('xpath', "//article/div//p", $node->body->value);
  }

}
