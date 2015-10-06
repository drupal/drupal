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
    $query->condition('status', FILE_STATUS_PERMANENT);
    return $query;
  }

}
