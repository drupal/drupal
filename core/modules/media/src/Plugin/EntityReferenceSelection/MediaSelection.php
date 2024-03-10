<?php

namespace Drupal\media\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides specific access control for the media entity type.
 */
#[EntityReferenceSelection(
  id: "default:media",
  label: new TranslatableMarkup("Media selection"),
  entity_types: ["media"],
  group: "default",
  weight: 1
)]
class MediaSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Ensure that users with insufficient permission cannot see unpublished
    // entities.
    if (!$this->currentUser->hasPermission('administer media')) {
      $query->condition('status', 1);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $media = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable media, it needs to published.
    /** @var \Drupal\media\MediaInterface $media */
    $media->setPublished();

    return $media;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer media')) {
      $entities = array_filter($entities, function ($media) {
        /** @var \Drupal\media\MediaInterface $media */
        return $media->isPublished();
      });
    }
    return $entities;
  }

}
