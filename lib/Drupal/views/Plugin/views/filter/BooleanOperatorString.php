<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\filter\BooleanOperatorString.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Annotation\Plugin;

/**
 * Simple filter to handle matching of boolean values.
 *
 * This handler checks to see if a string field is empty (equal to '') or not.
 * It is otherwise identical to the parent operator.
 *
 * Definition items:
 * - label: (REQUIRED) The label for the checkbox.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "boolean_string"
 * )
 */
class BooleanOperatorString extends BooleanOperator {

  public function query() {
    $this->ensureMyTable();
    $where = "$this->tableAlias.$this->realField ";

    if (empty($this->value)) {
      $where .= "= ''";
      if ($this->accept_null) {
        $where = '(' . $where . " OR $this->tableAlias.$this->realField IS NULL)";
      }
    }
    else {
      $where .= "<> ''";
    }
    $this->query->add_where($this->options['group'], $where);
  }

}
