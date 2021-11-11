<?php

namespace Drupal\ckeditor5\Plugin;

/**
 * Provides a trait for configurable CKEditor 5 plugins.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface
 */
trait CKEditor5PluginConfigurableTrait {

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
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

}
