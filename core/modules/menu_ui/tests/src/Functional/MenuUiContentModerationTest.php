<?php

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests Menu UI and Content Moderation integration.
 *
 * @group menu_ui
 */
class MenuUiContentModerationTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'content_moderation', 'node', 'menu_ui', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:main');

    // Create a 'page' content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    $workflow = Workflow::load('editorial');
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

    // Try to change the menu link title and save a new non-default (draft)
    // revision.
    $edit = [
      'menu[title]' => 'Test menu link draft',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the menu settings for the published version of this content.');
    $this->assertSession()->linkExists('Test menu link');
    $this->assertSession()->linkNotExists('Test menu link draft');

    // Try to change the menu link description and save a new non-default
    // (draft) revision.
    $edit = [
      'menu[description]' => 'Test menu link description',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the menu settings for the published version of this content.');

    // Try to change the menu link weight and save a new non-default (draft)
    // revision.
    $edit = [
      'menu[weight]' => 1,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the menu settings for the published version of this content.');

    // Try to change the menu link parent and save a new non-default (draft)
    // revision.
    $edit = [
      'menu[menu_parent]' => 'main:test_page_test.front_page',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the menu settings for the published version of this content.');

    // Try to delete the menu link and save a new non-default (draft) revision.
    $edit = [
      'menu[enabled]' => 0,
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextContains('You can only change the menu settings for the published version of this content.');
    $this->assertSession()->linkExists('Test menu link');

    // Try to save a new non-default (draft) revision without any changes and
    // check that the error message is not shown.
    $edit = ['moderation_state[0][state]' => 'draft'];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check that the menu settings were not applied.
    $this->assertSession()->pageTextNotContains('You can only change the menu settings for the published version of this content.');
    $this->assertSession()->linkExists('Test menu link');

    // Create a node.
    $node = $this->drupalCreateNode();

    // Publish the node with no changes.
    $edit = ['moderation_state[0][state]' => 'published'];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->responseContains(t('Page %label has been updated.', ['%label' => $node->toLink($node->label())->toString()]));

    // Add a menu link and save and create a new non-default (draft) revision.
    $edit = [
      'menu[enabled]' => 1,
      'menu[title]' => 'Test menu link',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    $this->assertSession()->pageTextContains('You can only change the menu settings for the published version of this content.');
  }

}
