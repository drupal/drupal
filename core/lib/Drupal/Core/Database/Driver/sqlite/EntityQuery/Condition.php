<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\sqlite\EntityQuery\Condition
 */

namespace Drupal\Core\Database\Driver\sqlite\EntityQuery;

use Drupal\Core\Database\EntityQuery\ConditionInterface as EntityQueryConditionInterface;

/**
 * Implements entity query conditions for SQL databases.
 */
class Condition implements EntityQueryConditionInterface {

  /**
   * {@inheritdoc}
   */
  public static function translateCondition(array &$condition, $case_sensitive) { }

}
