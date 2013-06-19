<?php

/**
 * @file
 * Contains \Drupal\views_ui\ViewDeleteFormController.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides a delete form for a view.
 */
class ViewDeleteFormController extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the %name view?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/structure/views';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);

    $this->entity->delete();
    $form_state['redirect'] = 'admin/structure/views';
  }

}
