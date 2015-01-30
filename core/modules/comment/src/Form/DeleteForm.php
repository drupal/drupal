<?php

/**
 * @file
 * Contains \Drupal\comment\Form\DeleteForm.
 */

namespace Drupal\comment\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this comment is a reply.
    return $this->entity->get('entity_id')->entity->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any replies to this comment will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The comment and all its replies have been deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('content')->notice('Deleted comment @cid and its replies.', array('@cid' => $this->entity->id()));
  }

}
