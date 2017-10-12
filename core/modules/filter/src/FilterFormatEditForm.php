<?php

namespace Drupal\filter;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form for adding a filter format.
 *
 * @internal
 */
class FilterFormatEditForm extends FilterFormatFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    if (!$this->entity->status()) {
      throw new NotFoundHttpException();
    }

    $form['#title'] = $this->entity->label();
    $form = parent::form($form, $form_state);
    $form['roles']['#default_value'] = array_keys(filter_get_roles_by_format($this->entity));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('The text format %format has been updated.', ['%format' => $this->entity->label()]));
    return $this->entity;
  }

}
