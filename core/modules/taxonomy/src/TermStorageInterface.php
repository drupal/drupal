<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermStorageInterface.
*/

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for taxonomy term entity controller classes.
 */
interface TermStorageInterface extends EntityStorageInterface {

  /**
   * Removed reference to terms from term_hierarchy.
   *
   * @param array $tids
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

  /**
   * Finds all parents of a given term ID.
   *
   * @param int $tid
   *   Term ID to retrieve parents for.
   *
   * @return array
   *   An array of term objects which are the parents of the term $tid.
   */
  public function loadParents($tid);

  /**
   * Finds all children of a term ID.
   *
   * @param int $tid
   *   Term ID to retrieve parents for.
   * @param string $vid
   *   An optional vocabulary ID to restrict the child search.
   *
   * @return array
   *   An array of term objects that are the children of the term $tid.
   */
  public function loadChildren($tid, $vid = NULL);

  /**
   * Finds all terms in a given vocabulary ID.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   *
   * @return array
   *   An array of term objects that are the children of the vocabulary $vid.
   */
  public function loadTree($vid);

  /**
   * Count the number of nodes in a given vocabulary ID.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   *
   * @return int
   *   A count of the nodes in a given vocabulary ID.
   */
  public function nodeCount($vid);

  /**
   * Reset the weights for a given vocabulary ID.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   */
  public function resetWeights($vid);

  /**
   * Returns all terms used to tag some given nodes.
   *
   * @param array $nids
   *   Node IDs to retrieve terms for.
   * @param array $vocabs
   *   (optional) A vocabularies array to restrict the term search. Defaults to
   *   empty array.
   * @param string $langcode
   *   (optional) A language code to restrict the term search. Defaults to NULL.
   *
   * @return array
   *   An array of nids and the term entities they were tagged with.
   */
  public function getNodeTerms($nids, $vocabs = array(), $langcode = NULL);

}
