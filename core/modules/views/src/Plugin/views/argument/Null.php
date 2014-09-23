<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\Null.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;

/**
 * Argument handler that ignores the argument.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("null")
 */
class Null extends ArgumentPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['must_not_be'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Override buildOptionsForm() so that only the relevant options
   * are displayed to the user.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['must_not_be'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Fail basic validation if any argument is given'),
      '#default_value' => !empty($this->options['must_not_be']),
      '#description' => $this->t('By checking this field, you can use this to make sure views with more arguments than necessary fail validation.'),
      '#fieldset' => 'more',
    );

    unset($form['exception']);
  }

  /**
   * Override defaultActions() to remove actions that don't
   * make sense for a null argument.
   */
  protected function defaultActions($which = NULL) {
    if ($which) {
      if (in_array($which, array('ignore', 'not found', 'empty', 'default'))) {
        return parent::defaultActions($which);
      }
      return;
    }
    $actions = parent::defaultActions();
    unset($actions['summary asc']);
    unset($actions['summary desc']);
    return $actions;
  }

  /**
   * Override the behavior of query() to prevent the query
   * from being changed in any way.
   */
  public function query($group_by = FALSE) {}

}
