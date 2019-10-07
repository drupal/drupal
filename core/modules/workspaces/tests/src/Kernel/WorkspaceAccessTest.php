<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspaceAccessException;

/**
 * Tests access on workspaces.
 *
 * @group workspaces
 */
class WorkspaceAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'workspaces',
    'workspace_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('workspaces', ['workspace_association']);

    $this->installEntitySchema('workspace');
    $this->installEntitySchema('user');

    // User 1.
    $this->createUser();
  }

  /**
   * Test cases for testWorkspaceAccess().
   *
   * @return array
   *   An array of operations and permissions to test with.
   */
  public function operationCases() {
    return [
      ['create', 'create workspace'],
      ['view', 'view any workspace'],
      ['view', 'view own workspace'],
      ['update', 'edit any workspace'],
      ['update', 'edit own workspace'],
      ['delete', 'delete any workspace'],
      ['delete', 'delete own workspace'],
    ];
  }

  /**
   * Verifies all workspace roles have the correct access for the operation.
   *
   * @param string $operation
   *   The operation to test with.
   * @param string $permission
   *   The permission to test with.
   *
   * @dataProvider operationCases
   */
  public function testWorkspaceAccess($operation, $permission) {
    $user = $this->createUser();
    $this->setCurrentUser($user);
    $workspace = Workspace::create(['id' => 'oak']);
    $workspace->save();

    $this->assertFalse($workspace->access($operation, $user));

    \Drupal::entityTypeManager()->getAccessControlHandler('workspace')->resetCache();
    $role = $this->createRole([$permission]);
    $user->addRole($role);
    $this->assertTrue($workspace->access($operation, $user));
  }

  /**
   * Tests workspace publishing access.
   */
  public function testPublishWorkspaceAccess() {
    $user = $this->createUser([
      'view own workspace',
      'edit own workspace',
    ]);
    $this->setCurrentUser($user);

    $workspace = Workspace::create(['id' => 'stage']);
    $workspace->save();

    // Check that, by default, an admin user is allowed to publish a workspace.
    $workspace->publish();

    // Simulate an external factor which decides that a workspace can not be
    // published.
    \Drupal::state()->set('workspace_access_test.result.publish', AccessResult::forbidden());
    \Drupal::entityTypeManager()->getAccessControlHandler('workspace')->resetCache();

    $this->expectException(WorkspaceAccessException::class);
    $workspace->publish();
  }

}
