<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\sort\GroupByNumeric.
 */

namespace Drupal\views\Plugin\views\sort;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Handler for GROUP BY on simple numeric fields.
 *
 * @ViewsSort("groupby_numeric")
 */
class GroupByNumeric extends SortPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\HandlerBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Initialize the original handler.
    $this->handler = Views::handlerManager('sort')->getHandler($options);
    $this->handler->init($view, $display, $options);
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->ensureMyTable();

    $params = array(
      'function' => $this->options['group_type'],
    );

    $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order'], NULL, $params);
  }

  public function adminLabel($short = FALSE) {
    return $this->getField(parent::adminLabel($short));
  }

}
