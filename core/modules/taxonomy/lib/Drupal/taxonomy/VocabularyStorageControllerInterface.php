<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyStorageControllerInterface.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigStorageControllerInterface;

/**
 * Defines a common interface for taxonomy vocabulary entity controller classes.
 */
interface VocabularyStorageControllerInterface extends ConfigStorageControllerInterface {

  /**
   * Gets top-level term IDs of vocabularies.
   *
   * @param array $vids
   *   Array of vocabulary IDs.
   *
   * @return array
   *   Array of top-level term IDs.
   */
  public function getToplevelTids($vids);

}
