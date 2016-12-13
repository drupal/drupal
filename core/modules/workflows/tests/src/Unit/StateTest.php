<?php

namespace Drupal\Tests\workflows\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\State;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;
use Drupal\workflows\WorkflowTypeManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\workflows\State
 *
 * @group workflows
 */
class StateTest extends UnitTestCase {

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
    $workflow_type->deleteState(Argument::any())->willReturn(NULL);
    $workflow_type->deleteTransition(Argument::any())->willReturn(NULL);
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('test_type', Argument::any())->willReturn($workflow_type->reveal());
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::__construct
   * @covers ::id
   * @covers ::label
   * @covers ::weight
   */
  public function testGetters() {
    $state = new State(
      $this->prophesize(WorkflowInterface::class)->reveal(),
      'draft',
      'Draft',
      3
    );
    $this->assertEquals('draft', $state->id());
    $this->assertEquals('Draft', $state->label());
    $this->assertEquals(3, $state->weight());
  }

  /**
   * @covers ::canTransitionTo
   */
  public function testCanTransitionTo() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $state = $workflow->getState('draft');
    $this->assertTrue($state->canTransitionTo('published'));
    $this->assertFalse($state->canTransitionTo('some_other_state'));

    $workflow->deleteTransition('publish');
    $this->assertFalse($state->canTransitionTo('published'));
  }

  /**
   * @covers ::getTransitionTo
   */
  public function testGetTransitionTo() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $state = $workflow->getState('draft');
    $transition = $state->getTransitionTo('published');
    $this->assertEquals('Publish', $transition->label());
  }

  /**
   * @covers ::getTransitionTo
   */
  public function testGetTransitionToException() {
    $this->setExpectedException(\InvalidArgumentException::class, "Can not transition to 'published' state");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft', 'Draft');
    $state = $workflow->getState('draft');
    $state->getTransitionTo('published');
  }

  /**
   * @covers ::getTransitions
   */
  public function testGetTransitions() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');
    $state = $workflow->getState('draft');
    $transitions = $state->getTransitions();
    $this->assertCount(2, $transitions);
    $this->assertEquals('Create new draft', $transitions['create_new_draft']->label());
    $this->assertEquals('Publish', $transitions['publish']->label());
  }

  /**
   * @covers ::labelCallback
   */
  public function testLabelCallback() {
    $workflow = $this->prophesize(WorkflowInterface::class)->reveal();
    $states = [
      new State($workflow, 'draft', 'Draft'),
      new State($workflow, 'published', 'Published'),
    ];
    $this->assertEquals(['Draft', 'Published'], array_map([State::class, 'labelCallback'], $states));
  }

}
