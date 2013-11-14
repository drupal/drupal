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
    return $this->t('Are you sure you want to delete the %name view?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'views_ui.list',
    );
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
    $form_state['redirect_route']['route_name'] = 'views_ui.list';
  }

}
