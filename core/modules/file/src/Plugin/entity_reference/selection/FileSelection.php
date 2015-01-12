<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Type\selection\FileSelection.
 */

namespace Drupal\file\Plugin\entity_reference\selection;

use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;

/**
 * Provides specific access control for the file entity type.
 *
 * @EntityReferenceSelection(
 *   id = "file_default",
 *   label = @Translation("File selection"),
 *   entity_types = {"file"},
 *   group = "default",
 *   weight = 1
 * )
 */
class FileSelection extends SelectionBase {

  /**
   * {@inheritdoc}
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $query->condition('status', FILE_STATUS_PERMANENT);
    return $query;
  }
}
