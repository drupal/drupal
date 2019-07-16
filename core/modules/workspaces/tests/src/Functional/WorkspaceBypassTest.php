<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests access bypass permission controls on workspaces.
 *
 * @group workspaces
 */
class WorkspaceBypassTest extends BrowserTestBase {

  use WorkspaceTestUtilities;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user', 'block', 'workspaces'];

  /**
   * Verifies that a user can edit anything in a workspace they own.
   */
  public function testBypassOwnWorkspace() {
    $permissions = [
      'create workspace',
      'edit own workspace',
      'view own workspace',
      'bypass entity access own workspace',
    ];

    $this->createContentType(['type' => 'test', 'label' => 'Test']);
    $this->setupWorkspaceSwitcherBlock();

    $ditka = $this->drupalCreateUser(array_merge($permissions, ['create test content']));

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($ditka);
    $bears = $this->createWorkspaceThroughUi('Bears', 'bears');
    $this->switchToWorkspace($bears);

    // Now create a node in the Bears workspace, as the owner of that workspace.
    $ditka_bears_node = $this->createNodeThroughUi('Ditka Bears node', 'test');
    $ditka_bears_node_id = $ditka_bears_node->id();

    // Editing both nodes should be possible.
    $this->drupalGet('/node/' . $ditka_bears_node_id . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Create a new user that should be able to edit anything in the Bears
    // workspace.
    $lombardi = $this->drupalCreateUser(array_merge($permissions, ['view any workspace']));
    $this->drupalLogin($lombardi);
    $this->switchToWorkspace($bears);

    // Editor 2 has the bypass permission but does not own the workspace and so,
    // should not be able to create and edit any node.
    $this->drupalGet('/node/' . $ditka_bears_node_id . '/edit');
    $this->assertSession()->statusCodeEquals(403);
  }

}
