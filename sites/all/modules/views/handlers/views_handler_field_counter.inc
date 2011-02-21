<?php
// $Id: views_handler_field_counter.inc,v 1.3.4.4 2010/11/05 07:20:54 dereine Exp $

class views_handler_field_counter extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();
    $options['counter_start'] = array('default' => 1);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['counter_start'] = array(
      '#type' => 'textfield',
      '#title' => t('Starting value'),
      '#default_value' => $this->options['counter_start'],
      '#description' => t('Specify the number the counter should start at.'),
      //'#process' => array('ctools_dependent_process'),
      '#size' => 2,
    );
  }

  function query() {
    // do nothing -- to override the parent query.
  }

  function render($values) {
    // Note:  1 is subtracted from the counter start value below because the
    // counter value is incremented by 1 at the end of this function.
    $count = is_numeric($this->options['counter_start']) ? $this->options['counter_start'] - 1 : 0;
    $pager = $this->view->query->pager;
    // Get the base count of the pager.
    if ($pager->use_pager()) {
      $count += ($pager->get_items_per_page() * $pager->get_current_page() + $pager->get_offset());
    }
    // Add the counter for the current site.
    $count += $this->view->row_index + 1;

    return $count;
  }
}
