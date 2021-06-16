<?php

namespace Drupal\workflow_type_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Plugin\WorkflowTypeTransitionFormBase;

/**
 * Form to configure the complex test workflow states.
 *
 * @see \Drupal\workflow_type_test\Plugin\WorkflowType\ComplexTestType
 */
class ComplexTestTypeTransitionForm extends WorkflowTypeTransitionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $transition = $form_state->get('transition');
    $configuration = $this->workflowType->getConfiguration();
    $form['extra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra'),
      '#description' => $this->t('Extra information added to transition'),
      '#default_value' => $transition && isset($configuration['transitions'][$transition->id()]['extra']) ? $configuration['transitions'][$transition->id()]['extra'] : '',
    ];
    return $form;
  }

}
