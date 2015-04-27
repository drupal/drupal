<?php

/**
 * @file
 * Contains \Drupal\system\Form\DateFormatEditForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for editing a date format.
 */
class DateFormatEditForm extends DateFormatFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $now = t('Displayed as %date', array('%date' => $this->dateFormatter->format(REQUEST_TIME, $this->entity->id())));
    $form['date_format_pattern']['#field_suffix'] = ' <small data-drupal-date-formatter="preview">' . $now . '</small>';
    $form['date_format_pattern']['#default_value'] = $this->entity->getPattern();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save format');
    unset($actions['delete']);
    return $actions;
  }

}
