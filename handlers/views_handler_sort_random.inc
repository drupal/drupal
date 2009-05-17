<?php

/**
 * @file
 * Definition of views_handler_sort_random.
 */

/**
 * Handle a random sort.
 *
 * @ingroup views_sort_handlers
 */
class views_handler_sort_random extends views_handler_sort {
  function query() {
    $this->query->add_orderby('rand');
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['order']['#access'] = FALSE;
  }
}
