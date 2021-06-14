<?php

namespace Drupal\Tests\workflows\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\workflow_type_test\Plugin\WorkflowType\TestType;
use Drupal\workflows\State;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * @coversDefaultClass \Drupal\workflows\State
 *
 * @group workflows
 */
class StateTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::id
   * @covers ::label
   * @covers ::weight
   */
  public function testGetters() {
    $state = new State(
      $this->prophesize(WorkflowTypeInterface::class)->reveal(),
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
    $workflow_type = new TestType([], '', []);
    $workflow_type
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $state = $workflow_type->getState('draft');
    $this->assertTrue($state->canTransitionTo('published'));
    $this->assertFalse($state->canTransitionTo('some_other_state'));

    $workflow_type->deleteTransition('publish');
    $this->assertFalse($state->canTransitionTo('published'));
  }

  /**
   * @covers ::getTransitionTo
   */
  public function testGetTransitionTo() {
    $workflow_type = new TestType([], '', []);
    $workflow_type
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $state = $workflow_type->getState('draft');
    $transition = $state->getTransitionTo('published');
    $this->assertEquals('Publish', $transition->label());
  }

  /**
   * @covers ::getTransitionTo
   */
  public function testGetTransitionToException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Can not transition to 'published' state");
    $workflow_type = new TestType([], '', []);
    $workflow_type->addState('draft', 'Draft');
    $state = $workflow_type->getState('draft');
    $state->getTransitionTo('published');
  }

  /**
   * @covers ::getTransitions
   */
  public function testGetTransitions() {
    $workflow_type = new TestType([], '', []);
    $workflow_type
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');
    $state = $workflow_type->getState('draft');
    $transitions = $state->getTransitions();
    $this->assertCount(2, $transitions);
    $this->assertEquals('Create new draft', $transitions['create_new_draft']->label());
    $this->assertEquals('Publish', $transitions['publish']->label());
  }

  /**
   * @covers ::labelCallback
   */
  public function testLabelCallback() {
    $workflow_type = $this->prophesize(WorkflowTypeInterface::class)->reveal();
    $states = [
      new State($workflow_type, 'draft', 'Draft'),
      new State($workflow_type, 'published', 'Published'),
    ];
    $this->assertEquals(['Draft', 'Published'], array_map([State::class, 'labelCallback'], $states));
  }

}
