<?php

namespace Drupal\Core\Action;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurableTrait;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a base implementation for a configurable Action plugin.
 */
abstract class ConfigurableActionBase extends ActionBase implements ConfigurableInterface, DependentPluginInterface, ConfigurablePluginInterface, PluginFormInterface {

  use ConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
