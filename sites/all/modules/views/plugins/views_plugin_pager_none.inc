<?php
// $Id: views_plugin_pager_none.inc,v 1.1.4.3 2010/11/06 17:56:10 dereine Exp $

/**
 * Plugin for views without pagers.
 *
 * @ingroup views_pager_plugins
 */
class views_plugin_pager_none extends views_plugin_pager {
  function summary_title() {
    if (!empty($this->options['offset'])) {
      return t('All items, skip @skip', array('@skip' => $this->options['offset']));
    }
    return t('All items');
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['offset'] = array('default' => 0);

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    $form['offset'] = array(
      '#type' => 'textfield',
      '#title' => t('Offset'),
      '#description' => t('The number of items to skip. For example, if this field is 3, the first 3 items will be skipped and not displayed.'),
      '#default_value' => $this->options['offset'],
    );
  }

  function use_pager() {
    return FALSE;
  }

  function use_count_query() {
    return FALSE;
  }

  function get_items_per_page() {
    return 0;
  }

  function execute_count_query(&$count_query) {
    // If we are displaying all items, never count. But we can update the count in post_execute.
  }

  function post_execute(&$result) {
    $this->total_items = count($result);
  }

  function query() {
    // The only query modifications we might do are offsets.
    if (!empty($this->options['offset'])) {
      $this->view->query->set_offset($this->options['offset']);
    }
  }
}
