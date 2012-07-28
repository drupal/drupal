<?php

/**
 * @file
 * Definition of Drupal\node\Plugins\views\filter\Status
 */

namespace Drupal\node\Plugin\views\filter;

use Drupal\views\Plugins\views\filter\FilterPluginBase;

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 */
class Status extends FilterPluginBase {
  function admin_summary() { }
  function operator_form(&$form, &$form_state) { }
  function can_expose() { return FALSE; }

  function query() {
    $table = $this->ensure_my_table();
    $this->query->add_where_expression($this->options['group'], "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1) OR ***BYPASS_NODE_ACCESS*** = 1");
  }
}
