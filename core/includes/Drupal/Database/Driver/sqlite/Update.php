<?php

namespace Drupal\Database\Driver\sqlite;

use Drupal\Database\Query\Condition;
use Drupal\Database\Query\ConditionInterface;
use Drupal\Database\Query\Update as QueryUpdate;

/**
 * SQLite specific implementation of UpdateQuery.
 *
 * SQLite counts all the rows that match the conditions as modified, even if they
 * will not be affected by the query. We workaround this by ensuring that
 * we don't select those rows.
 *
 * A query like this one:
 *   UPDATE test SET name = 'newname' WHERE tid = 1
 * will become:
 *   UPDATE test SET name = 'newname' WHERE tid = 1 AND name <> 'newname'
 */
class Update extends QueryUpdate {
  /**
   * Helper function that removes the fields that are already in a condition.
   *
   * @param $fields
   *   The fields.
   * @param QueryConditionInterface $condition
   *   A database condition.
   */
  protected function removeFieldsInCondition(&$fields, ConditionInterface $condition) {
    foreach ($condition->conditions() as $child_condition) {
      if ($child_condition['field'] instanceof ConditionInterface) {
        $this->removeFieldsInCondition($fields, $child_condition['field']);
      }
      else {
        unset($fields[$child_condition['field']]);
      }
    }
  }

  public function execute() {
    if (!empty($this->queryOptions['sqlite_return_matched_rows'])) {
      return parent::execute();
    }

    // Get the fields used in the update query, and remove those that are already
    // in the condition.
    $fields = $this->expressionFields + $this->fields;
    $this->removeFieldsInCondition($fields, $this->condition);

    // Add the inverse of the fields to the condition.
    $condition = new Condition('OR');
    foreach ($fields as $field => $data) {
      if (is_array($data)) {
        // The field is an expression.
        $condition->where($field . ' <> ' . $data['expression']);
        $condition->isNull($field);
      }
      elseif (!isset($data)) {
        // The field will be set to NULL.
        $condition->isNotNull($field);
      }
      else {
        $condition->condition($field, $data, '<>');
        $condition->isNull($field);
      }
    }
    if (count($condition)) {
      $condition->compile($this->connection, $this);
      $this->condition->where((string) $condition, $condition->arguments());
    }
    return parent::execute();
  }

}