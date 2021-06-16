<?php

namespace Drupal\Core\Entity\Query\Null;

use Drupal\Core\Entity\Query\ConditionBase;

/**
 * Defines the condition class for the null entity query.
 */
class Condition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function compile($query) {
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

}
