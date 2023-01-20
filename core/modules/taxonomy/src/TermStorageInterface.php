<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for taxonomy_term entity storage classes.
 */
interface TermStorageInterface extends ContentEntityStorageInterface {

  /**
   * Removed reference to terms from term_hierarchy.
   *
   * @param array $tids
   *   Array of terms that need to be removed from hierarchy.
   *
   * @todo Remove this method in Drupal 9.0.x. Now the parent references are
   *   automatically cleared when deleting a taxonomy term.
   *   https://www.drupal.org/node/2785693
   */
  public function deleteTermHierarchy($tids);

  /**
   * Updates terms hierarchy information with the hierarchy trail of it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $term
   *   Term entity that needs to be added to term hierarchy information.
   *
   * @todo remove this method Drupal 9.0.x. Now the parent references are
   *   automatically updates when a taxonomy term is added/updated.
   *   https://www.drupal.org/node/2785693
   */
  public function updateTermHierarchy(EntityInterface $term);

  /**
   * Finds all parents of a given term ID.
   *
   * @param int $tid
   *   Term ID to retrieve parents for.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of term objects which are the parents of the term $tid.
   */
  public function loadParents($tid);

  /**
   * Finds all ancestors of a given term ID.
   *
   * @param int $tid
   *   Term ID to retrieve ancestors for.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of term objects which are the ancestors of the term $tid.
   */
  public function loadAllParents($tid);

  /**
   * Finds all children of a term ID.
   *
   * @param int $tid
   *   Term ID to retrieve children for.
   * @param string $vid
   *   An optional vocabulary ID to restrict the child search.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of term objects that are the children of the term $tid.
   */
  public function loadChildren($tid, $vid = NULL);

  /**
   * Finds all terms in a given vocabulary ID.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   * @param int $parent
   *   The term ID under which to generate the tree. If 0, generate the tree
   *   for the entire vocabulary.
   * @param int $max_depth
   *   The number of levels of the tree to return. Leave NULL to return all
   *   levels.
   * @param bool $load_entities
   *   If TRUE, a full entity load will occur on the term objects. Otherwise
   *   they are partial objects queried directly from the {taxonomy_term_data}
   *   table to save execution time and memory consumption when listing large
   *   numbers of terms. Defaults to FALSE.
   *
   * @return object[]|\Drupal\taxonomy\TermInterface[]
   *   A numerically indexed array of term objects that are the children of the
   *   vocabulary $vid.
   */
  public function loadTree($vid, $parent = 0, $max_depth = NULL, $load_entities = FALSE);

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
   * @param array $vids
   *   (optional) an array of vocabulary IDs to restrict the term search.
   *   Defaults to empty array.
   * @param string $langcode
   *   (optional) A language code to restrict the term search. Defaults to NULL.
   *
   * @return array
   *   An array of nids and the term entities they were tagged with.
   */
  public function getNodeTerms(array $nids, array $vids = [], $langcode = NULL);

  /**
   * Returns the hierarchy type for a specific vocabulary ID.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve the hierarchy type for.
   *
   * @return int
   *   The vocabulary hierarchy.
   *   Possible values:
   *    - VocabularyInterface::HIERARCHY_DISABLED: No parents.
   *    - VocabularyInterface::HIERARCHY_SINGLE: Single parent.
   *    - VocabularyInterface::HIERARCHY_MULTIPLE: Multiple parents.
   */
  public function getVocabularyHierarchyType($vid);

  /**
   * Gets a list of term IDs with pending revisions.
   *
   * @return int[]
   *   An array of term IDs which have pending revisions, keyed by their
   *   revision IDs.
   *
   * @internal
   */
  public function getTermIdsWithPendingRevisions();

}
