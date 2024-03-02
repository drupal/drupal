<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\workflows\Attribute\WorkflowType;
use Drupal\workflows\Plugin\WorkflowTypeBase;

/**
 * Test workflow type.
 */
#[WorkflowType(
  id: 'workflow_type_test',
  label: new TranslatableMarkup('Workflow Type Test')
)]
class TestType extends WorkflowTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getRequiredStates() {
    // Normally this is obtained from the annotation but we get from state to
    // allow dynamic testing.
    return \Drupal::state()->get('workflow_type_test.required_states', []);
  }

}
