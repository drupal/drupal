<?php

/**
 * @file
 * Contains \Drupal\Core\Condition\ConditionPluginBase.
 */

namespace Drupal\Core\Condition;

use Drupal\Core\Executable\ExecutablePluginBase;

/**
 * Provides a basis for fulfilling contexts for condition plugins.
 */
abstract class ConditionPluginBase extends ExecutablePluginBase implements ConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $definition = $this->getPluginDefinition();
    return implode('_', array($definition['module'], $definition['id'], 'condition'));
  }

  /**
   * Implements \Drupal\condition\Plugin\ConditionInterface::isNegated().
   */
  public function isNegated() {
    return !empty($this->configuration['negate']);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form['negate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Negate the condition.'),
      '#default_value' => isset($this->configuration['negate']) ? $this->configuration['negate'] : FALSE,
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configuration['negate'] = $form_state['values']['negate'];
  }

  /**
   * Implements \Drupal\Core\Executable\ExecutablePluginBase::execute().
   */
  public function execute() {
    return $this->executableManager->execute($this);
  }

}
