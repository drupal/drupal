<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Vocabulary.
 */

namespace Drupal\taxonomy;

use Drupal\entity\Entity;

/**
 * Defines the taxonomy vocabulary entity.
 */
class Vocabulary extends Entity {

  /**
   * The taxonomy vocabulary ID.
   *
   * @var integer
   */
  public $vid;

  /**
   * Name of the vocabulary.
   *
   * @var string
   */
  public $name;

  /**
   * The vocabulary machine name.
   *
   * @var string
   */
  public $machine_name;

  /**
   * Description of the vocabulary.
   *
   * @var string
   */
  public $description;

  /**
   * The type of hierarchy allowed within the vocabulary.
   *
   * Possible values:
   * - TAXONOMY_HIERARCHY_DISABLED: No parents.
   * - TAXONOMY_HIERARCHY_SINGLE: Single parent.
   * - TAXONOMY_HIERARCHY_MULTIPLE: Multiple parents.
   *
   * @var integer
   */
  public $hierarchy = TAXONOMY_HIERARCHY_DISABLED;

  /**
   * The weight of this vocabulary in relation to other vocabularies.
   *
   * @var integer
   */
  public $weight = 0;

  /**
   * Implements Drupal\entity\EntityInterface::id().
   */
  public function id() {
    return $this->vid;
  }
}
