<?php

declare(strict_types=1);

namespace Drupal\database_test\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for database_test.
 */
class DatabaseTestHooks {

  /**
   * Implements hook_query_alter().
   */
  #[Hook('query_alter')]
  public function queryAlter(AlterableInterface $query): void {
    if ($query->hasTag('database_test_alter_add_range')) {
      $query->range(0, 2);
    }
    if ($query->hasTag('database_test_alter_add_join')) {
      $people_alias = $query->join('test', 'people', "[test_task].[pid] = [%alias].[id]");
      $query->addField($people_alias, 'name', 'name');
      $query->condition($people_alias . '.id', 2);
    }
    if ($query->hasTag('database_test_alter_change_conditional')) {
      $conditions =& $query->conditions();
      $conditions[0]['value'] = 2;
    }
    if ($query->hasTag('database_test_alter_change_fields')) {
      $fields =& $query->getFields();
      unset($fields['age']);
    }
    if ($query->hasTag('database_test_alter_change_expressions')) {
      $expressions =& $query->getExpressions();
      $expressions['double_age']['expression'] = '[age]*3';
    }
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * Called by DatabaseTestCase::testAlterRemoveRange.
   */
  #[Hook('query_database_test_alter_remove_range_alter')]
  public function queryDatabaseTestAlterRemoveRangeAlter(AlterableInterface $query): void {
    $query->range();
  }

}
