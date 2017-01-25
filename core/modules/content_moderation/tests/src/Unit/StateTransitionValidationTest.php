<?php

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\StateTransitionValidation;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\WorkflowTypeInterface;
use Drupal\workflows\WorkflowTypeManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\content_moderation\StateTransitionValidation
 * @group content_moderation
 */
class StateTransitionValidationTest extends \PHPUnit_Framework_TestCase {

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

    $validator = new StateTransitionValidation($this->setUpModerationInformation($entity));
    $has_transition = FALSE;
    foreach ($validator->getValidTransitions($entity, $user->reveal()) as $transition) {
      if ($transition->to()->id() === $to_id) {
        $has_transition = TRUE;
        break;
      }
    }
    $this->assertSame($result, $has_transition);
  }

  protected function setUpModerationInformation(ContentEntityInterface $entity) {
    // Create a container so that the plugin manager and workflow type can be
    // mocked.
    $container = new ContainerBuilder();
    $workflow_type = $this->prophesize(WorkflowTypeInterface::class);
    $workflow_type->decorateState(Argument::any())->willReturnArgument(0);
    $workflow_type->decorateTransition(Argument::any())->willReturnArgument(0);
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('content_moderation', Argument::any())->willReturn($workflow_type->reveal());
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);

    $workflow = new Workflow(['id' => 'process', 'type' => 'content_moderation'], 'workflow');
    $workflow
      ->addState('draft', 'draft')
      ->addState('needs_review', 'needs_review')
      ->addState('published', 'published')
      ->addTransition('draft', 'draft', ['draft'], 'draft')
      ->addTransition('review', 'review', ['draft'], 'needs_review')
      ->addTransition('publish', 'publish', ['needs_review', 'published'], 'published');
    $moderation_info = $this->prophesize(ModerationInformationInterface::class);
    $moderation_info->getWorkflowForEntity($entity)->willReturn($workflow);
    return $moderation_info->reveal();
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
