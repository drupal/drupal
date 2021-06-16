<?php

namespace Drupal\workflow_type_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Plugin\WorkflowTypeStateFormBase;

/**
 * Form to configure the complex test workflow states.
 *
 * @see \Drupal\workflow_type_test\Plugin\WorkflowType\ComplexTestType
 */
class ComplexTestTypeStateForm extends WorkflowTypeStateFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $state = $form_state->get('state');
    $configuration = $this->workflowType->getConfiguration();
    $form['extra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra'),
      '#description' => $this->t('Extra information added to state'),
      '#default_value' => $state && isset($configuration['states'][$state->id()]['extra']) ? $configuration['states'][$state->id()]['extra'] : '',
    ];
    return $form;
  }

}
