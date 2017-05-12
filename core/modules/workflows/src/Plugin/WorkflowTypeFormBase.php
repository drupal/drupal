<?php

namespace Drupal\workflows\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\workflows\WorkflowTypeFormInterface;

/**
 * Provides a base class for configurable workflow types.
 *
 * @see \Drupal\workflows\WorkflowTypeFormInterface
 * @see \Drupal\workflows\WorkflowTypeInterface
 * @see \Drupal\workflows\Plugin\WorkflowTypeBase
 * @see plugin_api
 */
abstract class WorkflowTypeFormBase extends WorkflowTypeBase implements WorkflowTypeFormInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
