<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatEditForm.
 */

namespace Drupal\system\Form;

/**
 * Provides a form controller for editing a date format.
 */
class DateFormatEditForm extends DateFormatFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $now = t('Displayed as %date', array('%date' => $this->dateService->format(REQUEST_TIME, $this->entity->id())));
    $form['date_format_pattern']['#field_suffix'] = ' <small id="edit-date-format-suffix">' . $now . '</small>';
    $form['date_format_pattern']['#default_value'] = $this->entity->getPattern($this->patternType);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save format');
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);
    drupal_set_message(t('Custom date format updated.'));
  }

}
