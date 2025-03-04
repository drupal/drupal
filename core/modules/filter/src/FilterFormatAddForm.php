<?php

namespace Drupal\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for adding a filter format.
 *
 * @internal
 */
class FilterFormatAddForm extends FilterFormatFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Added text format %format.', ['%format' => $this->entity->label()]));
    $form_state->setRedirect('filter.admin_overview');

    return $this->entity;
  }

}
