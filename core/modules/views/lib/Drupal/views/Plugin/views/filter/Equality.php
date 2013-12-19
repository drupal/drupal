<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\filter\Equality.
 */

namespace Drupal\views\Plugin\views\filter;

/**
 * Simple filter to handle equal to / not equal to filters
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("equality")
 */
class Equality extends FilterPluginBase {

  // exposed filter options
  protected $alwaysMultiple = TRUE;

  /**
   * Provide simple equality operator
   */
  public function operatorOptions() {
    return array(
      '=' => t('Is equal to'),
      '!=' => t('Is not equal to'),
    );
  }

  /**
   * Provide a simple textfield for equality
   */
  protected function valueForm(&$form, &$form_state) {
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    );

    if (!empty($form_state['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      if (!isset($form_state['input'][$identifier])) {
        $form_state['input'][$identifier] = $this->value;
      }
    }
  }

}
