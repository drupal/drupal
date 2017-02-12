<?php

namespace Drupal\Tests\workflows\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\State;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowTypeInterface;
use Drupal\workflows\WorkflowTypeManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\workflows\Entity\Workflow
 *
 * @group workflows
 */
class WorkflowTest extends UnitTestCase {

  /**
   * {@inheritdoc}
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
   * @covers ::addState
   * @covers ::hasState
   */
  public function testAddAndHasState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $this->assertFalse($workflow->hasState('draft'));

    // By default states are ordered in the order added.
    $workflow->addState('draft', 'Draft');
    $this->assertTrue($workflow->hasState('draft'));
    $this->assertFalse($workflow->hasState('published'));
    $this->assertEquals(0, $workflow->getState('draft')->weight());
    // Adding a state does not set up a transition to itself.
    $this->assertFalse($workflow->hasTransitionFromStateToState('draft', 'draft'));

    // New states are added with a new weight 1 more than the current highest
    // weight.
    $workflow->addState('published', 'Published');
    $this->assertEquals(1, $workflow->getState('published')->weight());
  }

  /**
   * @covers ::addState
   */
  public function testAddStateException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'draft' already exists in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft', 'Draft');
    $workflow->addState('draft', 'Draft');
  }

  /**
   * @covers ::addState
   */
  public function testAddStateInvalidIdException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state ID 'draft-draft' must contain only lowercase letters, numbers, and underscores");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft-draft', 'Draft');
  }

  /**
   * @covers ::getStates
   */
  public function testGetStates() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // Getting states works when there are none.
    $this->assertArrayEquals([], array_keys($workflow->getStates()));
    $this->assertArrayEquals([], array_keys($workflow->getStates([])));

    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived');

    // States are stored in alphabetical key order.
    $this->assertArrayEquals([
      'archived',
      'draft',
      'published',
    ], array_keys($workflow->get('states')));

    // Ensure we're returning state objects.
    $this->assertInstanceOf(State::class, $workflow->getStates()['draft']);

    // Passing in no IDs returns all states.
    $this->assertArrayEquals(['draft', 'published', 'archived'], array_keys($workflow->getStates()));

    // The order of states is by weight.
    $workflow->setStateWeight('published', -1);
    $this->assertArrayEquals(['published', 'draft', 'archived'], array_keys($workflow->getStates()));

    // The label is also used for sorting if weights are equal.
    $workflow->setStateWeight('archived', 0);
    $this->assertArrayEquals(['published', 'archived', 'draft'], array_keys($workflow->getStates()));

    // You can limit the states returned by passing in states IDs.
    $this->assertArrayEquals(['archived', 'draft'], array_keys($workflow->getStates(['draft', 'archived'])));

    // An empty array does not load all states.
    $this->assertArrayEquals([], array_keys($workflow->getStates([])));
  }

  /**
   * @covers ::getStates
   */
  public function testGetStatesException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'state_that_does_not_exist' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getStates(['state_that_does_not_exist']);
  }

  /**
   * @covers ::getState
   */
  public function testGetState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');

    // Ensure we're returning state objects and they are set up correctly
    $this->assertInstanceOf(State::class, $workflow->getState('draft'));
    $this->assertEquals('archived', $workflow->getState('archived')->id());
    $this->assertEquals('Archived', $workflow->getState('archived')->label());

    $draft = $workflow->getState('draft');
    $this->assertTrue($draft->canTransitionTo('draft'));
    $this->assertTrue($draft->canTransitionTo('published'));
    $this->assertFalse($draft->canTransitionTo('archived'));
    $this->assertEquals('Publish', $draft->getTransitionTo('published')->label());
    $this->assertEquals(0, $draft->weight());
    $this->assertEquals(1, $workflow->getState('published')->weight());
    $this->assertEquals(2, $workflow->getState('archived')->weight());
  }

  /**
   * @covers ::getState
   */
  public function testGetStateException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'state_that_does_not_exist' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getState('state_that_does_not_exist');
  }

  /**
   * @covers ::setStateLabel
   */
  public function testSetStateLabel() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft', 'Draft');
    $this->assertEquals('Draft', $workflow->getState('draft')->label());
    $workflow->setStateLabel('draft', 'Unpublished');
    $this->assertEquals('Unpublished', $workflow->getState('draft')->label());
  }

  /**
   * @covers ::setStateLabel
   */
  public function testSetStateLabelException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'draft' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->setStateLabel('draft', 'Draft');
  }

  /**
   * @covers ::setStateWeight
   */
  public function testSetStateWeight() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft', 'Draft');
    $this->assertEquals(0, $workflow->getState('draft')->weight());
    $workflow->setStateWeight('draft', -10);
    $this->assertEquals(-10, $workflow->getState('draft')->weight());
  }

  /**
   * @covers ::setStateWeight
   */
  public function testSetStateWeightException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'draft' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->setStateWeight('draft', 10);
  }

  /**
   * @covers ::deleteState
   */
  public function testDeleteState() {
    // Create a container so that the plugin manager and workflow type can be
    // mocked and test that
    // \Drupal\workflows\WorkflowTypeInterface::deleteState() is called
    // correctly.
    $container = new ContainerBuilder();
    $workflow_type = $this->prophesize(WorkflowTypeInterface::class);
    $workflow_type->decorateState(Argument::any())->willReturnArgument(0);
    $workflow_type->decorateTransition(Argument::any())->willReturnArgument(0);
    $workflow_type->deleteState('draft')->shouldBeCalled();
    $workflow_type->deleteTransition('create_new_draft')->shouldBeCalled();
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('test_type', Argument::any())->willReturn($workflow_type->reveal());
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);

    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('create_new_draft', 'Create new draft', ['draft', 'published'], 'draft');
    $this->assertCount(2, $workflow->getStates());
    $this->assertCount(2, $workflow->getState('published')->getTransitions());
    $workflow->deleteState('draft');
    $this->assertFalse($workflow->hasState('draft'));
    $this->assertCount(1, $workflow->getStates());
    $this->assertCount(1, $workflow->getState('published')->getTransitions());
  }

  /**
   * @covers ::deleteState
   */
  public function testDeleteStateException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'draft' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->deleteState('draft');
  }

  /**
   * @covers ::deleteState
   */
  public function testDeleteOnlyStateException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'draft' can not be deleted from workflow 'test' as it is the only state");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft', 'Draft');
    $workflow->deleteState('draft');
  }

  /**
   * @covers ::getInitialState
   */
  public function testGetInitialState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived');

    $this->assertEquals('draft', $workflow->getInitialState()->id());

    // Make published the first state.
    $workflow->setStateWeight('published', -1);
    $this->assertEquals('published', $workflow->getInitialState()->id());
  }

  /**
   * @covers ::addTransition
   * @covers ::hasTransition
   */
  public function testAddTransition() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');

    $this->assertFalse($workflow->getState('draft')->canTransitionTo('published'));
    $workflow->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertTrue($workflow->getState('draft')->canTransitionTo('published'));
    $this->assertEquals(0, $workflow->getTransition('publish')->weight());
    $this->assertTrue($workflow->hasTransition('publish'));
    $this->assertFalse($workflow->hasTransition('draft'));

    $workflow->addTransition('save_publish', 'Save', ['published'], 'published');
    $this->assertEquals(1, $workflow->getTransition('save_publish')->weight());
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionDuplicateException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition 'publish' already exists in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    $workflow->addTransition('publish', 'Publish', ['published'], 'published');
    $workflow->addTransition('publish', 'Publish', ['published'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionInvalidIdException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition ID 'publish-publish' must contain only lowercase letters, numbers, and underscores");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    $workflow->addTransition('publish-publish', 'Publish', ['published'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionMissingFromException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'draft' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    $workflow->addTransition('publish', 'Publish', ['draft'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionDuplicateTransitionStatesException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The 'publish' transition already allows 'draft' to 'published' transitions in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');
    $workflow->addTransition('publish', 'Publish', ['draft', 'published'], 'published');
    $workflow->addTransition('draft_to_published', 'Publish a draft', ['draft'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionConsistentAfterFromCatch() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    try {
      $workflow->addTransition('publish', 'Publish', ['draft'], 'published');
    }
    catch (\InvalidArgumentException $e) {
    }
    // Ensure that the workflow is not left in an inconsistent state after an
    // exception is thrown from Workflow::setTransitionFromStates() whilst
    // calling Workflow::addTransition().
    $this->assertFalse($workflow->hasTransition('publish'));
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionMissingToException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'published' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('draft', 'Draft');
    $workflow->addTransition('publish', 'Publish', ['draft'], 'published');
  }

  /**
   * @covers ::getTransitions
   * @covers ::setTransitionWeight
   */
  public function testGetTransitions() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // Getting transitions works when there are none.
    $this->assertArrayEquals([], array_keys($workflow->getTransitions()));
    $this->assertArrayEquals([], array_keys($workflow->getTransitions([])));

    // By default states are ordered in the order added.
    $workflow
      ->addState('a', 'A')
      ->addState('b', 'B')
      ->addTransition('a_b', 'A to B', ['a'], 'b')
      ->addTransition('a_a', 'A to A', ['a'], 'a');

    // Transitions are stored in alphabetical key order in configuration.
    $this->assertArrayEquals(['a_a', 'a_b'], array_keys($workflow->get('transitions')));

    // Ensure we're returning transition objects.
    $this->assertInstanceOf(Transition::class, $workflow->getTransitions()['a_a']);

    // Passing in no IDs returns all transitions.
    $this->assertArrayEquals(['a_b', 'a_a'], array_keys($workflow->getTransitions()));

    // The order of states is by weight.
    $workflow->setTransitionWeight('a_a', -1);
    $this->assertArrayEquals(['a_a', 'a_b'], array_keys($workflow->getTransitions()));

    // If all weights are equal it will fallback to labels.
    $workflow->setTransitionWeight('a_a', 0);
    $this->assertArrayEquals(['a_a', 'a_b'], array_keys($workflow->getTransitions()));
    $workflow->setTransitionLabel('a_b', 'A B');
    $this->assertArrayEquals(['a_b', 'a_a'], array_keys($workflow->getTransitions()));

    // You can limit the states returned by passing in states IDs.
    $this->assertArrayEquals(['a_a'], array_keys($workflow->getTransitions(['a_a'])));

    // An empty array does not load all states.
    $this->assertArrayEquals([], array_keys($workflow->getTransitions([])));
  }


  /**
   * @covers ::getTransition
   */
  public function testGetTransition() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');

    // Ensure we're returning state objects and they are set up correctly
    $this->assertInstanceOf(Transition::class, $workflow->getTransition('create_new_draft'));
    $this->assertEquals('publish', $workflow->getTransition('publish')->id());
    $this->assertEquals('Publish', $workflow->getTransition('publish')->label());

    $transition = $workflow->getTransition('publish');
    $this->assertEquals($workflow->getState('draft'), $transition->from()['draft']);
    $this->assertEquals($workflow->getState('published'), $transition->to());
  }

  /**
   * @covers ::getTransition
   */
  public function testGetTransitionException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition 'transition_that_does_not_exist' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTransition('transition_that_does_not_exist');
  }

  /**
   * @covers ::getTransitionsForState
   */
  public function testGetTransitionsForState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['archived', 'draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');

    $this->assertEquals(['create_new_draft', 'publish'], array_keys($workflow->getTransitionsForState('draft')));
    $this->assertEquals(['create_new_draft'], array_keys($workflow->getTransitionsForState('draft', 'to')));
    $this->assertEquals(['publish', 'archive'], array_keys($workflow->getTransitionsForState('published')));
    $this->assertEquals(['publish'], array_keys($workflow->getTransitionsForState('published', 'to')));
    $this->assertEquals(['create_new_draft'], array_keys($workflow->getTransitionsForState('archived', 'from')));
    $this->assertEquals(['archive'], array_keys($workflow->getTransitionsForState('archived', 'to')));
  }


  /**
   * @covers ::getTransitionFromStateToState
   * @covers ::hasTransitionFromStateToState
   */
  public function testGetTransitionFromStateToState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['archived', 'draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');

    $this->assertTrue($workflow->hasTransitionFromStateToState('draft', 'published'));
    $this->assertFalse($workflow->hasTransitionFromStateToState('archived', 'archived'));
    $transition = $workflow->getTransitionFromStateToState('published', 'archived');
    $this->assertEquals('Archive', $transition->label());
  }

  /**
   * @covers ::getTransitionFromStateToState
   */
  public function testGetTransitionFromStateToStateException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition from 'archived' to 'archived' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['archived', 'draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');

    $workflow->getTransitionFromStateToState('archived', 'archived');
  }

  /**
   * @covers ::setTransitionLabel
   */
  public function testSetTransitionLabel() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertEquals('Publish', $workflow->getTransition('publish')->label());
    $workflow->setTransitionLabel('publish', 'Publish!');
    $this->assertEquals('Publish!', $workflow->getTransition('publish')->label());
  }

  /**
   * @covers ::setTransitionLabel
   */
  public function testSetTransitionLabelException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition 'draft-published' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    $workflow->setTransitionLabel('draft-published', 'Publish');
  }

  /**
   * @covers ::setTransitionWeight
   */
  public function testSetTransitionWeight() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertEquals(0, $workflow->getTransition('publish')->weight());
    $workflow->setTransitionWeight('publish', 10);
    $this->assertEquals(10, $workflow->getTransition('publish')->weight());
  }

  /**
   * @covers ::setTransitionWeight
   */
  public function testSetTransitionWeightException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition 'draft-published' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    $workflow->setTransitionWeight('draft-published', 10);
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStates() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('test', 'Test', ['draft'], 'draft');

    $this->assertTrue($workflow->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertFalse($workflow->hasTransitionFromStateToState('published', 'draft'));
    $this->assertFalse($workflow->hasTransitionFromStateToState('archived', 'draft'));
    $workflow->setTransitionFromStates('test', ['draft', 'published', 'archived']);
    $this->assertTrue($workflow->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('published', 'draft'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('archived', 'draft'));
    $workflow->setTransitionFromStates('test', ['published', 'archived']);
    $this->assertFalse($workflow->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('published', 'draft'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('archived', 'draft'));
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStatesMissingTransition() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition 'test' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft');

    $workflow->setTransitionFromStates('test', ['draft', 'published', 'archived']);
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStatesMissingState() {
    $this->setExpectedException(\InvalidArgumentException::class, "The state 'published' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft');

    $workflow->setTransitionFromStates('create_new_draft', ['draft', 'published', 'archived']);
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStatesAlreadyExists() {
    $this->setExpectedException(\InvalidArgumentException::class, "The 'create_new_draft' transition already allows 'draft' to 'draft' transitions in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('archived', 'Archived')
      ->addState('needs_review', 'Needs Review')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('needs_review', 'Needs review', ['needs_review'], 'draft');

    $workflow->setTransitionFromStates('needs_review', ['draft']);
  }

  /**
   * @covers ::deleteTransition
   */
  public function testDeleteTransition() {
    // Create a container so that the plugin manager and workflow type can be
    // mocked and test that
    // \Drupal\workflows\WorkflowTypeInterface::deleteState() is called
    // correctly.
    $container = new ContainerBuilder();
    $workflow_type = $this->prophesize(WorkflowTypeInterface::class);
    $workflow_type->decorateState(Argument::any())->willReturnArgument(0);
    $workflow_type->decorateTransition(Argument::any())->willReturnArgument(0);
    $workflow_type->deleteTransition('publish')->shouldBeCalled();
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('test_type', Argument::any())->willReturn($workflow_type->reveal());
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);

    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertTrue($workflow->getState('draft')->canTransitionTo('published'));
    $workflow->deleteTransition('publish');
    $this->assertFalse($workflow->getState('draft')->canTransitionTo('published'));
    $this->assertTrue($workflow->getState('draft')->canTransitionTo('draft'));
  }

  /**
   * @covers ::deleteTransition
   */
  public function testDeleteTransitionException() {
    $this->setExpectedException(\InvalidArgumentException::class, "The transition 'draft-published' does not exist in workflow 'test'");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->addState('published', 'Published');
    $workflow->deleteTransition('draft-published');
  }

  /**
   * @covers ::status
   */
  public function testStatus() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $this->assertFalse($workflow->status());
    $workflow->addState('published', 'Published');
    $this->assertTrue($workflow->status());
  }

}
