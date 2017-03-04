<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Simple filter to handle equal to / not equal to filters
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("equality")
 */
class Equality extends FilterPluginBase {

  // exposed filter options
  protected $alwaysMultiple = TRUE;

  /**
   * Provide simple equality operator
   */
  public function operatorOptions() {
    return [
      '=' => $this->t('Is equal to'),
      '!=' => $this->t('Is not equal to'),
    ];
  }

  /**
   * Provide a simple textfield for equality
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    ];

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if (!isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
    }
  }

}
