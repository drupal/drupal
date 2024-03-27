<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests access on workspaces.
 *
 * @group workspaces
 * @group #slow
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
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('workspaces', ['workspace_association']);

    $this->installEntitySchema('workspace');
    $this->installEntitySchema('user');

    // User 1.
    $this->createUser();
  }

  /**
   * Tests cases for testWorkspaceAccess().
   *
   * @return array
   *   An array of operations and permissions to test with.
   */
  public function operationCases() {
    return [
      ['create', 'administer workspaces'],
      ['create', 'create workspace'],
      ['view', 'administer workspaces'],
      ['view', 'view any workspace'],
      ['view', 'view own workspace'],
      ['update', 'administer workspaces'],
      ['update', 'edit any workspace'],
      ['update', 'edit own workspace'],
      ['delete', 'administer workspaces'],
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
    $this->assertTrue($workspace->access('publish'));

    // Simulate an external factor which decides that a workspace can not be
    // published.
    \Drupal::state()->set('workspace_access_test.result.publish', AccessResult::forbidden());
    \Drupal::entityTypeManager()->getAccessControlHandler('workspace')->resetCache();
    $this->assertFalse($workspace->access('publish'));
  }

  /**
   * @covers \Drupal\workspaces\Plugin\EntityReferenceSelection\WorkspaceSelection::getReferenceableEntities
   */
  public function testWorkspaceSelection() {
    $own_permission_user = $this->createUser(['view own workspace']);
    $any_permission_user = $this->createUser(['view any workspace']);
    $admin_permission_user = $this->createUser(['administer workspaces']);

    // Create the following workspace hierarchy:
    // - top1 ($own_permission_user)
    // --- child1_1 ($own_permission_user)
    // --- child1_2 ($any_permission_user)
    // ----- child1_2_1 ($any_permission_user)
    // - top2 ($admin_permission_user)
    // --- child2_1 ($admin_permission_user)
    $created_time = \Drupal::time()->getCurrentTime();
    Workspace::create([
      'uid' => $own_permission_user->id(),
      'id' => 'top1',
      'label' => 'top1',
      'created' => ++$created_time,
    ])->save();
    Workspace::create([
      'uid' => $own_permission_user->id(),
      'id' => 'child1_1',
      'parent' => 'top1',
      'label' => 'child1_1',
      'created' => ++$created_time,
    ])->save();
    Workspace::create([
      'uid' => $any_permission_user->id(),
      'id' => 'child1_2',
      'parent' => 'top1',
      'label' => 'child1_2',
      'created' => ++$created_time,
    ])->save();
    Workspace::create([
      'uid' => $any_permission_user->id(),
      'id' => 'child1_2_1',
      'parent' => 'child1_2',
      'label' => 'child1_2_1',
      'created' => ++$created_time,
    ])->save();
    Workspace::create([
      'uid' => $admin_permission_user->id(),
      'id' => 'top2',
      'label' => 'top2',
      'created' => ++$created_time,
    ])->save();
    Workspace::create([
      'uid' => $admin_permission_user->id(),
      'id' => 'child2_1',
      'parent' => 'top2',
      'label' => 'child2_1',
      'created' => ++$created_time,
    ])->save();

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $selection_handler */
    $selection_handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance([
      'target_type' => 'workspace',
      'handler' => 'default',
      'sort' => [
        'field' => 'created',
        'direction' => 'asc',
      ],
    ]);

    // The $own_permission_user should only be allowed to reference 'top1' and
    // 'child1_1'.
    $this->setCurrentUser($own_permission_user);
    $expected = [
      'top1',
      'child1_1',
    ];
    $this->assertEquals($expected, array_keys($selection_handler->getReferenceableEntities()['workspace']));
    $this->assertEquals($expected, array_keys($selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 3)['workspace']));
    $expected = [
      'top1',
    ];
    $this->assertEquals($expected, array_keys($selection_handler->getReferenceableEntities('top')['workspace']));

    // The $any_permission_user and $admin_permission_user should be allowed to
    // reference any workspace.
    $expected_all = [
      'top1',
      'child1_1',
      'child1_2',
      'child1_2_1',
      'top2',
      'child2_1',
    ];
    $expected_3 = [
      'top1',
      'child1_1',
      'child1_2',
    ];
    $expected_top = [
      'top1',
      'top2',
    ];
    $this->setCurrentUser($any_permission_user);
    $this->assertEquals($expected_all, array_keys($selection_handler->getReferenceableEntities()['workspace']));
    $this->assertEquals($expected_3, array_keys($selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 3)['workspace']));
    $this->assertEquals($expected_top, array_keys($selection_handler->getReferenceableEntities('top')['workspace']));

    $this->setCurrentUser($admin_permission_user);
    $this->assertEquals($expected_all, array_keys($selection_handler->getReferenceableEntities()['workspace']));
    $this->assertEquals($expected_3, array_keys($selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 3)['workspace']));
    $this->assertEquals($expected_top, array_keys($selection_handler->getReferenceableEntities('top')['workspace']));
  }

}
