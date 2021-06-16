<?php

namespace Drupal\block_test\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;

/**
 * Provides a form for a block that is empty.
 */
class EmptyBlockForm extends PluginFormBase {

  /**
   * {@inheritdoc}
   */
  public $plugin;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
