<?php

/**
 * @file
 * Contains \Drupal\comment\Form\DeleteForm.
 */

namespace Drupal\comment\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the comment delete confirmation form.
 */
class DeleteForm extends EntityNGConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the comment %title?', array('%title' => $this->entity->subject->value));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1987778.
    $actions['cancel']['#href'] = 'node/' . $this->entity->nid->target_id;

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
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

    $form_state['redirect'] = "node/{$this->entity->nid->target_id}";
  }

}
