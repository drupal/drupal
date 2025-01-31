<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler that ignores the argument.
 *
 * @ingroup views_argument_handlers
  */
#[ViewsArgument(
  id: 'null',
)]
class NullArgument extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['must_not_be'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['must_not_be'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fail basic validation if any argument is given'),
      '#default_value' => !empty($this->options['must_not_be']),
      '#description' => $this->t('By checking this field, you can use this to make sure views with more arguments than necessary fail validation.'),
      '#group' => 'options][more',
    ];

    unset($form['exception']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultActions($which = NULL) {
    if ($which) {
      if (in_array($which, ['ignore', 'not found', 'empty', 'default'])) {
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
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {}

}
