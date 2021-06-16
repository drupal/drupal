<?php

namespace Drupal\search\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for editing a search page.
 *
 * @internal
 */
class SearchPageEditForm extends SearchPageFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save search page');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('The %label search page has been updated.', ['%label' => $this->entity->label()]));
  }

}
