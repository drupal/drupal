<?php

namespace Drupal\content_moderation\Plugin\Field;

use Drupal\content_moderation\Entity\ModerationState;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * A computed field that provides a content entity's moderation state.
 *
 * It links content entities to a moderation state configuration entity via a
 * moderation state content entity.
 */
class ModerationStateFieldItemList extends EntityReferenceFieldItemList {

  /**
   * Gets the moderation state entity linked to a content entity revision.
   *
   * @return \Drupal\content_moderation\ModerationStateInterface|null
   *   The moderation state configuration entity linked to a content entity
   *   revision.
   */
  protected function getModerationState() {
    $entity = $this->getEntity();

    if ($entity->id() && $entity->getRevisionId()) {
      $revisions = \Drupal::service('entity.query')->get('content_moderation_state')
        ->condition('content_entity_type_id', $entity->getEntityTypeId())
        ->condition('content_entity_id', $entity->id())
        ->condition('content_entity_revision_id', $entity->getRevisionId())
        ->allRevisions()
        ->sort('revision_id', 'DESC')
        ->execute();

      if ($revision_to_load = key($revisions)) {
        /** @var \Drupal\content_moderation\ContentModerationStateInterface $content_moderation_state */
        $content_moderation_state = \Drupal::entityTypeManager()
          ->getStorage('content_moderation_state')
          ->loadRevision($revision_to_load);

        // Return the correct translation.
        $langcode = $entity->language()->getId();
        if (!$content_moderation_state->hasTranslation($langcode)) {
          $content_moderation_state->addTranslation($langcode);
        }
        if ($content_moderation_state->language()->getId() !== $langcode) {
          $content_moderation_state = $content_moderation_state->getTranslation($langcode);
        }

        return $content_moderation_state->get('moderation_state')->entity;
      }
    }
    // It is possible that the bundle does not exist at this point. For example,
    // the node type form creates a fake Node entity to get default values.
    // @see \Drupal\node\NodeTypeForm::form()
    $bundle_entity = \Drupal::service('content_moderation.moderation_information')
      ->loadBundleEntity($entity->getEntityType()->getBundleEntityType(), $entity->bundle());
    if ($bundle_entity && ($default = $bundle_entity->getThirdPartySetting('content_moderation', 'default_moderation_state'))) {
      return ModerationState::load($default);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    if ($index !== 0) {
      throw new \InvalidArgumentException('An entity can not have multiple moderation states at the same time.');
    }
    // Compute the value of the moderation state.
    if (!isset($this->list[$index]) || $this->list[$index]->isEmpty()) {
      $moderation_state = $this->getModerationState();
      // Do not store NULL values in the static cache.
      if ($moderation_state) {
        $this->list[$index] = $this->createItem($index, ['entity' => $moderation_state]);
      }
    }

    return isset($this->list[$index]) ? $this->list[$index] : NULL;
  }

}
