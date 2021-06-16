<?php

namespace Drupal\workflows\Plugin;

use Drupal\Component\Plugin\PluginAwareInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A base class for workflow type state forms.
 */
abstract class WorkflowTypeStateFormBase implements PluginFormInterface, PluginAwareInterface {

  use StringTranslationTrait;

  /**
   * The workflow type.
   *
   * @var \Drupal\workflows\WorkflowTypeInterface
   */
  protected $workflowType;

  /**
   * {@inheritdoc}
   */
  public function setPlugin(PluginInspectionInterface $plugin) {
    $this->workflowType = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $state = $form_state->get('state');
    $configuration = $this->workflowType->getConfiguration();
    $configuration['states'][$state->id()] = $values + $configuration['states'][$state->id()];
    $this->workflowType->setConfiguration($configuration);
  }

}
