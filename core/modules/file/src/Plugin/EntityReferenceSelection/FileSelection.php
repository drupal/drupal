<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\EntityReferenceSelection\FileSelection.
 */

namespace Drupal\file\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the file entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:file",
 *   label = @Translation("File selection"),
 *   entity_types = {"file"},
 *   group = "default",
 *   weight = 1
 * )
 */
class FileSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Allow referencing :
    // - files with status "permanent"
    // - or files uploaded by the current user (since newly uploaded files only
    //   become "permanent" after the containing entity gets validated and
    //   saved.)
    $query->condition($query->orConditionGroup()
      ->condition('status', FILE_STATUS_PERMANENT)
      ->condition('uid', $this->currentUser->id()));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $file = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable file, it needs to have a "permanent"
    // status.
    /** @var \Drupal\file\FileInterface $file */
    $file->setPermanent();

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    $entities = array_filter($entities, function ($file) {
      /** @var \Drupal\file\FileInterface $file */
      return $file->isPermanent() || $file->getOwnerId() === $this->currentUser->id();
    });
    return $entities;
  }

}
