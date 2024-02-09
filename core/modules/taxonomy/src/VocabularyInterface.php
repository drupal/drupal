<?php

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a taxonomy vocabulary entity.
 */
interface VocabularyInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

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
   * Returns the vocabulary description.
   *
   * @return string
   *   The vocabulary description.
   */
  public function getDescription();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

}
