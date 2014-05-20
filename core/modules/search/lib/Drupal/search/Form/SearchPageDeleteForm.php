<?php

/**
 * @file
 * Contains Drupal\search\Form\SearchPageDeleteForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a deletion confirm form for search.
 */
class SearchPageDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %label search page?', array('%label' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('search.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    $form_state['redirect_route'] = $this->getCancelRoute();
    drupal_set_message($this->t('The %label search page has been deleted.', array('%label' => $this->entity->label())));
  }

}
