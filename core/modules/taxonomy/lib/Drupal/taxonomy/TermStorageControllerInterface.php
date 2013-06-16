<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermStorageControllerInterface.
*/

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Defines a common interface for taxonomy term entity controller classes.
 */
interface TermStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Removed reference to terms from term_hierarchy.
   *
   * @param array
   *   Array of terms that need to be removed from hierarchy.
   */
  public function deleteTermHierarchy($tids);

  /**
   * Updates terms hierarchy information with the hierarchy trail of it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $term
   *   Term entity that needs to be added to term hierarchy information.
   */
  public function updateTermHierarchy(EntityInterface $term);

}
