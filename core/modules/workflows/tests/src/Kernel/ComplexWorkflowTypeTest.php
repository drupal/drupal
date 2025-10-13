<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflow_type_test\Plugin\WorkflowType\ComplexTestType;
use Drupal\workflows\Entity\Workflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Workflow entity tests that require modules or storage.
 */
#[CoversClass(ComplexTestType::class)]
#[Group('workflows')]
#[RunTestsInSeparateProcesses]
class ComplexWorkflowTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workflows', 'workflow_type_test'];

  /**
   * Tests load multiple by type.
   *
   * @legacy-covers \Drupal\workflows\Entity\Workflow::loadMultipleByType
   */
  public function testLoadMultipleByType(): void {
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
