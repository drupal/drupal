<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_plugin_elements_subset\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;

class SneakySuperset extends CKEditor5PluginDefault implements CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
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
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'configured_subset' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    return $this->configuration['configured_subset'];
  }

}
