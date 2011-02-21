<?php
// $Id: views_handler_filter_node_tnid.inc,v 1.1 2008/12/02 16:34:24 merlinofchaos Exp $
/**
 * Filter by whether the node is the original translation.
 */
class views_handler_filter_node_tnid extends views_handler_filter {
  function admin_summary() { }
  function option_definition() {
    $options = parent::option_definition();

    $options['operator']['default'] = 1;

    return $options;
  }

  /**
   * Provide simple boolean operator
   */
  function operator_form(&$form, &$form_state) {
    $form['operator'] = array(
      '#type' => 'radios',
      '#title' => t('Include untranslated nodes'),
      '#default_value' => $this->operator,
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
    );
  }

  function can_expose() { return FALSE; }

  function query() {
    $table = $this->ensure_my_table();
    // Select for source translations (tnid = nid). Conditionally, also accept either untranslated nodes (tnid = 0).
    $this->query->add_where($this->options['group'], "$table.tnid = $table.nid" . ($this->operator ? " OR $table.tnid = 0" : ''));
  }
}

