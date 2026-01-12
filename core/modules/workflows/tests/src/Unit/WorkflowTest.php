<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\workflow_type_test\Plugin\WorkflowType\TestType;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\State;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowTypeManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\Argument;

/**
 * Tests Drupal\workflows\Plugin\WorkflowTypeBase.
 */
#[CoversClass(WorkflowTypeBase::class)]
#[Group('workflows')]
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
   * Tests add and has state.
   *
   * @legacy-covers ::addState
   * @legacy-covers ::hasState
   */
  public function testAddAndHasState(): void {
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
   * Tests add state exception.
   */
  public function testAddStateException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' already exists in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
  }

  /**
   * Tests add state invalid id exception.
   */
  public function testAddStateInvalidIdException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state ID 'draft-draft' must contain only lowercase letters, numbers, and underscores");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft-draft', 'Draft');
  }

  /**
   * Tests get states.
   */
  public function testGetStates(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // Getting states works when there are none.
    $this->assertSame([], $workflow->getTypePlugin()->getStates());
    $this->assertSame([], $workflow->getTypePlugin()->getStates([]));

    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived');

    // States are stored in alphabetical key order.
    $this->assertEquals([
      'archived',
      'draft',
      'published',
    ], array_keys($workflow->getTypePlugin()->getConfiguration()['states']));

    // Ensure we're returning state objects.
    $this->assertInstanceOf(State::class, $workflow->getTypePlugin()->getStates()['draft']);

    // Passing in no IDs returns all states.
    $this->assertEquals(['draft', 'published', 'archived'], array_keys($workflow->getTypePlugin()->getStates()));

    // The order of states is by weight.
    $workflow->getTypePlugin()->setStateWeight('published', -1);
    $this->assertEquals(['published', 'draft', 'archived'], array_keys($workflow->getTypePlugin()->getStates()));

    // The label is also used for sorting if weights are equal.
    $workflow->getTypePlugin()->setStateWeight('archived', 0);
    $this->assertEquals(['published', 'archived', 'draft'], array_keys($workflow->getTypePlugin()->getStates()));

    // You can limit the states returned by passing in states IDs.
    $this->assertEquals(['archived', 'draft'], array_keys($workflow->getTypePlugin()->getStates(['draft', 'archived'])));

    // An empty array does not load all states.
    $this->assertSame([], $workflow->getTypePlugin()->getStates([]));
  }

  /**
   * Tests numeric IDs when added to a workflow.
   */
  public function testNumericIdSorting(): void {
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
   * Tests get states exception.
   */
  public function testGetStatesException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'state_that_does_not_exist' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->getStates(['state_that_does_not_exist']);
  }

  /**
   * Tests get state.
   */
  public function testGetState(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');

    // Ensure we're returning state objects and they are set up correctly.
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
   * Tests get state exception.
   */
  public function testGetStateException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'state_that_does_not_exist' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->getState('state_that_does_not_exist');
  }

  /**
   * Tests set state label.
   */
  public function testSetStateLabel(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $this->assertEquals('Draft', $workflow->getTypePlugin()->getState('draft')->label());
    $workflow->getTypePlugin()->setStateLabel('draft', 'Unpublished');
    $this->assertEquals('Unpublished', $workflow->getTypePlugin()->getState('draft')->label());
  }

  /**
   * Tests set state label exception.
   */
  public function testSetStateLabelException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->setStateLabel('draft', 'Draft');
  }

  /**
   * Tests set state weight.
   */
  public function testSetStateWeight(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $this->assertEquals(0, $workflow->getTypePlugin()->getState('draft')->weight());
    $workflow->getTypePlugin()->setStateWeight('draft', -10);
    $this->assertEquals(-10, $workflow->getTypePlugin()->getState('draft')->weight());
  }

  /**
   * Tests set state weight exception.
   */
  public function testSetStateWeightException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->setStateWeight('draft', 10);
  }

  /**
   * Tests set state weight non numeric exception.
   */
  public function testSetStateWeightNonNumericException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The weight 'foo' must be numeric for state 'Published'.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->setStateWeight('published', 'foo');
  }

  /**
   * Tests delete state.
   */
  public function testDeleteState(): void {
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
   * Tests delete state exception.
   */
  public function testDeleteStateException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->deleteState('draft');
  }

  /**
   * Tests delete only state exception.
   *
   * @legacy-covers ::deleteState
   */
  public function testDeleteOnlyStateException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' can not be deleted from workflow as it is the only state");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $workflow->getTypePlugin()->deleteState('draft');
  }

  /**
   * Tests add transition.
   *
   * @legacy-covers ::addTransition
   * @legacy-covers ::hasTransition
   */
  public function testAddTransition(): void {
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
   * Tests add transition duplicate exception.
   */
  public function testAddTransitionDuplicateException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'publish' already exists in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['published'], 'published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['published'], 'published');
  }

  /**
   * Tests add transition invalid id exception.
   */
  public function testAddTransitionInvalidIdException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition ID 'publish-publish' must contain only lowercase letters, numbers, and underscores");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish-publish', 'Publish', ['published'], 'published');
  }

  /**
   * Tests add transition missing from exception.
   */
  public function testAddTransitionMissingFromException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'draft' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
  }

  /**
   * Tests add transition duplicate transition states exception.
   */
  public function testAddTransitionDuplicateTransitionStatesException(): void {
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
   * Tests add transition consistent after from catch.
   */
  public function testAddTransitionConsistentAfterFromCatch(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    try {
      $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
    }
    catch (\InvalidArgumentException) {
    }
    // Ensure that the workflow is not left in an inconsistent state after an
    // exception is thrown from Workflow::setTransitionFromStates() whilst
    // calling Workflow::addTransition().
    $this->assertFalse($workflow->getTypePlugin()->hasTransition('publish'));
  }

  /**
   * Tests add transition missing to exception.
   */
  public function testAddTransitionMissingToException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The state 'published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('draft', 'Draft');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', ['draft'], 'published');
  }

  /**
   * Tests get transitions.
   *
   * @legacy-covers ::getTransitions
   * @legacy-covers ::setTransitionWeight
   */
  public function testGetTransitions(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');

    // Getting transitions works when there are none.
    $this->assertSame([], $workflow->getTypePlugin()->getTransitions());
    $this->assertSame([], $workflow->getTypePlugin()->getTransitions([]));

    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('a', 'A')
      ->addState('b', 'B')
      ->addTransition('a_b', 'A to B', ['a'], 'b')
      ->addTransition('a_a', 'A to A', ['a'], 'a');

    // Transitions are stored in alphabetical key order in configuration.
    $this->assertEquals(['a_a', 'a_b'], array_keys($workflow->getTypePlugin()->getConfiguration()['transitions']));

    // Ensure we're returning transition objects.
    $this->assertInstanceOf(Transition::class, $workflow->getTypePlugin()->getTransitions()['a_a']);

    // Passing in no IDs returns all transitions.
    $this->assertEquals(['a_b', 'a_a'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // The order of states is by weight.
    $workflow->getTypePlugin()->setTransitionWeight('a_a', -1);
    $this->assertEquals(['a_a', 'a_b'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // If all weights are equal it will fallback to labels.
    $workflow->getTypePlugin()->setTransitionWeight('a_a', 0);
    $this->assertEquals(['a_a', 'a_b'], array_keys($workflow->getTypePlugin()->getTransitions()));
    $workflow->getTypePlugin()->setTransitionLabel('a_b', 'A B');
    $this->assertEquals(['a_b', 'a_a'], array_keys($workflow->getTypePlugin()->getTransitions()));

    // You can limit the states returned by passing in states IDs.
    $this->assertEquals(['a_a'], array_keys($workflow->getTypePlugin()->getTransitions(['a_a'])));

    // An empty array does not load all states.
    $this->assertSame([], $workflow->getTypePlugin()->getTransitions([]));
  }

  /**
   * Tests get transition.
   */
  public function testGetTransition(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    // By default states are ordered in the order added.
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addState('archived', 'Archived')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft')
      ->addTransition('publish', 'Publish', ['draft'], 'published');

    // Ensure we're returning state objects and they are set up correctly.
    $this->assertInstanceOf(Transition::class, $workflow->getTypePlugin()->getTransition('create_new_draft'));
    $this->assertEquals('publish', $workflow->getTypePlugin()->getTransition('publish')->id());
    $this->assertEquals('Publish', $workflow->getTypePlugin()->getTransition('publish')->label());

    $transition = $workflow->getTypePlugin()->getTransition('publish');
    $this->assertEquals($workflow->getTypePlugin()->getState('draft'), $transition->from()['draft']);
    $this->assertEquals($workflow->getTypePlugin()->getState('published'), $transition->to());
  }

  /**
   * Tests get transition exception.
   */
  public function testGetTransitionException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'transition_that_does_not_exist' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->getTransition('transition_that_does_not_exist');
  }

  /**
   * Tests get transitions for state.
   */
  public function testGetTransitionsForState(): void {
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
   * Tests get transition from state to state.
   *
   * @legacy-covers ::getTransitionFromStateToState
   * @legacy-covers ::hasTransitionFromStateToState
   */
  public function testGetTransitionFromStateToState(): void {
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
   * Tests get transition from state to state exception.
   */
  public function testGetTransitionFromStateToStateException(): void {
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
   * Tests set transition label.
   */
  public function testSetTransitionLabel(): void {
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
   * Tests set transition label exception.
   */
  public function testSetTransitionLabelException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'draft-published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->setTransitionLabel('draft-published', 'Publish');
  }

  /**
   * Tests set transition weight.
   */
  public function testSetTransitionWeight(): void {
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
   * Tests set transition weight exception.
   */
  public function testSetTransitionWeightException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'draft-published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->setTransitionWeight('draft-published', 10);
  }

  /**
   * Tests set transition weight non numeric exception.
   */
  public function testSetTransitionWeightNonNumericException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The weight 'foo' must be numeric for transition 'Publish'.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->addTransition('publish', 'Publish', [], 'published');
    $workflow->getTypePlugin()->setTransitionWeight('publish', 'foo');
  }

  /**
   * Tests set transition from states.
   */
  public function testSetTransitionFromStates(): void {
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
   * Tests set transition from states missing transition.
   */
  public function testSetTransitionFromStatesMissingTransition(): void {
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
   * Tests set transition from states missing state.
   */
  public function testSetTransitionFromStatesMissingState(): void {
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
   * Tests set transition from states already exists.
   */
  public function testSetTransitionFromStatesAlreadyExists(): void {
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
   * Tests delete transition.
   */
  public function testDeleteTransition(): void {
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
   * Tests delete transition exception.
   */
  public function testDeleteTransitionException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The transition 'draft-published' does not exist in workflow.");
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $workflow->getTypePlugin()->addState('published', 'Published');
    $workflow->getTypePlugin()->deleteTransition('draft-published');
  }

  /**
   * Tests status.
   *
   * @legacy-covers \Drupal\workflows\Entity\Workflow::status
   */
  public function testStatus(): void {
    $workflow = new Workflow(['id' => 'test', 'type' => 'test_type'], 'workflow');
    $this->assertFalse($workflow->status());
    $workflow->getTypePlugin()->addState('published', 'Published');
    $this->assertTrue($workflow->status());
  }

}
