<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Test the workspace entity.
 *
 * @group workspaces
 */
class WorkspaceTest extends BrowserTestBase {

  use WorkspaceTestUtilities;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'field_ui', 'node', 'toolbar', 'user', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor1;

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor2;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
      'edit any workspace',
      'view own workspace',
      'access toolbar',
    ];

    $this->editor1 = $this->drupalCreateUser($permissions);
    $this->editor2 = $this->drupalCreateUser($permissions);
  }

  /**
   * Test creating a workspace with special characters.
   */
  public function testSpecialCharacters() {
    $this->drupalLogin($this->editor1);

    // Test a valid workspace name.
    $this->createWorkspaceThroughUi('Workspace 1', 'a0_$()+-/');

    // Test and invalid workspace name.
    $this->drupalGet('/admin/config/workflow/workspaces/add');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $page->fillField('label', 'workspace2');
    $page->fillField('id', 'A!"£%^&*{}#~@?');
    $page->findButton('Save')->click();
    $page->hasContent("This value is not valid");
  }

  /**
   * Test that the toolbar correctly shows the active workspace.
   */
  public function testWorkspaceToolbar() {
    $this->drupalLogin($this->editor1);

    $this->drupalGet('/admin/config/workflow/workspaces/add');
    $this->submitForm([
      'id' => 'test_workspace',
      'label' => 'Test workspace',
    ], 'Save');

    // Activate the test workspace.
    $this->drupalGet('/admin/config/workflow/workspaces/manage/test_workspace/activate');
    $this->submitForm([], 'Confirm');

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    // Toolbar should show the correct label.
    $this->assertTrue($page->hasLink('Test workspace'));

    // Change the workspace label.
    $this->drupalGet('/admin/config/workflow/workspaces/manage/test_workspace/edit');
    $this->submitForm(['label' => 'New name'], 'Save');

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    // Toolbar should show the new label.
    $this->assertTrue($page->hasLink('New name'));
  }

  /**
   * Test changing the owner of a workspace.
   */
  public function testWorkspaceOwner() {
    $this->drupalLogin($this->editor1);

    $this->drupalGet('/admin/config/workflow/workspaces/add');
    $this->submitForm([
      'id' => 'test_workspace',
      'label' => 'Test workspace',
    ], 'Save');

    $storage = \Drupal::entityTypeManager()->getStorage('workspace');
    $test_workspace = $storage->load('test_workspace');
    $this->assertEquals($this->editor1->id(), $test_workspace->getOwnerId());

    $this->drupalGet('/admin/config/workflow/workspaces/manage/test_workspace/edit');
    $this->submitForm(['uid[0][target_id]' => $this->editor2->getAccountName()], 'Save');

    $test_workspace = $storage->loadUnchanged('test_workspace');
    $this->assertEquals($this->editor2->id(), $test_workspace->getOwnerId());
  }

  /**
   * Tests that editing a workspace creates a new revision.
   */
  public function testWorkspaceFormRevisions() {
    $this->drupalLogin($this->editor1);
    $storage = \Drupal::entityTypeManager()->getStorage('workspace');

    // The current 'stage' workspace entity should be revision 1.
    $stage_workspace = $storage->load('stage');
    $this->assertEquals('1', $stage_workspace->getRevisionId());

    // Re-save the 'stage' workspace via the UI to create revision 2.
    $this->drupalGet($stage_workspace->toUrl('edit-form')->toString());
    $this->submitForm([], 'Save');
    $stage_workspace = $storage->loadUnchanged('stage');
    $this->assertEquals('2', $stage_workspace->getRevisionId());
  }

  /**
   * Tests adding new fields to workspace entities.
   */
  public function testWorkspaceFieldUi() {
    $user = $this->drupalCreateUser([
      'administer workspaces',
      'access administration pages',
      'administer site configuration',
      'administer workspace fields',
      'administer workspace display',
      'administer workspace form display',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('admin/config/workflow/workspaces/fields');
    $this->assertSession()->statusCodeEquals(200);

    // Create a new filed.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_label = $this->randomMachineName();
    $edit = [
      'new_storage_type' => 'string',
      'label' => $field_label,
      'field_name' => $field_name,
    ];
    $this->drupalGet("admin/config/workflow/workspaces/fields/add-field");
    $this->submitForm($edit, 'Save and continue');
    $page = $this->getSession()->getPage();
    $page->pressButton('Save field settings');
    $page->pressButton('Save settings');

    // Check that the field is displayed on the manage form display page.
    $this->drupalGet('admin/config/workflow/workspaces/form-display');
    $this->assertSession()->pageTextContains($field_label);

    // Check that the field is displayed on the manage display page.
    $this->drupalGet('admin/config/workflow/workspaces/display');
    $this->assertSession()->pageTextContains($field_label);
  }

  /**
   * Verifies that a workspace with existing content may be deleted.
   */
  public function testDeleteWorkspaceWithExistingContent() {
    $this->createContentType(['type' => 'test', 'label' => 'Test']);
    $this->setupWorkspaceSwitcherBlock();

    // Login and create a workspace.
    $this->drupalLogin($this->rootUser);
    $may_4 = $this->createWorkspaceThroughUi('May 4', 'may_4');
    $this->switchToWorkspace($may_4);

    // Create a node in the workspace.
    $node = $this->createNodeThroughUi('A mayfly flies / In May or June', 'test');

    // Delete the workspace.
    $this->drupalGet('/admin/config/workflow/workspaces/manage/may_4/delete');
    $this->assertSession()->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $page->findButton('Delete')->click();
    $page->hasContent('The workspace May 4 has been deleted.');
  }

}
