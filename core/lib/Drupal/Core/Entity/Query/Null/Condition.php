<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Null\Condition.
 */

namespace Drupal\Core\Entity\Query\Null;

use Drupal\Core\Entity\Query\ConditionBase;

/**
 * Defines the condition class for the null entity query.
 */
class Condition extends ConditionBase {

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function compile($query) {
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::exists().
   */
  public function exists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::notExists().
   */
  public function notExists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

}
