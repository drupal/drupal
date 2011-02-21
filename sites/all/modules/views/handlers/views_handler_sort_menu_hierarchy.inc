<?php
// $Id: views_handler_sort_menu_hierarchy.inc,v 1.1 2008/09/03 19:21:28 merlinofchaos Exp $

/**
 * Sort in menu hierarchy order.
 *
 * Given a field name of 'p' this produces an ORDER BY on p1, p2, ..., p9.
 * This is only really useful for the {menu_links} table.
 *
 * @ingroup views_sort_handlers
 */
class views_handler_sort_menu_hierarchy extends views_handler_sort {
  function query() {
    $this->ensure_my_table();
    $max_depth = isset($this->definition['max depth']) ? $this->definition['max depth'] : MENU_MAX_DEPTH;
    for ($i = 1; $i <= $max_depth; ++$i) {
      $this->query->add_orderby($this->table_alias, $this->field . $i, $this->options['order']);
    }
  }
}
