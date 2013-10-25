<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatAddFormController.
 */

namespace Drupal\filter;

/**
 * Provides a form controller for adding a filter format.
 */
class FilterFormatAddFormController extends FilterFormatFormControllerBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);
    drupal_set_message(t('Added text format %format.', array('%format' => $this->entity->label())));
    return $this->entity;
  }

}
