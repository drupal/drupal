<?php

namespace Drupal\Tests\workflows\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * @coversDefaultClass \Drupal\workflows\WorkflowAccessControlHandler
 * @group workflows
 */
class WorkflowAccessControlHandlerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workflows',
    'workflow_type_test',
    'system',
    'user',
  ];

  /**
   * The workflow access control handler.
   *
   * @var \Drupal\workflows\WorkflowAccessControlHandler
   */
  protected $accessControlHandler;

  /**
   * A test admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A non-privileged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('workflow');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    $this->accessControlHandler = $this->container->get('entity_type.manager')->getAccessControlHandler('workflow');

    // Create and discard user 1, which is special and bypasses all access
    // checking.
    $this->createUser([]);
    $this->user = $this->createUser([]);
    $this->adminUser = $this->createUser(['administer workflows']);
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCheckCreateAccess() {
    // A user must have the correct permission to create a workflow.
    $this->assertEquals(
      AccessResult::neutral()
        ->addCacheContexts(['user.permissions'])
        ->setReason("The 'administer workflows' permission is required.")
        ->addCacheTags(['workflow_type_plugins']),
      $this->accessControlHandler->createAccess(NULL, $this->user, [], TRUE)
    );
    $this->assertEquals(
      AccessResult::allowed()
        ->addCacheContexts(['user.permissions'])
        ->addCacheTags(['workflow_type_plugins']),
      $this->accessControlHandler->createAccess(NULL, $this->adminUser, [], TRUE)
    );

    // Remove all plugin types and ensure not even the admin user is allowed to
    // create a workflow.
    workflow_type_test_set_definitions([]);
    $this->accessControlHandler->resetCache();
    $this->assertEquals(
      AccessResult::neutral()
        ->addCacheContexts(['user.permissions'])
        ->addCacheTags(['workflow_type_plugins']),
      $this->accessControlHandler->createAccess(NULL, $this->adminUser, [], TRUE)
    );
  }

  /**
   * @covers ::checkAccess
   * @dataProvider checkAccessProvider
   */
  public function testCheckAccess($user, $operation, $result, $states_to_create = []) {
    $workflow = Workflow::create([
      'type' => 'workflow_type_test',
      'id' => 'test_workflow',
    ]);
    $workflow->save();
    $workflow_type = $workflow->getTypePlugin();
    foreach ($states_to_create as $state_id => $is_required) {
      $workflow_type->addState($state_id, $this->randomString());
    }
    \Drupal::state()->set('workflow_type_test.required_states', array_filter($states_to_create));
    $this->assertEquals($result, $this->accessControlHandler->access($workflow, $operation, $this->{$user}, TRUE));
  }

  /**
   * Data provider for ::testCheckAccess.
   *
   * @return array
   */
  public function checkAccessProvider() {
    $container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    return [
      'Admin view' => [
        'adminUser',
        'view',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
      'Admin update' => [
        'adminUser',
        'update',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
      'Admin delete' => [
        'adminUser',
        'delete',
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
      ],
      'Admin delete only state' => [
        'adminUser',
        'delete-state:foo',
        AccessResult::neutral()->addCacheTags(['config:workflows.workflow.test_workflow']),
        ['foo' => FALSE],
      ],
      'Admin delete one of two states' => [
        'adminUser',
        'delete-state:foo',
        AccessResult::allowed()
          ->addCacheTags(['config:workflows.workflow.test_workflow'])
          ->addCacheContexts(['user.permissions']),
        ['foo' => FALSE, 'bar' => FALSE],
      ],
      'Admin delete required state when there are >1 states' => [
        'adminUser',
        'delete-state:foo',
        AccessResult::allowed()
          ->addCacheTags(['config:workflows.workflow.test_workflow'])
          ->addCacheContexts(['user.permissions']),
        ['foo' => TRUE, 'bar' => FALSE],
      ],
      'User view' => [
        'user',
        'view',
        AccessResult::neutral()
          ->addCacheContexts(['user.permissions'])
          ->setReason("The 'administer workflows' permission is required."),
      ],
      'User update' => [
        'user',
        'update',
        AccessResult::neutral()
          ->addCacheContexts(['user.permissions'])
          ->setReason("The 'administer workflows' permission is required."),
      ],
      'User delete' => [
        'user',
        'delete',
        AccessResult::neutral()
          ->addCacheContexts(['user.permissions'])
          ->setReason("The 'administer workflows' permission is required."),
      ],
      'User delete only state' => [
        'user',
        'delete-state:foo',
        AccessResult::neutral()->addCacheTags(['config:workflows.workflow.test_workflow']),
        ['foo' => FALSE],
      ],
      'User delete one of two states' => [
        'user',
        'delete-state:foo',
        AccessResult::neutral()
          ->addCacheTags(['config:workflows.workflow.test_workflow'])
          ->addCacheContexts(['user.permissions'])
          ->setReason("The 'administer workflows' permission is required."),
        ['foo' => FALSE, 'bar' => FALSE],
      ],
      'User delete required state when there are >1 states' => [
        'user',
        'delete-state:foo',
        AccessResult::neutral()
          ->addCacheTags(['config:workflows.workflow.test_workflow'])
          ->addCacheContexts(['user.permissions'])
          ->setReason("The 'administer workflows' permission is required."),
        ['foo' => TRUE, 'bar' => FALSE],
      ],
    ];
  }

}
