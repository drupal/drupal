<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ConfigurableActionBase.
 */

namespace Drupal\Core\Action;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a base implementation for a configurable Action plugin.
 */
abstract class ConfigurableActionBase extends ActionBase implements ConfigurablePluginInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

}
