<?php
// $Id: views_handler_sort_search_score.inc,v 1.1 2009/02/23 22:19:47 merlinofchaos Exp $

/**
 * Field handler to provide simple renderer that allows linking to a node.
 */
class views_handler_sort_search_score extends views_handler_sort {
  function query() {
    // Check to see if the search filter added 'score' to the table.
    // Our filter stores it as $handler->search_score -- and we also
    // need to check its relationship to make sure that we're using the same
    // one or obviously this won't work.
    foreach ($this->view->filter as $handler) {
      if (isset($handler->search_score) && $handler->relationship == $this->relationship) {
        $this->query->add_orderby(NULL, NULL, $this->options['order'], $handler->search_score);
        $this->table_alias = $handler->table_alias;
        return;
      }
    }

    // Do absolutely nothing if there is no filter in place; there is no reason to
    // sort on the raw scores with this handler.
  }
}

