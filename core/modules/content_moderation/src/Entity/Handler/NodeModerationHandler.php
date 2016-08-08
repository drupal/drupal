<?php

namespace Drupal\content_moderation\Entity\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Customizations for node entities.
 */
class NodeModerationHandler extends ModerationHandler {

  /**
   * {@inheritdoc}
   */
  public function onPresave(ContentEntityInterface $entity, $default_revision, $published_state) {
    if ($this->shouldModerate($entity, $published_state)) {
      parent::onPresave($entity, $default_revision, $published_state);
      // Only nodes have a concept of published.
      /** @var \Drupal\node\NodeInterface $entity */
      $entity->setPublished($published_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsEntityFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    $form['revision']['#disabled'] = TRUE;
    $form['revision']['#default_value'] = TRUE;
    $form['revision']['#description'] = $this->t('Revisions are required.');
  }

  /**
   * {@inheritdoc}
   */
  public function enforceRevisionsBundleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    /* @var \Drupal\node\Entity\NodeType $entity */
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity->getThirdPartySetting('content_moderation', 'enabled', FALSE)) {
      // Force the revision checkbox on.
      $form['workflow']['options']['#default_value']['revision'] = 'revision';
      $form['workflow']['options']['revision']['#disabled'] = TRUE;
    }
  }

  /**
   * Check if an entity's default revision and/or state needs adjusting.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param bool $published_state
   *   Whether the state being transitioned to is a published state or not.
   *
   * @return bool
   *   TRUE when either the default revision or the state needs to be updated.
   */
  protected function shouldModerate(ContentEntityInterface $entity, $published_state) {
    // @todo clarify the first condition.
    // First condition is needed so you can add a translation.
    // Second condition checks to see if the published status has changed.
    return $entity->isDefaultTranslation() || $entity->isPublished() !== $published_state;
  }

}
