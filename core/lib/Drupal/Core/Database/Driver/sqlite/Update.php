<?php

/**
 * @file
 * Definition of Drupal\Core\Database\Driver\sqlite\Update
 */

namespace Drupal\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\Update as QueryUpdate;

/**
 * SQLite specific implementation of UpdateQuery.
 *
 * SQLite counts all the rows that match the conditions as modified, even if they
 * will not be affected by the query. We workaround this by ensuring that
 * we don't select those rows.
 *
 * A query like this one:
 *   UPDATE test SET col1 = 'newcol1', col2 = 'newcol2' WHERE tid = 1
 * will become:
 *   UPDATE test SET col1 = 'newcol1', col2 = 'newcol2' WHERE tid = 1 AND (col1 <> 'newcol1' OR col2 <> 'newcol2')
 */
class Update extends QueryUpdate {
  public function execute() {
    if (!empty($this->queryOptions['sqlite_return_matched_rows'])) {
      return parent::execute();
    }

    // Get the fields used in the update query.
    $fields = $this->expressionFields + $this->fields;

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
