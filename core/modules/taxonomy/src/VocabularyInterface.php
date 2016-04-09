<?php

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a taxonomy vocabulary entity.
 */
interface VocabularyInterface extends ConfigEntityInterface {

  /**
   * Returns the vocabulary hierarchy.
   *
   * @return int
   *   The vocabulary hierarchy.
   */
  public function getHierarchy();

  /**
   * Sets the vocabulary hierarchy.
   *
   * @param int $hierarchy
   *   The hierarchy type of vocabulary.
   *   Possible values:
   *    - TAXONOMY_HIERARCHY_DISABLED: No parents.
   *    - TAXONOMY_HIERARCHY_SINGLE: Single parent.
   *    - TAXONOMY_HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @return $this
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
