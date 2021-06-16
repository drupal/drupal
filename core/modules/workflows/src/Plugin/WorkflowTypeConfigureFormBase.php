<?php

namespace Drupal\workflows\Plugin;

use Drupal\Component\Plugin\PluginAwareInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A base class for workflow type configuration forms.
 */
abstract class WorkflowTypeConfigureFormBase implements PluginFormInterface, PluginAwareInterface {

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

}
