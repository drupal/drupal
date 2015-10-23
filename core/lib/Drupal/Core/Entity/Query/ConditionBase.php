<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\ConditionBase.
 */

namespace Drupal\Core\Entity\Query;

/**
 * Defines a common base class for all entity condition implementations.
 */
abstract class ConditionBase extends ConditionFundamentals implements ConditionInterface {

  /**
   * {@inheritdoc}
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
      'langcode' => $langcode,
    );

    return $this;
  }
}
