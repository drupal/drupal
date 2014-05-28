<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchPageEditForm.
 */

namespace Drupal\search\Form;

/**
 * Provides a form for editing a search page.
 */
class SearchPageEditForm extends SearchPageFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save search page');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    parent::save($form, $form_state);

    drupal_set_message($this->t('The %label search page has been updated.', array('%label' => $this->entity->label())));
  }

}
