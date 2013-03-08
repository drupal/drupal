<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Type\selection\FileSelection.
 */

namespace Drupal\file\Plugin\entity_reference\selection;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase;

/**
 * Provides specific access control for the file entity type.
 *
 * @Plugin(
 *   id = "file_default",
 *   module = "file",
 *   label = @Translation("File selection"),
 *   entity_types = {"file"},
 *   group = "default",
 *   weight = 1
 * )
 */
class FileSelection extends SelectionBase {

  /**
   * Overrides SelectionBase::buildEntityQuery().
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $query->condition('status', FILE_STATUS_PERMANENT);
  }
}
