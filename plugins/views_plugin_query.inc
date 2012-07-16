<?php

/**
 * @file
 * Defines the base query class, which is the underlying layer in a View.
 */

/**
 * @defgroup views_query_plugins Views query plugins
 * @{
 * A Views query plugin builds SQL to execute using the Drupal database API.
 *
 * @see hook_views_plugins()
 */

/**
 * Object used to create a SELECT query.
 */
class views_plugin_query extends views_plugin {
  /**
   * A pager plugin that should be provided by the display.
   *
   * @var views_plugin_pager
   */
  var $pager = NULL;

  /**
   * Constructor; Create the basic query object and fill with default values.
   */
  function init($base_table, $base_field, $options) {
    $this->base_table = $base_table;
    $this->base_field = $base_field;
    $this->unpack_options($this->options, $options);
  }

  /**
   * Generate a query and a countquery from all of the information supplied
   * to the object.
   *
   * @param $get_count
   *   Provide a countquery if this is true, otherwise provide a normal query.
   */
  function query($get_count = FALSE) { }

  /**
   * Let modules modify the query just prior to finalizing it.
   *
   * @param view $view
   *   The view which is executed.
   */
  function alter(&$view) {  }

  /**
   * Builds the necessary info to execute the query.
   *
   * @param view $view
   *   The view which is executed.
   */
  function build(&$view) { }

  /**
   * Executes the query and fills the associated view object with according
   * values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->pager['current_page'].
   *
   * $view->result should contain an array of objects. The array must use a
   * numeric index starting at 0.
   *
   * @param view $view
   *   The view which is executed.
   */
  function execute(&$view) {  }

  /**
   * Add a signature to the query, if such a thing is feasible.
   *
   * This signature is something that can be used when perusing query logs to
   * discern where particular queries might be coming from.
   *
   * @param view $view
   *   The view which is executed.
   */
  function add_signature(&$view) { }

  /**
   * Get aggregation info for group by queries.
   *
   * If NULL, aggregation is not allowed.
   */
  function get_aggregation_info() { }

  /**
   * Add settings for the ui.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }

  function options_validate(&$form, &$form_state) { }

  function options_submit(&$form, &$form_state) { }

  function summary_title() {
    return t('Settings');
  }

  /**
   * Set a LIMIT on the query, specifying a maximum number of results.
   */
  function set_limit($limit) {
    $this->limit = $limit;
  }

  /**
   * Set an OFFSET on the query, specifying a number of results to skip
   */
  function set_offset($offset) {
    $this->offset = $offset;
  }

  /**
   * Render the pager, if necessary.
   */
  function render_pager($exposed_input) {
    if (!empty($this->pager) && $this->pager->use_pager()) {
      return $this->pager->render($exposed_input);
    }

    return '';
  }

  /**
   * Create a new grouping for the WHERE or HAVING clause.
   *
   * @param $type
   *   Either 'AND' or 'OR'. All items within this group will be added
   *   to the WHERE clause with this logical operator.
   * @param $group
   *   An ID to use for this group. If unspecified, an ID will be generated.
   * @param $where
   *   'where' or 'having'.
   *
   * @return $group
   *   The group ID generated.
   */
  function set_where_group($type = 'AND', $group = NULL, $where = 'where') {
    // Set an alias.
    $groups = &$this->$where;

    if (!isset($group)) {
      $group = empty($groups) ? 1 : max(array_keys($groups)) + 1;
    }

    // Create an empty group
    if (empty($groups[$group])) {
      $groups[$group] = array('conditions' => array(), 'args' => array());
    }

    $groups[$group]['type'] = strtoupper($type);
    return $group;
  }

  /**
   * Control how all WHERE and HAVING groups are put together.
   *
   * @param $type
   *   Either 'AND' or 'OR'
   */
  function set_group_operator($type = 'AND') {
    $this->group_operator = strtoupper($type);
  }

  /**
   * Returns the according entity objects for the given query results.
   */
  function get_result_entities($results, $relationship = NULL) {
    return FALSE;
  }
}

/**
 * @}
 */
