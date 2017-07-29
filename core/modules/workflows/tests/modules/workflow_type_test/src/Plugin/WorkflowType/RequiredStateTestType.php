<?php

namespace Drupal\workflow_type_test\Plugin\WorkflowType;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\WorkflowInterface;

/**
 * Test workflow type.
 *
 * @WorkflowType(
 *   id = "workflow_type_required_state_test",
 *   label = @Translation("Required State Type Test"),
 *   required_states = {
 *     "fresh",
 *     "rotten",
 *   }
 * )
 */
class RequiredStateTestType extends WorkflowTypeBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function initializeWorkflow(WorkflowInterface $workflow) {
    $workflow
      ->getTypePlugin()
      ->addState('fresh', $this->t('Fresh'))
      ->setStateWeight('fresh', -5)
      ->addState('rotten', $this->t('Rotten'))
      ->addTransition('rot', $this->t('Rot'), ['fresh'], 'rotten');
    return $workflow;
  }

}
