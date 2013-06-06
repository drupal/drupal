<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ConfigurableActionBase.
 */

namespace Drupal\Core\Action;

use Drupal\Core\Action\ConfigurableActionInterface;
use Drupal\Core\Action\ActionBase;

/**
 * Provides a base implementation for a configurable Action plugin.
 */
abstract class ConfigurableActionBase extends ActionBase implements ConfigurableActionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->getDefaultConfiguration();
  }

  /**
   * Returns default configuration for this action.
   *
   * @return array
   */
  protected function getDefaultConfiguration() {
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
  public function validate(array &$form, array &$form_state) {
  }

}
