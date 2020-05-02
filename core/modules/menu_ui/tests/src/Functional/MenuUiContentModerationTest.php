<?php

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests Menu UI and Content Moderation integration.
 *
 * @group menu_ui
 */
class MenuUiContentModerationTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'content_moderation',
    'node',
    'menu_ui',
    'test_page_test',
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

    $this->drupalPlaceBlock('system_menu_block:main');

    // Create a 'page' content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * Tests that node drafts can not modify the menu settings.
   */
  public function testMenuUiWithPendingRevisions() {
    $editor = $this->drupalCreateUser([
      'administer nodes',
      'administer menu',
      'create page content',
      'edit any page content',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'view latest version',
      'view any unpublished content',
    ]);
    $this->drupalLogin($editor);

    // Create a node.
    $node = $this->drupalCreateNode();

    // Publish the node with no changes.
    $this->drupalPostForm('node/' . $node->id() . '/edit', [], t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));

    // Create a pending revision with no changes.
    $edit = ['moderation_state[0][state]' => 'draft'];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));

    // Add a menu link and save a new default (published) revision.
    $edit = [
      'menu[enabled]' => 1,
      'menu[title]' => 'Test menu link',
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    $this->assertSession()->linkExists('Test menu link');

    // Try to change the menu link weight and save a new non-default (draft)
    // revision.
    $edit = [
      'menu[weight]' => 1,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the menu item weight for the published version of this content.');

    // Try to change the menu link parent and save a new non-default (draft)
    // revision.
    $edit = [
      'menu[menu_parent]' => 'main:test_page_test.front_page',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the parent menu item for the published version of this content.');

    // Try to delete the menu link and save a new non-default (draft) revision.
    $edit = [
      'menu[enabled]' => 0,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only remove the menu item in the published version of this content.');
    $this->assertSession()->linkExists('Test menu link');

    // Try to change the menu link title and description and save a new
    // non-default (draft) revision.
    $edit = [
      'menu[title]' => 'Test menu link draft',
      'menu[description]' => 'Test menu link description',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));

    // Ensure the content was not immediately published.
    $this->assertSession()->linkExists('Test menu link');

    // Publish the node and ensure the new link text was published.
    $edit = [
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->linkExists('Test menu link draft');

    // Try to save a new non-default (draft) revision without any changes and
    // check that the error message is not shown.
    $edit = ['moderation_state[0][state]' => 'draft'];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Create a node.
    $node = $this->drupalCreateNode();

    // Publish the node with no changes.
    $edit = ['moderation_state[0][state]' => 'published'];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));

    // Add a menu link and save and create a new non-default (draft) revision
    // and ensure it's not immediately published.
    $edit = [
      'menu[enabled]' => 1,
      'menu[title]' => 'Second test menu link',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));
    $this->assertSession()->linkNotExists('Second test menu link');

    // Publish the content and ensure the new menu link shows up.
    $edit = [
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));
    $this->assertSession()->linkExists('Second test menu link');
  }

}
