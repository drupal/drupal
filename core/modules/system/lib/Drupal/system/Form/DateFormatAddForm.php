<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatAddForm.
 */

namespace Drupal\system\Form;

/**
 * Provides a form for adding a date format.
 */
class DateFormatAddForm extends DateFormatFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Add format');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);
    drupal_set_message(t('Custom date format added.'));
  }

}
