<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\views\Functional\BulkFormTest;

/**
 * Tests the views bulk form in a workspace.
 *
 * @group views
 * @group workspaces
 */
class WorkspaceViewsBulkFormTest extends BulkFormTest {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'workspaces', 'workspaces_ui', 'workspaces_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Override the user created in the parent method to add workspaces access.
    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'administer workspaces',
      'edit any page content',
      'delete any page content',
    ]);
    $this->drupalLogin($admin_user);

    // Ensure that all the test methods are executed in the context of a
    // workspace.
    $this->setupWorkspaceSwitcherBlock();
    $this->createAndActivateWorkspaceThroughUi('Test workspace', 'test');
  }

  /**
   * Tests the Workspaces view bulk form integration.
   */
  public function testBulkForm(): void {
    // Ignore entity types that are not being tested, in order to fully re-use
    // the parent test method.
    $this->ignoreEntityType('view');

    parent::testBulkForm();
  }

}
