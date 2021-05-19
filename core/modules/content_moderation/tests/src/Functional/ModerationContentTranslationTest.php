<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test content_moderation functionality with content_translation.
 *
 * @group content_moderation
 */
class ModerationContentTranslationTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'locale',
    'content_translation',
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
    $this->drupalLogin($this->rootUser);
    // Create an Article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article'])->save();
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    // Adding languages requires a container rebuild in the test running
    // environment so that multilingual services are used.
    $this->rebuildContainer();
  }

  /**
   * Tests existing translations being edited after enabling content moderation.
   */
  public function testModerationWithExistingContent() {
    // Create a published article in English.
    $edit = [
      'title[0][value]' => 'Published English node',
      'langcode[0][value]' => 'en',
    ];
    $this->drupalPostForm('node/add/article', $edit, 'Save');
    $this->assertSession()->pageTextContains('Article Published English node has been created.');
    $english_node = $this->drupalGetNodeByTitle('Published English node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'Published French node',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article Published French node has been updated.');

    // Install content moderation and enable moderation on Article node type.
    \Drupal::service('module_installer')->install(['content_moderation']);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();
    $this->drupalLogin($this->rootUser);

    // Edit the English node.
    $this->drupalGet('node/' . $english_node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Published English new node',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Article Published English new node has been updated.');
    // Edit the French translation.
    $this->drupalGet('fr/node/' . $english_node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => 'Published French new node',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Article Published French new node has been updated.');
  }

}
