<?php

declare(strict_types=1);

namespace Drupal\workflow_type_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\Plugin\WorkflowTypeConfigureFormBase;

/**
 * Form to configure the complex test workflow type.
 *
 * @see \Drupal\workflow_type_test\Plugin\WorkflowType\ComplexTestType
 */
class ComplexTestTypeConfigureForm extends WorkflowTypeConfigureFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $type_configuration = $this->workflowType->getConfiguration();
    $form['example_setting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example global workflow setting'),
      '#description' => $this->t('Extra information added to the workflow'),
      '#default_value' => $type_configuration['example_setting'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $type_configuration = $this->workflowType->getConfiguration();
    $type_configuration['example_setting'] = $form_state->getValue('example_setting');
    $this->workflowType->setConfiguration($type_configuration);
  }

}
