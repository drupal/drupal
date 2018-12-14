<?php

namespace Drupal\Tests\workflows\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\WorkflowDeleteAccessCheck;
use Drupal\workflows\WorkflowStateTransitionOperationsAccessCheck;
use Drupal\workflows\WorkflowInterface;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\workflows\WorkflowStateTransitionOperationsAccessCheck
 * @group workflows
 */
class WorkflowStateTransitionOperationsAccessCheckTest extends UnitTestCase {

  /**
   * Test the access method correctly proxies to the entity access system.
   *
   * @covers ::access
   * @dataProvider accessTestCases
   */
  public function testAccess($route_requirement, $resulting_entity_access_check, $route_parameters = []) {
    $workflow_entity_access_result = AccessResult::allowed();
    $workflow = $this->prophesize(WorkflowInterface::class);
    $workflow->access($resulting_entity_access_check, Argument::type(AccountInterface::class), TRUE)
      ->shouldBeCalled()
      ->willReturn($workflow_entity_access_result);

    $route = new Route('', [
      'workflow' => NULL,
      'workflow_transition' => NULL,
      'workflow_state' => NULL,
    ], [
      '_workflow_access' => $route_requirement,
    ]);
    $route_match_params = ['workflow' => $workflow->reveal()] + $route_parameters;
    $route_match = new RouteMatch(NULL, $route, $route_match_params);

    $access_check = new WorkflowStateTransitionOperationsAccessCheck();
    $account = $this->prophesize(AccountInterface::class);
    $this->assertEquals($workflow_entity_access_result, $access_check->access($route_match, $account->reveal()));
  }

  /**
   * Test cases for ::testAccess.
   */
  public function accessTestCases() {
    return [
      'Transition add' => [
        'add-transition',
        'add-transition',
      ],
      'Transition update' => [
        'update-transition',
        'update-transition:foo-transition',
        [
          'workflow_transition' => 'foo-transition',
        ],
      ],
      'Transition delete' => [
        'delete-transition',
        'delete-transition:foo-transition',
        [
          'workflow_transition' => 'foo-transition',
        ],
      ],
      'State add' => [
        'add-state',
        'add-state',
      ],
      'State update' => [
        'update-state',
        'update-state:bar-state',
        [
          'workflow_state' => 'bar-state',
        ],
      ],
      'State delete' => [
        'delete-state',
        'delete-state:bar-state',
        [
          'workflow_state' => 'bar-state',
        ],
      ],
    ];
  }

  /**
   * @covers ::access
   */
  public function testMissingRouteParams() {
    $workflow = $this->prophesize(WorkflowInterface::class);
    $workflow->access()->shouldNotBeCalled();

    $route = new Route('', [
      'workflow' => NULL,
      'workflow_state' => NULL,
    ], [
      '_workflow_access' => 'update-state',
    ]);

    $access_check = new WorkflowStateTransitionOperationsAccessCheck();
    $account = $this->prophesize(AccountInterface::class);

    $missing_both = new RouteMatch(NULL, $route, []);
    $this->assertEquals(AccessResult::neutral(), $access_check->access($missing_both, $account->reveal()));

    $missing_state = new RouteMatch(NULL, $route, [
      'workflow' => $workflow->reveal(),
    ]);
    $this->assertEquals(AccessResult::neutral(), $access_check->access($missing_state, $account->reveal()));

    $missing_workflow = new RouteMatch(NULL, $route, [
      'workflow_state' => 'foo',
    ]);
    $this->assertEquals(AccessResult::neutral(), $access_check->access($missing_workflow, $account->reveal()));
  }

  /**
   * @covers ::access
   * @dataProvider invalidOperationNameTestCases
   */
  public function testInvalidOperationName($operation_name) {
    $this->setExpectedException(\Exception::class, "Invalid _workflow_access operation '$operation_name' specified for route 'Foo Route'.");
    $route = new Route('', [], [
      '_workflow_access' => $operation_name,
    ]);
    $access_check = new WorkflowStateTransitionOperationsAccessCheck();
    $account = $this->prophesize(AccountInterface::class);
    $access_check->access(new RouteMatch('Foo Route', $route, []), $account->reveal());
  }

  /**
   * Test cases for ::testInvalidOperationName.
   */
  public function invalidOperationNameTestCases() {
    return [
      ['invalid-op'],
      ['foo-add-transition'],
      ['add-transition-bar'],
    ];
  }

  /**
   * @covers \Drupal\workflows\WorkflowDeleteAccessCheck::access
   * @expectedDeprecation Using the _workflow_state_delete_access check is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0, use _workflow_access instead. As an internal API _workflow_state_delete_access may also be removed in a minor release.
   * @group legacy
   */
  public function testLegacyWorkflowStateDeleteAccessCheck() {
    $workflow_entity_access_result = AccessResult::allowed();

    // When using the legacy access check, passing a route with a state called
    // 'foo-state' will result in an entity access check of
    // 'delete-state:foo-state'.
    $workflow = $this->prophesize(WorkflowInterface::class);
    $workflow->access('delete-state:foo-state', Argument::type(AccountInterface::class), TRUE)
      ->shouldBeCalled()
      ->willReturn($workflow_entity_access_result);

    $route = new Route('', [
      'workflow' => NULL,
      'workflow_state' => NULL,
    ], ['_workflow_state_delete_access' => 'true']);
    $route_match = new RouteMatch(NULL, $route, [
      'workflow' => $workflow->reveal(),
      'workflow_state' => 'foo-state',
    ]);

    $access_check = new WorkflowDeleteAccessCheck();
    $this->assertEquals($workflow_entity_access_result, $access_check->access($route_match, $this->prophesize(AccountInterface::class)->reveal()));
  }

}
