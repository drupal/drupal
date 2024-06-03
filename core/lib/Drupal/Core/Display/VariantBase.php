<?php

namespace Drupal\Core\Display;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a base class for DisplayVariant plugins.
 *
 * @see \Drupal\Core\Display\Attribute\DisplayVariant
 * @see \Drupal\Core\Display\VariantInterface
 * @see \Drupal\Core\Display\VariantManager
 * @see plugin_api
 */
abstract class VariantBase extends PluginBase implements VariantInterface {

  use PluginDependencyTrait;
  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel() {
    return $this->pluginDefinition['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->configuration['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->configuration['weight'] = (int) $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'uuid' => '',
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this display variant.'),
      '#default_value' => $this->label(),
      '#maxlength' => '255',
    ];
    return $form;
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
    $this->configuration['label'] = $form_state->getValue('label');
  }

  /**
   * {@inheritdoc}
   */
  public function access(?AccountInterface $account = NULL) {
    return TRUE;
  }

}
