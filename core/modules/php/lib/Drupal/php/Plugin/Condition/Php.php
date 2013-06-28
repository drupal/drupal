<?php

/**
 * @file
 * Contains \Drupal\php\Plugin\Condition\Php.
 */

namespace Drupal\php\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Condition\Annotation\Condition;
use Drupal\Core\Annotation\Translation;

/**
 * Provides a 'Php' condition.
 *
 * @Condition(
 *   id = "php",
 *   label = @Translation("PHP")
 * )
 */
class Php extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    if (empty($this->configuration['php'])) {
      // Initialize an empty value.
      $this->configuration['php'] = FALSE;
    }
    $form['php'] = array(
      '#type' => 'textarea',
      '#title' => t('When the following PHP return TRUE (experts only)'),
      '#default_value' => $this->configuration['php'],
      '#description' => t('Enter PHP code between <?php ?>. Note that executing incorrect PHP code can break your Drupal site. Return TRUE in order for this condition to evaluate as TRUE.'),
      '#access' => user_access('use PHP for settings')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configuration['php'] = $form_state['values']['php'];
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['php'])) {
      return t('When the given PHP evaluates as @state.',
        array(
          '@state' => !empty($this->configuration['negate']) ? 'FALSE' : 'TRUE'
        )
      );
    }
    else {
      return t('No PHP code has been provided.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return php_eval($this->configuration['php']);
  }

}
