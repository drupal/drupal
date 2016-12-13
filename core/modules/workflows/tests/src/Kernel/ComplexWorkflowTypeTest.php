<?php

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflow_type_test\DecoratedState;
use Drupal\workflow_type_test\DecoratedTransition;

/**
 * Workflow entity tests that require modules or storage.
 *
 * @coversDefaultClass \Drupal\workflows\Entity\Workflow
 *
 * @group workflows
 */
class ComplexWorkflowTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflows', 'workflow_type_test'];

  /**
   * Tests a workflow type that decorates transitions and states.
   *
   * @covers ::getState
   * @covers ::getTransition
   */
  public function testComplexType() {
    $workflow = new Workflow(['id' => 'test', 'type' => 'workflow_type_complex_test'], 'workflow');
    $workflow
      ->addState('draft', 'Draft')
      ->addTransition('create_new_draft', 'Create new draft', ['draft'], 'draft');
    $this->assertInstanceOf(DecoratedState::class, $workflow->getState('draft'));
    $this->assertInstanceOf(DecoratedTransition::class, $workflow->getTransition('create_new_draft'));
  }

  /**
   * @covers ::loadMultipleByType
   */
  public function testLoadMultipleByType() {
    $workflow1 = new Workflow(['id' => 'test1', 'type' => 'workflow_type_complex_test'], 'workflow');
    $workflow1->save();
    $workflow2 = new Workflow(['id' => 'test2', 'type' => 'workflow_type_complex_test'], 'workflow');
    $workflow2->save();
    $workflow3 = new Workflow(['id' => 'test3', 'type' => 'workflow_type_test'], 'workflow');
    $workflow3->save();

    $this->assertEquals(['test1', 'test2'], array_keys(Workflow::loadMultipleByType('workflow_type_complex_test')));
    $this->assertEquals(['test3'], array_keys(Workflow::loadMultipleByType('workflow_type_test')));
    $this->assertEquals([], Workflow::loadMultipleByType('a_type_that_does_not_exist'));
  }

}
