<?php

/**
 * @file
 * Contains \Drupal\comment\Form\DeleteForm.
 */

namespace Drupal\comment\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;

/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the comment %title?', array('%title' => $this->entity->subject->value));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
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
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    // Delete the comment and its replies.
    $this->entity->delete();
    drupal_set_message($this->t('The comment and all its replies have been deleted.'));
    watchdog('content', 'Deleted comment @cid and its replies.', array('@cid' => $this->entity->id()));
    // Clear the cache so an anonymous user sees that his comment was deleted.
    Cache::invalidateTags(array('content' => TRUE));

    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
