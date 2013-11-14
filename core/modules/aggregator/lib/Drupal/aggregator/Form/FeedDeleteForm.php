<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedDeleteForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides a form for deleting a feed.
 */
class FeedDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'aggregator.admin_overview',
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
    $this->entity->delete();
    watchdog('aggregator', 'Feed %feed deleted.', array('%feed' => $this->entity->label()));
    drupal_set_message($this->t('The feed %feed has been deleted.', array('%feed' => $this->entity->label())));
    if (arg(0) == 'admin') {
      $form_state['redirect_route']['route_name'] = 'aggregator.admin_overview';
    }
    else {
      $form_state['redirect_route']['route_name'] = 'aggregator.sources';
    }
  }

}
