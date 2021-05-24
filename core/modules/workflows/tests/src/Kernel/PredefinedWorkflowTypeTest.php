<?php

namespace Drupal\Tests\workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test a predefined workflow based on something other than configuration.
 *
 * @group workflows
 */
class PredefinedWorkflowTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workflows', 'workflow_type_test'];

  /**
   * Tests a predefined workflow type.
   */
  public function testPredefinedWorkflowType() {
    $workflow = Workflow::create([
      'id' => 'aces',
      'label' => 'Aces Workflow',
      'type' => 'predefined_states_workflow_test_type',
      'transitions' => [
        'bet' => [
          'label' => 'Bet',
          'from' => [
            'pay_blinds',
          ],
          'to' => 'bet',
        ],
        'raise' => [
          'label' => 'Raise',
          'from' => [
            'pay_blinds',
          ],
          'to' => 'raise',
        ],
      ],
    ]);
    $workflow->save();

    // No states configuration is stored for this workflow.
    $configuration = $workflow->getTypePlugin()->getConfiguration();
    $this->assertFalse(isset($configuration['states']));
  }

}
