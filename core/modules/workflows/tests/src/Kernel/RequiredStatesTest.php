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
  protected static $modules = ['workflows', 'workflow_type_test'];

  /**
   * @covers ::getRequiredStates
   * @covers ::__construct
   */
  public function testGetRequiredStates() {
    $workflow = Workflow::create([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ]);
    $workflow->save();
    $this->assertEquals(['fresh', 'rotten'], $workflow->getTypePlugin()
      ->getRequiredStates());

    // Ensure that the workflow has the default configuration.
    $this->assertTrue($workflow->getTypePlugin()->hasState('rotten'));
    $this->assertTrue($workflow->getTypePlugin()->hasState('fresh'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('fresh', 'rotten'));
  }

  /**
   * @covers \Drupal\workflows\Entity\Workflow::preSave
   */
  public function testDeleteRequiredStateAPI() {
    $workflow = Workflow::create([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ]);
    $workflow->save();
    // Ensure that required states can't be deleted.
    $this->expectException(RequiredStateMissingException::class);
    $this->expectExceptionMessage("Required State Type Test' requires states with the ID 'fresh' in workflow 'test'");
    $workflow->getTypePlugin()->deleteState('fresh');
    $workflow->save();
  }

  /**
   * @covers \Drupal\workflows\Entity\Workflow::preSave
   */
  public function testNoStatesRequiredStateAPI() {
    $workflow = Workflow::create([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
      'type_settings' => [
        'states' => [],
      ],
    ]);
    $this->expectException(RequiredStateMissingException::class);
    $this->expectExceptionMessage("Required State Type Test' requires states with the ID 'fresh', 'rotten' in workflow 'test'");
    $workflow->save();
  }

  /**
   * Ensures that initialized configuration can be changed.
   */
  public function testChangeRequiredStateAPI() {
    $workflow = Workflow::create([
      'id' => 'test',
      'type' => 'workflow_type_required_state_test',
    ]);
    $workflow->save();

    // Ensure states added by default configuration can be changed.
    $this->assertEquals('Fresh', $workflow->getTypePlugin()->getState('fresh')->label());
    $workflow
      ->getTypePlugin()
      ->setStateLabel('fresh', 'Fresher');
    $workflow->save();
    $this->assertEquals('Fresher', $workflow->getTypePlugin()->getState('fresh')->label());

    // Ensure transitions can be altered.
    $workflow
      ->getTypePlugin()
      ->addState('cooked', 'Cooked')
      ->setTransitionFromStates('rot', ['fresh', 'cooked']);
    $workflow->save();
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('fresh', 'rotten'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('cooked', 'rotten'));

    $workflow
      ->getTypePlugin()
      ->setTransitionFromStates('rot', ['cooked']);
    $workflow->save();
    $this->assertFalse($workflow->getTypePlugin()->hasTransitionFromStateToState('fresh', 'rotten'));
    $this->assertTrue($workflow->getTypePlugin()->hasTransitionFromStateToState('cooked', 'rotten'));

    // Ensure the default configuration does not cause ordering issues.
    $workflow->getTypePlugin()->addTransition('cook', 'Cook', ['fresh'], 'cooked');
    $workflow->save();
    $this->assertSame([
      'cooked',
      'fresh',
      'rotten',
    ], array_keys($workflow->getTypePlugin()->getConfiguration()['states']));
    $this->assertSame([
      'cook',
      'rot',
    ], array_keys($workflow->getTypePlugin()->getConfiguration()['transitions']));

    // Ensure that transitions can be deleted.
    $workflow->getTypePlugin()->deleteTransition('rot');
    $workflow->save();
    $this->assertFalse($workflow->getTypePlugin()->hasTransition('rot'));
  }

}
