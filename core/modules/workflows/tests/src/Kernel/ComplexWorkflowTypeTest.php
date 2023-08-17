<?php

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Workflow entity tests that require modules or storage.
 *
 * @coversDefaultClass \Drupal\workflow_type_test\Plugin\WorkflowType\ComplexTestType
 *
 * @group workflows
 */
class ComplexWorkflowTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workflows', 'workflow_type_test'];

  /**
   * @covers \Drupal\workflows\Entity\Workflow::loadMultipleByType
   */
  public function testLoadMultipleByType() {
    $workflow1 = Workflow::create([
      'id' => 'test1',
      'label' => 'Test 1',
      'type' => 'workflow_type_complex_test',
    ]);
    $workflow1->save();
    $workflow2 = Workflow::create([
      'id' => 'test2',
      'label' => 'Test 2',
      'type' => 'workflow_type_complex_test',
    ]);
    $workflow2->save();
    $workflow3 = Workflow::create([
      'id' => 'test3',
      'label' => 'Test 3',
      'type' => 'workflow_type_test',
    ]);
    $workflow3->save();

    $this->assertEquals(['test1', 'test2'], array_keys(Workflow::loadMultipleByType('workflow_type_complex_test')));
    $this->assertEquals(['test3'], array_keys(Workflow::loadMultipleByType('workflow_type_test')));
    $this->assertEquals([], Workflow::loadMultipleByType('a_type_that_does_not_exist'));
  }

}
