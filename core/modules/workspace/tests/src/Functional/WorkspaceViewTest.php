<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workspace\Entity\Workspace;

/**
 * Tests permission controls on workspaces.
 *
 * @group workspace
 */
class WorkspaceViewTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workspace', 'workspace'];

  /**
   * Verifies that a user can view their own workspace.
   */
  public function testViewOwnWorkspace() {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
      'view own workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);
    $this->createWorkspaceThroughUi('Bears', 'bears');

    $bears = Workspace::load('bears');

    // Now login as a different user and create a workspace.
    $editor2 = $this->drupalCreateUser($permissions);

    $this->drupalLogin($editor2);
    $this->createWorkspaceThroughUi('Packers', 'packers');

    $packers = Workspace::load('packers');

    // Load the activate form for the Bears workspace. It should fail because
    // the workspace belongs to someone else.
    $this->drupalGet("admin/config/workflow/workspace/manage/{$bears->id()}/activate");
    $this->assertSession()->statusCodeEquals(403);

    // But editor 2 should be able to activate the Packers workspace.
    $this->drupalGet("admin/config/workflow/workspace/manage/{$packers->id()}/activate");
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Verifies that a user can view any workspace.
   */
  public function testViewAnyWorkspace() {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
      'view any workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);

    $this->createWorkspaceThroughUi('Bears', 'bears');

    $bears = Workspace::load('bears');

    // Now login as a different user and create a workspace.
    $editor2 = $this->drupalCreateUser($permissions);

    $this->drupalLogin($editor2);
    $this->createWorkspaceThroughUi('Packers', 'packers');

    $packers = Workspace::load('packers');

    // Load the activate form for the Bears workspace. This user should be
    // able to see both workspaces because of the "view any" permission.
    $this->drupalGet("admin/config/workflow/workspace/manage/{$bears->id()}/activate");

    $this->assertSession()->statusCodeEquals(200);

    // But editor 2 should be able to activate the Packers workspace.
    $this->drupalGet("admin/config/workflow/workspace/manage/{$packers->id()}/activate");
    $this->assertSession()->statusCodeEquals(200);
  }

}
