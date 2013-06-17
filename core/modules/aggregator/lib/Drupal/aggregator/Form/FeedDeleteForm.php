<?php
/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedDeleteForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Entity\EntityNGConfirmFormBase;

/**
 * Provides a form for deleting a feed.
 */
class FeedDeleteForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the feed %feed?', array('%feed' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/services/aggregator';
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
    $this->entity->delete();
    watchdog('aggregator', 'Feed %feed deleted.', array('%feed' => $this->entity->label()));
    drupal_set_message(t('The feed %feed has been deleted.', array('%feed' => $this->entity->label())));
    if (arg(0) == 'admin') {
      $form_state['redirect'] = 'admin/config/services/aggregator';
    }
    else {
      $form_state['redirect'] = 'aggregator/sources';
    }
  }

}
