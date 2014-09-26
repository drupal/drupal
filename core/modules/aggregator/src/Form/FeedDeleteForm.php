<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\FeedDeleteForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

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
  public function getCancelUrl() {
    return new Url('aggregator.admin_overview');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $this->logger('aggregator')->notice('Feed %feed deleted.', array('%feed' => $this->entity->label()));
    drupal_set_message($this->t('The feed %feed has been deleted.', array('%feed' => $this->entity->label())));
    $form_state->setRedirect('aggregator.admin_overview');
  }

}
