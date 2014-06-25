<?php

/**
 * @file
 * Contains \Drupal\Core\Condition\ConditionPluginBase.
 */

namespace Drupal\Core\Condition;

use Drupal\Core\Executable\ExecutablePluginBase;

/**
 * Provides a basis for fulfilling contexts for condition plugins.
 *
 * @see \Drupal\Core\Condition\Annotation\Condition
 * @see \Drupal\Core\Condition\ConditionInterface
 * @see \Drupal\Core\Condition\ConditionManager
 *
 * @ingroup plugin_api
 */
abstract class ConditionPluginBase extends ExecutablePluginBase implements ConditionInterface {

  /**
   * {@inheritdoc}
   */
  public static function contextDefinitions() {
    return [];
  }

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
  public function isNegated() {
    return !empty($this->configuration['negate']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form['negate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Negate the condition.'),
      '#default_value' => $this->configuration['negate'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['negate'] = $form_state['values']['negate'];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->executableManager->execute($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'id' => $this->getPluginId(),
    ) + $this->configuration;
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
    return array(
      'negate' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
