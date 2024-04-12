<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests entity deletions with workspaces.
 *
 * @group workspaces
 */
class WorkspaceEntityDeleteTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'node', 'user', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'article', 'label' => 'Article']);
    $this->setupWorkspaceSwitcherBlock();
  }

  /**
   * Test entity deletion with workspaces.
   */
  public function testEntityDelete(): void {
    $assert_session = $this->assertSession();

    $permissions = [
      'administer workspaces',
      'create workspace',
      'access content overview',
      'administer nodes',
      'create article content',
      'edit own article content',
      'delete own article content',
      'view own unpublished content',
    ];
    $editor = $this->drupalCreateUser($permissions);
    $this->drupalLogin($editor);

    // Create a Dev workspace as a child of Stage.
    $stage = Workspace::load('stage');
    $dev = $this->createWorkspaceThroughUi('Dev', 'dev', 'stage');

    // Create a published and an unpublished node in Live.
    $published_live = $this->createNodeThroughUi('Test 1 published - live', 'article');
    $unpublished_live = $this->createNodeThroughUi('Test 2 unpublished - live', 'article', FALSE);

    // Create a published and an unpublished node in Stage.
    $this->switchToWorkspace($stage);
    $published_stage = $this->createNodeThroughUi('Test 3 published - stage', 'article');
    $unpublished_stage = $this->createNodeThroughUi('Test 4 unpublished - stage', 'article', FALSE);

    // Check that the Live nodes (both published and unpublished) can not be
    // deleted, while the Stage nodes can be.
    $this->drupalGet('admin/content');
    $assert_session->linkByHrefNotExists($published_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefExists($published_stage->toUrl('delete-form')->toString());
    $assert_session->linkByHrefExists($unpublished_stage->toUrl('delete-form')->toString());

    // Switch to Dev and check which nodes can be deleted.
    $this->switchToWorkspace($dev);
    $this->drupalGet('admin/content');

    // The two Live nodes have the same deletable status as they had in Stage.
    $assert_session->linkByHrefNotExists($published_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_live->toUrl('delete-form')->toString());

    // The two Stage nodes should not be deletable in a child workspace (Dev).
    $assert_session->linkByHrefNotExists($published_stage->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_stage->toUrl('delete-form')->toString());

    // Add a new revision for each node and check that their 'deletable' status
    // remains unchanged.
    $this->switchToWorkspace($stage);
    $this->drupalGet($published_live->toUrl('edit-form')->toString());
    $this->submitForm([], 'Save');
    $this->drupalGet($unpublished_live->toUrl('edit-form')->toString());
    $this->submitForm([], 'Save');
    $this->drupalGet($published_stage->toUrl('edit-form')->toString());
    $this->submitForm([], 'Save');
    $this->drupalGet($unpublished_stage->toUrl('edit-form')->toString());
    $this->submitForm([], 'Save');

    $this->drupalGet('admin/content');
    $assert_session->linkByHrefNotExists($published_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefExists($published_stage->toUrl('delete-form')->toString());
    $assert_session->linkByHrefExists($unpublished_stage->toUrl('delete-form')->toString());

    // Publish the Stage workspace and check that no entity can be deleted
    // anymore in Stage nor Dev.
    $stage->publish();
    $this->drupalGet('admin/content');
    $assert_session->linkByHrefNotExists($published_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($published_stage->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_stage->toUrl('delete-form')->toString());

    $this->switchToWorkspace($dev);
    $this->drupalGet('admin/content');
    $assert_session->linkByHrefNotExists($published_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_live->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($published_stage->toUrl('delete-form')->toString());
    $assert_session->linkByHrefNotExists($unpublished_stage->toUrl('delete-form')->toString());
  }

  /**
   * Test node deletion with workspaces and the 'bypass node access' permission.
   */
  public function testNodeDeleteWithBypassAccessPermission(): void {
    $assert_session = $this->assertSession();

    $permissions = [
      'administer workspaces',
      'create workspace',
      'access content overview',
      'bypass node access',
    ];
    $editor = $this->drupalCreateUser($permissions);
    $this->drupalLogin($editor);

    // Create a published node in Live.
    $published_live = $this->createNodeThroughUi('Test 1 published - live', 'article');

    $stage = Workspace::load('stage');
    $this->switchToWorkspace($stage);

    // A user with the 'bypass node access' permission will be able to see the
    // 'Delete' operation button, but it shouldn't be able to perform the
    // deletion.
    $this->drupalGet('admin/content');
    $assert_session->linkByHrefExists($published_live->toUrl('delete-form')->toString());
    $this->clickLink('Delete');
    $assert_session->pageTextContains('This content item can only be deleted in the Live workspace.');
    $assert_session->buttonNotExists('Delete');

    $this->drupalGet($published_live->toUrl('delete-form')->toString());
    $assert_session->pageTextContains('This content item can only be deleted in the Live workspace.');
    $assert_session->buttonNotExists('Delete');

    // Go back to Live and check that the delete form is not affected by the
    // workspace delete protection.
    $this->switchToLive();
    $this->drupalGet($published_live->toUrl('delete-form')->toString());
    $assert_session->pageTextNotContains('This content item can only be deleted in the Live workspace.');
    $assert_session->buttonExists('Delete');
  }

}
