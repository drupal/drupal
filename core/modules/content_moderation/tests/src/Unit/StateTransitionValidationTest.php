<?php

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\StateTransitionValidation;
use Drupal\Tests\UnitTestCase;
use Drupal\workflow_type_test\Plugin\WorkflowType\TestType;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\State;
use Drupal\workflows\WorkflowTypeManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\content_moderation\StateTransitionValidation
 * @group content_moderation
 */
class StateTransitionValidationTest extends UnitTestCase {

  /**
   * A test workflow.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a container so that the plugin manager and workflow type can be
    // mocked.
    $container = new ContainerBuilder();
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('content_moderation', Argument::any())->willReturn(new TestType([], '', []));
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);

    $this->workflow = new Workflow(['id' => 'process', 'type' => 'content_moderation'], 'workflow');
    $this->workflow
      ->getTypePlugin()
      ->addState('draft', 'draft')
      ->addState('needs_review', 'needs_review')
      ->addState('published', 'published')
      ->addTransition('draft', 'draft', ['draft'], 'draft')
      ->addTransition('review', 'review', ['draft'], 'needs_review')
      ->addTransition('publish', 'publish', ['needs_review', 'published'], 'published');
  }

  /**
   * Verifies user-aware transition validation.
   *
   * @param string $from_id
   *   The state to transition from.
   * @param string $to_id
   *   The state to transition to.
   * @param string $permission
   *   The permission to give the user, or not.
   * @param bool $allowed
   *   Whether or not to grant a user this permission.
   * @param bool $result
   *   Whether getValidTransitions() is expected to have the.
   *
   * @dataProvider userTransitionsProvider
   */
  public function testUserSensitiveValidTransitions($from_id, $to_id, $permission, $allowed, $result) {
    $user = $this->prophesize(AccountInterface::class);
    // The one listed permission will be returned as instructed; Any others are
    // always denied.
    $user->hasPermission($permission)->willReturn($allowed);
    $user->hasPermission(Argument::type('string'))->willReturn(FALSE);

    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity = $entity->reveal();
    $entity->moderation_state = new \stdClass();
    $entity->moderation_state->value = $from_id;

    $moderation_info = $this->prophesize(ModerationInformationInterface::class);
    $moderation_info->getWorkflowForEntity($entity)->willReturn($this->workflow);

    $validator = new StateTransitionValidation($moderation_info->reveal());
    $has_transition = FALSE;
    foreach ($validator->getValidTransitions($entity, $user->reveal()) as $transition) {
      if ($transition->to()->id() === $to_id) {
        $has_transition = TRUE;
        break;
      }
    }
    $this->assertSame($result, $has_transition);
  }

  /**
   * @expectedDeprecation Omitting the $entity parameter from Drupal\content_moderation\StateTransitionValidation::isTransitionValid is deprecated and will be required in Drupal 9.0.0.
   * @group legacy
   */
  public function testDeprecatedEntityParameter() {
    $moderation_info = $this->prophesize(ModerationInformationInterface::class);
    $state = new State($this->workflow->getTypePlugin(), 'draft', 'draft');
    $user = $this->prophesize(AccountInterface::class);

    $validator = new StateTransitionValidation($moderation_info->reveal());
    $validator->isTransitionValid($this->workflow, $state, $state, $user->reveal());
  }

  /**
   * Data provider for the user transition test.
   */
  public function userTransitionsProvider() {
    // The user has the right permission, so let it through.
    $ret[] = ['draft', 'draft', 'use process transition draft', TRUE, TRUE];

    // The user doesn't have the right permission, block it.
    $ret[] = ['draft', 'draft', 'use process transition draft', FALSE, FALSE];

    // The user has some other permission that doesn't matter.
    $ret[] = ['draft', 'draft', 'use process transition review', TRUE, FALSE];

    return $ret;
  }

}
