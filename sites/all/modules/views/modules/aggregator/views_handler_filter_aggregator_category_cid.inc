<?php
// $Id: views_handler_filter_aggregator_category_cid.inc,v 1.2.4.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * Filter by aggregator category cid
 */
class views_handler_filter_aggregator_category_cid extends views_handler_filter_in_operator {
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();

    $result = db_query('SELECT * FROM {aggregator_category} ORDER BY title');
    foreach ($result as $category) {
      $this->value_options[$category->cid] = $category->title;
    }
  }
}
