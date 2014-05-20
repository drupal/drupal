<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewDeleteForm.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Provides a delete form for a view.
 */
class ViewDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %name view?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('views_ui.list');
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
    parent::submit($form, $form_state);

    $this->entity->delete();
    drupal_set_message($this->t('View %name deleted',array('%name' => $this->entity->label())));

    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
