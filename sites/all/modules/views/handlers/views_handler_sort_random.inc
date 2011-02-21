<?php
// $Id: views_handler_sort_random.inc,v 1.1.6.1 2009/11/02 22:01:25 merlinofchaos Exp $

/**
 * Handle a random sort.
 */
class views_handler_sort_random extends views_handler_sort {
  function query() {
    switch (db_driver()) {
      case 'mysql':
      case 'mysqli':
        $formula = 'RAND()';
        break;
      case 'pgsql':
        $formula = 'RANDOM()';
        break;
    }
    if (!empty($formula)) {
      $this->query->add_orderby(NULL, $formula, $this->options['order'], '_' . $this->field);
    }
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['order']['#access'] = FALSE;
  }
}
