<?php

namespace Drupal\Tests\workflows\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\workflow_type_test\Plugin\WorkflowType\TestType;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\State;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowTypeManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\workflows\Plugin\WorkflowTypeBase
 *
 * @group workflows
 */
class WorkflowTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a container so that the plugin manager and workflow type can be
    // mocked.
    $container = new ContainerBuilder();
    $workflow_manager = $this->prophesize(WorkflowTypeManager::class);
    $workflow_manager->createInstance('test_type', Argument::any())->willReturn(new TestType([], '', []));
    $container->set('plugin.manager.workflows.type', $workflow_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::addState
   * @covers ::hasState
   */
  public function testAddAndHasState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $this->assertFalse($workflow->getTypePlugin()->hasState('draft'));

    // By default states are ordered in the order added.
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $this->assertTrue($workflow->getTypePlugin()->hasState('draft'));
    $this->assertFalse($workflow->getTypePlugin()->hasState('published'));
    $this->assertEquals(0, $workflow->getTypePlugin()->getState('draft')->weight());
    // Adding a state does not set up a transition to itself.
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'draft'));

    // New states are added with a new weight 1 more than the current highest
    // weight.
    $workflow->getTypePlugin()->addState('published', 'Published');
    $this->assertEquals(1, $workflow->getTypePlugin()->getState('published')->weight());
  }

  /**
   * @covers ::addState
   */
  public function testAddStateException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' already exists in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
  }

  /**
   * @covers ::addState
   */
  public function testAddStateInvalidIdException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state ID 'draft-draft' must contain only lowercase letters, numbers, and underscores");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft-draft', 'Draft');
  }

  /**
   * @covers ::getStates
   */
  public function testGetStates() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // Getting states works when there are none.
    $this->assertArrayEquals([], array_keys($workflow->getTypePlugin()->getStates()));
    $this->assertArrayEquals([], array_keys($workflow->getTypePlugin()->getStates([])));

    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived');

    // States are stored in alphabetical key order.
    $this->assertArrayEquals([
      'archived',
      'draft',
      'published',
    ], array_keys($workflow->getTypePlugin()->getConfiguration()['states']));

    // Ensure we're returning state objects.
    $this->assertInstanceOf(State::class, $workflow->getTypePlugin()->getStates()['draft']);

    // Passing in no IDs returns all states.
    $this->assertArrayEquals(['draft', 'published', 'archived'], array_keys($workflow->getTypePlugin()->getStates()));

    // The order of states is by weight.
    $workflow->getTypePlugin()->setStateWeight('published', -1);
    $this->assertArrayEquals(['published', 'draft', 'archived'], array_keys($workflow->getTypePlugin()->getStates()));

    // The label is also used for sorting if weights are equal.
    $workflow->getTypePlugin()->setStateWeight('archived', 0);
    $this->assertArrayEquals(['published', 'archived', 'draft'], array_keys($workflow->getTypePlugin()->getStates()));

    // You can limit the states returned by passing in states IDs.
    $this->assertArrayEquals(['archived', 'draft'], array_keys($workflow->getTypePlugin()->getStates(['draft', 'archived'])));

    // An empty array does not load all states.
    $this->assertArrayEquals([], array_keys($workflow->getTypePlugin()->getStates([])));
  }

  /**
   * Test numeric IDs when added to a workflow.
   */
  public function testNumericIdSorting() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow_type = $workflow->getTypePlugin();

    $workflow_type->addState('1', 'One');
    $workflow_type->addState('2', 'Two');
    $workflow_type->addState('3', 'ZZZ');
    $workflow_type->addState('4', 'AAA');

    $workflow_type->setStateWeight('1', 1);
    $workflow_type->setStateWeight('2', 2);
    $workflow_type->setStateWeight('3', 3);
    $workflow_type->setStateWeight('4', 3);

    // Ensure numeric states are correctly sorted by weight first, label second.
    $this->assertEquals([1, 2, 4, 3], array_keys($workflow_type->getStates()));
  }

  /**
   * @covers ::getStates
   */
  public function testGetStatesException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'state_that_does_not_exist' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->getStates(['state_that_does_not_exist']);
  }

  /**
   * @covers ::getState
   */
  public function testGetState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');

    // Ensure we're returning state objects and they are set up correctly
    $this->assertInstanceOf(State::class, $workflow->getTypePlugin()->getState('draft'));
    $this->assertEquals('archived', $workflow->getTypePlugin()->getState('archived')->id());
    $this->assertEquals('Archived', $workflow->getTypePlugin()->getState('archived')->label());

    $draft = $workflow->getTypePlugin()->getState('draft');
    $this->assertTrue($draft->canTransitionTo('draft'));
    $this->assertTrue($draft->canTransitionTo('published'));
    $this->assertFalse($draft->canTransitionTo('archived'));
    $this->assertEquals('Publish', $draft->getTransitionTo('published')->label());
    $this->assertEquals(0, $draft->weight());
    $this->assertEquals(1, $workflow->getTypePlugin()->getState('published')->weight());
    $this->assertEquals(2, $workflow->getTypePlugin()->getState('archived')->weight());
  }

  /**
   * @covers ::getState
   */
  public function testGetStateException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'state_that_does_not_exist' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->getState('state_that_does_not_exist');
  }

  /**
   * @covers ::setStateLabel
   */
  public function testSetStateLabel() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $this->assertEquals('Draft', $workflow->getTypePlugin()->getState('draft')->label());
    $workflow->getTypePlugin()->setStateLabel('draft', 'Unpublished');
    $this->assertEquals('Unpublished', $workflow->getTypePlugin()->getState('draft')->label());
  }

  /**
   * @covers ::setStateLabel
   */
  public function testSetStateLabelException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->setStateLabel('draft', 'Draft');
  }

  /**
   * @covers ::setStateWeight
   */
  public function testSetStateWeight() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $this->assertEquals(0, $workflow->getTypePlugin()->getState('draft')->weight());
    $workflow->getTypePlugin()->setStateWeight('draft', -10);
    $this->assertEquals(-10, $workflow->getTypePlugin()->getState('draft')->weight());
  }

  /**
   * @covers ::setStateWeight
   */
  public function testSetStateWeightException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->setStateWeight('draft', 10);
  }

  /**
   * @covers ::setStateWeight
   */
  public function testSetStateWeightNonNumericException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The weight 'foo' must be numeric for state 'Published'.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->setStateWeight('published', 'foo');
  }

  /**
   * @covers ::deleteState
   */
  public function testDeleteState() {
    $workflow_type = new TestType([], '', []);
    $workflow_type
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('create_new_draft', 'Create new draft', ['draft', 'published'], 'draft')
      ->addTransition('archive', 'Archive', ['draft', 'published'], 'archived');
    $this->assertCount(3, $workflow_type->getStates());
    $this->assertCount(3, $workflow_type->getState('published')->getTransitions());
    $workflow_type->deleteState('draft');
    $this->assertFalse($workflow_type->hasState('draft'));
    $this->assertCount(2, $workflow_type->getStates());
    $this->assertCount(2, $workflow_type->getState('published')->getTransitions());
    $workflow_type->deleteState('published');
    $this->assertCount(0, $workflow_type->getTransitions());
  }

  /**
   * @covers ::deleteState
   */
  public function testDeleteStateException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->deleteState('draft');
  }

  /**
   * @covers ::deleteState
   */
  public function testDeleteOnlyStateException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' can not be deleted from workflow as it is the only state");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $workflow->getTypePlugin()->deleteState('draft');
  }

  /**
   * @covers ::addTransition
   * @covers ::hasTransition
   */
  public function testAddTransition() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');

    $this->assertFalse($workflow->getTypePlugin()->getState('draft')->canTransitionTo('published'));
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertTrue($workflow->getTypePlugin()->getState('draft')->canTransitionTo('published'));
    $this->assertEquals(0, $workflow->getTypePlugin()->getTransition('publish')->weight());
    $this->assertTrue($workflow->getTypePlugin()->hasTransition('publish'));
    $this->assertFalse($workflow->getTypePlugin()->hasTransition('draft'));

    $workflow->getTypePlugin()->addTransition('save_publish', 'Save', ['published'], 'published');
    $this->assertEquals(1, $workflow->getTypePlugin()->getTransition('save_publish')->weight());
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionDuplicateException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'publish' already exists in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['published'], 'published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['published'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionInvalidIdException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition ID 'publish-publish' must contain only lowercase letters, numbers, and underscores");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish-publish', 'Publish', ['published'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionMissingFromException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionDuplicateTransitionStatesException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The 'publish' transition already allows 'draft' to 'published' transitions in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft', 'published'], 'published');
    $workflow->getTypePlugin()->addTransition('draft_to_published', 'Publish a draft', ['draft'], 'published');
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionConsistentAfterFromCatch() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    try {
      $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
    }
    catch (\InvalidArgumentException $e) {
    }
    // Ensure that the workflow is not left in an inconsistent state after an
    // exception is thrown from Workflow::setTransitionFromStates() whilst
    // calling Workflow::addTransition().
    $this->assertFalse($workflow->getTypePlugin()->hasTransition('publish'));
  }

  /**
   * @covers ::addTransition
   */
  public function testAddTransitionMissingToException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
  }

  /**
   * @covers ::getTransitions
   * @covers ::setTransitionWeight
   */
  public function testGetTransitions() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // Getting transitions works when there are none.
    $this->assertArrayEquals([], array_keys($workflow->getTypePlugin()->getTransitions()));
    $this->assertArrayEquals([], array_keys($workflow->getTypePlugin()->getTransitions([])));

    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('a', 'A')
      ->addState('b', 'B')
      ->addTransition('a_b', 'A to B', ['a'], 'b')
      ->addTransition('a_a', 'A to A', ['a'], 'a');

    // Transitions are stored in alphabetical key order in configuration.
    $this->assertArrayEquals(['a_a', 'a_b'], array_keys($workflow->getTypePlugin()->getConfiguration()['transitions']));

    // Ensure we're returning transition objects.
    $this->assertInstanceOf(Transition::class, $workflow->getTypePlugin()->getTransitions()['a_a']);

    // Passing in no IDs returns all transitions.
    $this->assertArrayEquals(['a_b', 'a_a'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // The order of states is by weight.
    $workflow->getTypePlugin()->setTransitionWeight('a_a', -1);
    $this->assertArrayEquals(['a_a', 'a_b'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // If all weights are equal it will fallback to labels.
    $workflow->getTypePlugin()->setTransitionWeight('a_a', 0);
    $this->assertArrayEquals(['a_a', 'a_b'], array_keys($workflow->getTypePlugin()->getTransitions()));
    $workflow->getTypePlugin()->setTransitionLabel('a_b', 'A B');
    $this->assertArrayEquals(['a_b', 'a_a'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // You can limit the states returned by passing in states IDs.
    $this->assertArrayEquals(['a_a'], array_keys($workflow->getTypePlugin()->getTransitions(['a_a'])));

    // An empty array does not load all states.
    $this->assertArrayEquals([], array_keys($workflow->getTypePlugin()->getTransitions([])));
  }

  /**
   * @covers ::getTransition
   */
  public function testGetTransition() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');

    // Ensure we're returning state objects and they are set up correctly
    $this->assertInstanceOf(Transition::class, $workflow->getTypePlugin()->getTransition('create_new_draft'));
    $this->assertEquals('publish', $workflow->getTypePlugin()->getTransition('publish')->id());
    $this->assertEquals('Publish', $workflow->getTypePlugin()->getTransition('publish')->label());

    $transition = $workflow->getTypePlugin()->getTransition('publish');
    $this->assertEquals($workflow->getTypePlugin()->getState('draft'), $transition->from()['draft']);
    $this->assertEquals($workflow->getTypePlugin()->getState('published'), $transition->to());
  }

  /**
   * @covers ::getTransition
   */
  public function testGetTransitionException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'transition_that_does_not_exist' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->getTransition('transition_that_does_not_exist');
  }

  /**
   * @covers ::getTransitionsForState
   */
  public function testGetTransitionsForState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['archived', 'draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');

    $this->assertEquals(['create_new_draft', 'publish'], array_keys($workflow->getTypePlugin()->getTransitionsForState('draft')));
    $this->assertEquals(['create_new_draft'], array_keys($workflow->getTypePlugin()->getTransitionsForState('draft', 'to')));
    $this->assertEquals(['publish', 'archive'], array_keys($workflow->getTypePlugin()->getTransitionsForState('published')));
    $this->assertEquals(['publish'], array_keys($workflow->getTypePlugin()->getTransitionsForState('published', 'to')));
    $this->assertEquals(['create_new_draft'], array_keys($workflow->getTypePlugin()->getTransitionsForState('archived', 'from')));
    $this->assertEquals(['archive'], array_keys($workflow->getTypePlugin()->getTransitionsForState('archived', 'to')));
  }

  /**
   * @covers ::getTransitionFromStateToState
   * @covers ::hasTransitionFromStateToState
   */
  public function testGetTransitionFromStateToState() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['archived', 'draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');

    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'published'));
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('archived', 'archived'));
    $transition = $workflow->getTypePlugin()->getTransitionFromStateToState('published', 'archived');
    $this->assertEquals('Archive', $transition->label());
  }

  /**
   * @covers ::getTransitionFromStateToState
   */
  public function testGetTransitionFromStateToStateException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition from 'archived' to 'archived' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['archived', 'draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->addTransition('archive', 'Archive', ['published'], 'archived');

    $workflow->getTypePlugin()->getTransitionFromStateToState('archived', 'archived');
  }

  /**
   * @covers ::setTransitionLabel
   */
  public function testSetTransitionLabel() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertEquals('Publish', $workflow->getTypePlugin()->getTransition('publish')->label());
    $workflow->getTypePlugin()->setTransitionLabel('publish', 'Publish!');
    $this->assertEquals('Publish!', $workflow->getTypePlugin()->getTransition('publish')->label());
  }

  /**
   * @covers ::setTransitionLabel
   */
  public function testSetTransitionLabelException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'draft-published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->setTransitionLabel('draft-published', 'Publish');
  }

  /**
   * @covers ::setTransitionWeight
   */
  public function testSetTransitionWeight() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertEquals(0, $workflow->getTypePlugin()->getTransition('publish')->weight());
    $workflow->getTypePlugin()->setTransitionWeight('publish', 10);
    $this->assertEquals(10, $workflow->getTypePlugin()->getTransition('publish')->weight());
  }

  /**
   * @covers ::setTransitionWeight
   */
  public function testSetTransitionWeightException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'draft-published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->setTransitionWeight('draft-published', 10);
  }

  /**
   * @covers ::setTransitionWeight
   */
  public function testSetTransitionWeightNonNumericException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The weight 'foo' must be numeric for transition 'Publish'.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', [], 'published');
    $workflow->getTypePlugin()->setTransitionWeight('publish', 'foo');
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStates() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('test', 'Test', ['draft'], 'draft');

    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'draft'));
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('archived', 'draft'));
    $workflow->getTypePlugin()->setTransitionFromStates('test', ['draft', 'published', 'archived']);
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'draft'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('archived', 'draft'));
    $workflow->getTypePlugin()->setTransitionFromStates('test', ['published', 'archived']);
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('draft', 'draft'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('published', 'draft'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('archived', 'draft'));
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStatesMissingTransition() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'test' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft');

    $workflow->getTypePlugin()->setTransitionFromStates('test', ['draft', 'published', 'archived']);
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStatesMissingState() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft');

    $workflow->getTypePlugin()->setTransitionFromStates('create_new_draft', ['draft', 'published', 'archived']);
  }

  /**
   * @covers ::setTransitionFromStates
   */
  public function testSetTransitionFromStatesAlreadyExists() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The 'create_new_draft' transition already allows 'draft' to 'draft' transitions in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('archived', 'Archived')
      ->addState('needs_review', 'Needs Review')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('needs_review', 'Needs review', ['needs_review'], 'draft');

    $workflow->getTypePlugin()->setTransitionFromStates('needs_review', ['draft']);
  }

  /**
   * @covers ::deleteTransition
   */
  public function testDeleteTransition() {
    $workflow_type = new TestType([], '', []);
    $workflow_type
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $this->assertTrue($workflow_type->getState('draft')->canTransitionTo('published'));
    $workflow_type->deleteTransition('publish');
    $this->assertFalse($workflow_type->getState('draft')->canTransitionTo('published'));
    $this->assertTrue($workflow_type->getState('draft')->canTransitionTo('draft'));
  }

  /**
   * @covers ::deleteTransition
   */
  public function testDeleteTransitionException() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'draft-published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->deleteTransition('draft-published');
  }

  /**
   * @covers \Drupal\workflows\Entity\Workflow::status
   */
  public function testStatus() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $this->assertFalse($workflow->status());
    $workflow->getTypePlugin()->addState('published', 'Published');
    $this->assertTrue($workflow->status());
  }

}
