<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyStorageInterface.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines an interface for vocabulary entity storage classes.
 */
interface VocabularyStorageInterface extends ConfigEntityStorageInterface {

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
