<?php

namespace Drupal\Tests\workflows\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;
use Drupal\workflows\WorkflowTypeManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\workflows\Transition
 *
 * @group workflows
 */
class TransitionTest extends UnitTestCase {

  /**
   * Sets up the Workflow Type manager so that workflow entities can be used.
   */
  protected function setUp() {
    parent::setUp();
    // Create a container so that the plugin manager and workflow type can be
    // mocked.
    $container = new ContainerBuilder();
    $workflow_type = $this->prophesize(WorkflowTypeInterface::class);
    $workflow_type->decorateState(Argument::any())->willReturnArgument(0);
    $workflow_type->decorateTransition(Argument::any())->willReturnArgument(0);
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('test_type', Argument::any())->willReturn($workflow_type->reveal());
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::__construct
   * @covers ::id
   * @covers ::label
   */
  public function testGetters() {
    $state = new Transition(
      $this->prophesize(WorkflowInterface::class)->reveal(),
      'draft_published',
      'Publish',
      ['draft'],
      'published'
    );
    $this->assertEquals('draft_published', $state->id());
    $this->assertEquals('Publish', $state->label());
  }

  /**
   * @covers ::from
   * @covers ::to
   */
  public function testFromAndTo() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $state = $workflow->getState('draft');
    $transition = $state->getTransitionTo('published');
    $this->assertEquals($state, $transition->from()['draft']);
    $this->assertEquals($workflow->getState('published'), $transition->to());
  }

}
