<?php

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\Exception\RequiredStateMissingException;

/**
 * Tests Workflow type's required states and configuration initialization.
 *
 * @coversDefaultClass \Drupal\workflows\Plugin\WorkflowTypeBase
 *
 * @group workflows
 */
class RequiredStatesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflows', 'workflow_type_test'];

  /**
   * @covers ::getRequiredStates
   * @covers ::initializeWorkflow
   * @covers ::__construct
   */
  public function testGetRequiredStates() {
    $workflow = new Workflow([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ], 'workflow');
    $workflow = $workflow->getTypePlugin()->initializeWorkflow($workflow);
    $workflow->save();
    $this->assertEquals(['fresh', 'rotten'], $workflow->getTypePlugin()
      ->getRequiredStates());

    // Ensure that the workflow has the default configuration.
    $this->assertTrue($workflow->hasState('rotten'));
    $this->assertTrue($workflow->hasState('fresh'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('fresh', 'rotten'));
  }

  /**
   * @covers \Drupal\workflows\Entity\Workflow::preSave
   */
  public function testDeleteRequiredStateAPI() {
    $workflow = new Workflow([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ], 'workflow');
    $workflow = $workflow->getTypePlugin()->initializeWorkflow($workflow);
    $workflow->save();
    // Ensure that required states can't be deleted.
    $this->setExpectedException(RequiredStateMissingException::class, "Required State Type Test' requires states with the ID 'fresh' in workflow 'test'");
    $workflow->deleteState('fresh')->save();
  }

  /**
   * @covers \Drupal\workflows\Entity\Workflow::preSave
   */
  public function testNoStatesRequiredStateAPI() {
    $workflow = new Workflow([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ], 'workflow');
    $this->setExpectedException(RequiredStateMissingException::class, "Required State Type Test' requires states with the ID 'fresh', 'rotten' in workflow 'test'");
    $workflow->save();
  }

  /**
   * Ensures that initialized configuration can be changed.
   */
  public function testChangeRequiredStateAPI() {
    $workflow = new Workflow([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ], 'workflow');
    $workflow = $workflow->getTypePlugin()->initializeWorkflow($workflow);
    $workflow->save();

    // Ensure states added by default configuration can be changed.
    $this->assertEquals('Fresh', $workflow->getState('fresh')->label());
    $workflow
      ->setStateLabel('fresh', 'Fresher')
      ->save();
    $this->assertEquals('Fresher', $workflow->getState('fresh')->label());

    // Ensure transitions can be altered.
    $workflow
      ->addState('cooked', 'Cooked')
      ->setTransitionFromStates('rot', ['fresh', 'cooked'])
      ->save();
    $this->assertTrue($workflow->hasTransitionFromStateToState('fresh', 'rotten'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('cooked', 'rotten'));

    $workflow
      ->setTransitionFromStates('rot', ['cooked'])
      ->save();
    $this->assertFalse($workflow->hasTransitionFromStateToState('fresh', 'rotten'));
    $this->assertTrue($workflow->hasTransitionFromStateToState('cooked', 'rotten'));

    // Ensure the default configuration does not cause ordering issues.
    $workflow->addTransition('cook', 'Cook', ['fresh'], 'cooked')->save();
    $this->assertSame([
      'cooked',
      'fresh',
      'rotten',
    ], array_keys($workflow->get('states')));
    $this->assertSame([
      'cook',
      'rot',
    ], array_keys($workflow->get('transitions')));

    // Ensure that transitions can be deleted.
    $workflow->deleteTransition('rot')->save();
    $this->assertFalse($workflow->hasTransition('rot'));
  }

}
