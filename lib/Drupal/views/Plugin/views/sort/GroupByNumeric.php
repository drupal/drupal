<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\sort\GroupByNumeric.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\Core\Annotation\Plugin;

/**
 * Handler for GROUP BY on simple numeric fields.
 *
 * @Plugin(
 *   id = "groupby_numeric"
 * )
 */
class GroupByNumeric extends SortPluginBase {

  public function init(&$view, &$options) {
    parent::init($view, $options);

    // Initialize the original handler.
    $this->handler = views_get_handler($options['table'], $options['field'], 'sort');
    $this->handler->init($view, $options);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->ensureMyTable();

    $params = array(
      'function' => $this->options['group_type'],
    );

    $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order'], NULL, $params);
  }

  public function uiName($short = FALSE) {
    return $this->getField(parent::uiName($short));
  }

}
