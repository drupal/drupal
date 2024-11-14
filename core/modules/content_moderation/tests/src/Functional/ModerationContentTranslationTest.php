<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;
use Drupal\user\Entity\Role;

/**
 * Test content_moderation functionality with content_translation.
 *
 * @group content_moderation
 */
class ModerationContentTranslationTest extends BrowserTestBase {

  use ContentModerationTestTrait;
  use ContentTranslationTestTrait;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
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
    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'create content translations',
      'translate any entity',
    ]);
    $this->drupalLogin($this->adminUser);
    // Create an Article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article'])->save();
    static::createLanguageFromLangcode('fr');
    // Enable content translation on articles.
    $this->enableContentTranslation('node', 'article');
    // Adding languages requires a container rebuild in the test running
    // environment so that multilingual services are used.
    $this->rebuildContainer();
  }

  /**
   * Tests existing translations being edited after enabling content moderation.
   */
  public function testModerationWithExistingContent(): void {
    // Create a published article in English.
    $edit = [
      'title[0][value]' => 'Published English node',
      'langcode[0][value]' => 'en',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
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
    $this->grantPermissions(Role::load(Role::AUTHENTICATED_ID), ['use editorial transition publish']);

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
