<?php

/**
 * @file
 * Definition of views_plugin_pager.
 */

/**
 * @defgroup views_pager_plugins Views pager plugins
 * @{
 * @todo.
 *
 * @see hook_views_plugins()
 */

/**
 * The base plugin to handle pager.
 */
class views_plugin_pager extends views_plugin {
  var $current_page = NULL;
  var $total_items = 0;

  /**
   * Initialize the plugin.
   *
   * @param $view
   *   The view object.
   * @param $display
   *   The display handler.
   */
  function init(&$view, &$display, $options = array()) {
    $this->view = &$view;
    $this->display = &$display;

    $this->unpack_options($this->options, $options);
  }

  /**
   * Get how many items per page this pager will display.
   *
   * All but the leanest pagers should probably return a value here, so
   * most pagers will not need to override this method.
   */
  function get_items_per_page() {
    return isset($this->options['items_per_page']) ? $this->options['items_per_page'] : 0;
  }

  /**
   * Set how many items per page this pager will display.
   *
   * This is mostly used for things that will override the value.
   */
  function set_items_per_page($items) {
    $this->options['items_per_page'] = $items;
  }

  /**
   * Get the page offset, or how many items to skip.
   *
   * Even pagers that don't actually page can skip items at the beginning,
   * so few pagers will need to override this method.
   */
  function get_offset() {
    return isset($this->options['offset']) ? $this->options['offset'] : 0;
  }

  /**
   * Set the page offset, or how many items to skip.
   */
  function set_offset($offset) {
    $this->options['offset'] = $offset;
  }

  /**
   * Get the current page.
   *
   * If NULL, we do not know what the current page is.
   */
  function get_current_page() {
    return $this->current_page;
  }

  /**
   * Set the current page.
   *
   * @param $number
   *   If provided, the page number will be set to this. If NOT provided,
   *   the page number will be set from the global page array.
   */
  function set_current_page($number = NULL) {
    if (!is_numeric($number) || $number < 0) {
      $number = 0;
    }
    $this->current_page = $number;
  }

  /**
   * Get the total number of items.
   *
   * If NULL, we do not yet know what the total number of items are.
   */
  function get_total_items() {
    return $this->total_items;
  }

  /**
   * Get the pager id, if it exists
   */
  function get_pager_id() {
    return isset($this->options['id']) ? $this->options['id'] : 0;
  }

  /**
   * Provide the default form form for validating options
   */
  function options_validate(&$form, &$form_state) { }

  /**
   * Provide the default form form for submitting options
   */
  function options_submit(&$form, &$form_state) { }

  /**
   * Return a string to display as the clickable title for the
   * pager plugin.
   */
  function summary_title() {
    return t('Unknown');
  }

  /**
   * Determine if this pager actually uses a pager.
   *
   * Only a couple of very specific pagers will set this to false.
   */
  function use_pager() {
    return TRUE;
  }

  /**
   * Determine if a pager needs a count query.
   *
   * If a pager needs a count query, a simple query
   */
  function use_count_query() {
    return TRUE;
  }

  /**
   * Execute the count query, which will be done just prior to the query
   * itself being executed.
   */
  function execute_count_query(&$count_query) {
    $this->total_items = $count_query->execute()->fetchField();
    if (!empty($this->options['offset'])) {
      $this->total_items -= $this->options['offset'];
    }

    $this->update_page_info();
    return $this->total_items;
  }

  /**
   * If there are pagers that need global values set, this method can
   * be used to set them. It will be called when the count query is run.
   */
  function update_page_info() {

  }

  /**
   * Modify the query for paging
   *
   * This is called during the build phase and can directly modify the query.
   */
  function query() { }

  /**
   * Perform any needed actions just prior to the query executing.
   */
  function pre_execute(&$query) { }

  /**
   * Perform any needed actions just after the query executing.
   */
  function post_execute(&$result) { }

  /**
   * Perform any needed actions just before rendering.
   */
  function pre_render(&$result) { }

  /**
   * Render the pager.
   *
   * Called during the view render process, this will render the
   * pager.
   *
   * @param $input
   *   Any extra GET parameters that should be retained, such as exposed
   *   input.
   */
  function render($input) { }

  /**
   * Determine if there are more records available.
   *
   * This is primarily used to control the display of a more link.
   */
  function has_more_records() {
    return $this->get_items_per_page()
      && $this->total_items > (intval($this->current_page) + 1) * $this->get_items_per_page();
  }

  function exposed_form_alter(&$form, &$form_state) { }

  function exposed_form_validate(&$form, &$form_state) { }

  function exposed_form_submit(&$form, &$form_state, &$exclude) { }

  function uses_exposed() {
    return FALSE;
  }

  function items_per_page_exposed() {
    return FALSE;
  }

  function offset_exposed() {
    return FALSE;
  }
}

/**
 * @}
 */
