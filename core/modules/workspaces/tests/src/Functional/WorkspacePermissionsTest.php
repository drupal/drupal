<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests permission controls on workspaces.
 *
 * @group workspaces
 * @group #slow
 */
class WorkspacePermissionsTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Verifies that a user can create but not edit a workspace.
   */
  public function testCreateWorkspace(): void {
    $editor = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'create workspace',
    ]);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor);
    $this->createWorkspaceThroughUi('Bears', 'bears');

    // Now edit that same workspace; We shouldn't be able to do so, since
    // we don't have edit permissions.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $etm */
    $etm = \Drupal::service('entity_type.manager');
    /** @var \Drupal\workspaces\WorkspaceInterface $bears */
    $entity_list = $etm->getStorage('workspace')->loadByProperties(['label' => 'Bears']);
    $bears = current($entity_list);

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/edit");
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Verifies that a user can create and edit only their own workspace.
   */
  public function testEditOwnWorkspace(): void {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);
    $this->createWorkspaceThroughUi('Bears', 'bears');

    // Now edit that same workspace; We should be able to do so.
    $bears = Workspace::load('bears');

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Bears again');
    $page->fillField('id', 'bears');
    $page->findButton('Save')->click();
    $page->hasContent('Bears again (bears)');

    // Now login as a different user and ensure they don't have edit access,
    // and vice versa.
    $editor2 = $this->drupalCreateUser($permissions);

    $this->drupalLogin($editor2);
    $this->createWorkspaceThroughUi('Packers', 'packers');
    $packers = Workspace::load('packers');

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$packers->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/edit");
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Verifies that a user can edit any workspace.
   */
  public function testEditAnyWorkspace(): void {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);
    $this->createWorkspaceThroughUi('Bears', 'bears');

    // Now edit that same workspace; We should be able to do so.
    $bears = Workspace::load('bears');

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $page->fillField('label', 'Bears again');
    $page->fillField('id', 'bears');
    $page->findButton('Save')->click();
    $page->hasContent('Bears again (bears)');

    // Now login as a different user and ensure they don't have edit access,
    // and vice versa.
    $admin = $this->drupalCreateUser(array_merge($permissions, ['edit any workspace']));

    $this->drupalLogin($admin);
    $this->createWorkspaceThroughUi('Packers', 'packers');
    $packers = Workspace::load('packers');

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$packers->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Verifies that a user can create and delete only their own workspace.
   */
  public function testDeleteOwnWorkspace(): void {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'delete own workspace',
    ];
    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);
    $bears = $this->createWorkspaceThroughUi('Bears', 'bears');

    // Now try to delete that same workspace; We should be able to do so.
    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);

    // Now login as a different user and ensure they don't have edit access,
    // and vice versa.
    $editor2 = $this->drupalCreateUser($permissions);

    $this->drupalLogin($editor2);
    $packers = $this->createWorkspaceThroughUi('Packers', 'packers');

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$packers->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/delete");
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Verifies that a user can delete any workspace.
   */
  public function testDeleteAnyWorkspace(): void {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'delete own workspace',
    ];
    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);
    $bears = $this->createWorkspaceThroughUi('Bears', 'bears');

    // Now edit that same workspace; We should be able to do so.
    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);

    // Now login as a different user and ensure they have delete access on both
    // workspaces.
    $admin = $this->drupalCreateUser(array_merge($permissions, ['delete any workspace']));

    $this->drupalLogin($admin);
    $packers = $this->createWorkspaceThroughUi('Packers', 'packers');

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$packers->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("/admin/config/workflow/workspaces/manage/{$bears->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);
  }

}
