<?php

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a taxonomy vocabulary entity.
 */
interface VocabularyInterface extends ConfigEntityInterface {

  /**
   * Denotes that no term in the vocabulary has a parent.
   */
  const HIERARCHY_DISABLED = 0;

  /**
   * Denotes that one or more terms in the vocabulary has a single parent.
   */
  const HIERARCHY_SINGLE = 1;

  /**
   * Denotes that one or more terms in the vocabulary have multiple parents.
   */
  const HIERARCHY_MULTIPLE = 2;

  /**
   * Returns the vocabulary hierarchy.
   *
   * @return int
   *   The vocabulary hierarchy.
   *
   * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
   *   \Drupal\taxonomy\TermStorage::getVocabularyHierarchyType() instead.
   */
  public function getHierarchy();

  /**
   * Sets the vocabulary hierarchy.
   *
   * @param int $hierarchy
   *   The hierarchy type of vocabulary.
   *   Possible values:
   *    - VocabularyInterface::HIERARCHY_DISABLED: No parents.
   *    - VocabularyInterface::HIERARCHY_SINGLE: Single parent.
   *    - VocabularyInterface::HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @return $this
   *
   * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Reset
   *   the cache of the taxonomy_term storage handler instead.
   */
  public function setHierarchy($hierarchy);

  /**
   * Returns the vocabulary description.
   *
   * @return string
   *   The vocabulary description.
   */
  public function getDescription();

}
