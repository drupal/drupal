<?php

/**
 * @file
 * Defines the various handler objects to help build and display views.
 */

/**
 * Instantiate and construct a new handler
 */
function _views_create_handler($definition, $type = 'handler', $handler_type = NULL) {
//  debug('Instantiating handler ' . $definition['handler']);
  if (empty($definition['handler'])) {
    vpr('_views_create_handler - type: @type - failed: handler has not been provided.',
      array('@type' => isset($handler_type) ? ( $type . '(handler type: ' . $handler_type . ')' ) : $type)
    );
    return;
  }

  // class_exists will automatically load the code file.
  if (!empty($definition['override handler']) &&
      !class_exists($definition['override handler'])) {
    vpr(
      '_views_create_handler - loading override handler @type failed: class @override_handler could not be loaded. ' .
      'Verify the class file has been registered in the corresponding .info-file (files[]).',
      array(
        '@type' => isset($handler_type) ? ( $type . '(handler type: ' . $handler_type . ')' ) : $type,
        '@override_handler' => $definition['override handler']
      )
    );
    return;
  }

  if (!class_exists($definition['handler'])) {
    vpr(
      '_views_create_handler - loading handler @type failed: class @handler could not be loaded. ' .
      'Verify the class file has been registered in the corresponding .info-file (files[]).',
      array(
        '@type' => isset($handler_type) ? ( $type . '(handler type: ' . $handler_type . ')' ) : $type,
        '@handler' => $definition['handler']
      )
    );
    return;
  }

   if (!empty($definition['override handler'])) {
     $handler = new $definition['override handler'];
   }
   else {
     $handler = new $definition['handler'];
   }

  $handler->set_definition($definition);
  if ($type == 'handler') {
    $handler->is_handler = TRUE;
    $handler->handler_type = $handler_type;
  }
  else {
    $handler->is_plugin = TRUE;
    $handler->plugin_type = $type;
    $handler->plugin_name = $definition['name'];
  }

  // let the handler have something like a constructor.
  $handler->construct();

  return $handler;
}

/**
 * Prepare a handler's data by checking defaults and such.
 */
function _views_prepare_handler($definition, $data, $field, $type) {
  foreach (array('group', 'title', 'title short', 'help', 'real field') as $key) {
    if (!isset($definition[$key])) {
      // First check the field level
      if (!empty($data[$field][$key])) {
        $definition[$key] = $data[$field][$key];
      }
      // Then if that doesn't work, check the table level
      elseif (!empty($data['table'][$key])) {
        $definition[$key] = $data['table'][$key];
      }
    }
  }

  return _views_create_handler($definition, 'handler', $type);
}

/**
 * Fetch a handler to join one table to a primary table from the data cache
 */
function views_get_table_join($table, $base_table) {
  $data = views_fetch_data($table);
  if (isset($data['table']['join'][$base_table])) {
    $h = $data['table']['join'][$base_table];
    if (!empty($h['handler']) && class_exists($h['handler'])) {
      $handler = new $h['handler'];
    }
    else {
      $handler = new views_join();
    }

    // Fill in some easy defaults
    $handler->definition = $h;
    if (empty($handler->definition['table'])) {
      $handler->definition['table'] = $table;
    }
    // If this is empty, it's a direct link.
    if (empty($handler->definition['left_table'])) {
      $handler->definition['left_table'] = $base_table;
    }

    if (isset($h['arguments'])) {
      call_user_func_array(array(&$handler, 'construct'), $h['arguments']);
    }
    else {
      $handler->construct();
    }

    return $handler;
  }

  // DEBUG -- identify missing handlers
  vpr("Missing join: @table @base_table", array('@table' => $table, '@base_table' => $base_table));
}

/**
 * Base handler, from which all the other handlers are derived.
 * It creates a common interface to create consistency amongst
 * handlers and data.
 *
 * This class would be abstract in PHP5, but PHP4 doesn't understand that.
 *
 * Definition terms:
 * - table: The actual table this uses; only specify if different from
 *          the table this is attached to.
 * - real field: The actual field this uses; only specify if different from
 *               the field this item is attached to.
 * - group: A text string representing the 'group' this item is attached to,
 *          for display in the UI. Examples: "Node", "Taxonomy", "Comment",
 *          "User", etc. This may be inherited from the parent definition or
 *          the 'table' definition.
 * - title: The title for this handler in the UI. This may be inherited from
 *          the parent definition or the 'table' definition.
 * - help: A more informative string to give to the user to explain what this
 *         field/handler is or does.
 * - access callback: If this field should have access control, this could
 *                    be a function to use. 'user_access' is a common
 *                    function to use here. If not specified, no access
 *                    control is provided.
 * - access arguments: An array of arguments for the access callback.
 */
class views_handler extends views_object {
  /**
   * The top object of a view.
   *
   * @var view
   */
  var $view = NULL;

  /**
   * Where the $query object will reside:
   *
   * @var views_plugin_query
   */
  var $query = NULL;

  /**
   * The type of the handler, for example filter/footer/field.
   */
  var $handler_type = NULL;

  /**
   * The alias of the table of this handler which is used in the query.
   */
  public $table_alias;

  /**
   * The actual field in the database table, maybe different
   * on other kind of query plugins/special handlers.
   */
  var $real_field;

  /**
   * The relationship used for this field.
   */
  var $relationship = NULL;

  /**
   * init the handler with necessary data.
   * @param $view
   *   The $view object this handler is attached to.
   * @param $options
   *   The item from the database; the actual contents of this will vary
   *   based upon the type of handler.
   */
  function init(&$view, &$options) {
    $this->view = &$view;
    $display_id = $this->view->current_display;
    // Check to see if this handler type is defaulted. Note that
    // we have to do a lookup because the type is singular but the
    // option is stored as the plural.

    // If the 'moved to' keyword moved our handler, let's fix that now.
    if (isset($this->actual_table)) {
      $options['table'] = $this->actual_table;
    }

    if (isset($this->actual_field)) {
      $options['field'] = $this->actual_field;
    }

    $types = views_object_types();
    $plural = $this->handler_type;
    if (isset($types[$this->handler_type]['plural'])) {
      $plural = $types[$this->handler_type]['plural'];
    }
    if ($this->view->display_handler->is_defaulted($plural)) {
      $display_id = 'default';
    }

    $this->localization_keys = array(
      $display_id,
      $this->handler_type,
      $options['table'],
      $options['id']
    );

    $this->unpack_options($this->options, $options);

    // This exist on most handlers, but not all. So they are still optional.
    if (isset($options['table'])) {
      $this->table = $options['table'];
    }

    if (isset($this->definition['real field'])) {
      $this->real_field = $this->definition['real field'];
    }

    if (isset($this->definition['field'])) {
      $this->real_field = $this->definition['field'];
    }

    if (isset($options['field'])) {
      $this->field = $options['field'];
      if (!isset($this->real_field)) {
        $this->real_field = $options['field'];
      }
    }

    $this->query = &$view->query;
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['id'] = array('default' => '');
    $options['table'] = array('default' => '');
    $options['field'] = array('default' => '');
    $options['relationship'] = array('default' => 'none');
    $options['group_type'] = array('default' => 'group');
    $options['ui_name'] = array('default' => '');

    return $options;
  }

  /**
   * Return a string representing this handler's name in the UI.
   */
  function ui_name($short = FALSE) {
    if (!empty($this->options['ui_name'])) {
      $title = check_plain($this->options['ui_name']);
      return $title;
    }
    $title = ($short && isset($this->definition['title short'])) ? $this->definition['title short'] : $this->definition['title'];
    return t('!group: !title', array('!group' => $this->definition['group'], '!title' => $title));
  }

  /**
   * Shortcut to get a handler's raw field value.
   *
   * This should be overridden for handlers with formulae or other
   * non-standard fields. Because this takes an argument, fields
   * overriding this can just call return parent::get_field($formula)
   */
  function get_field($field = NULL) {
    if (!isset($field)) {
      if (!empty($this->formula)) {
        $field = $this->get_formula();
      }
      else {
        $field = $this->table_alias . '.' . $this->real_field;
      }
    }

    // If grouping, check to see if the aggregation method needs to modify the field.
    if ($this->view->display_handler->use_group_by()) {
      $this->view->init_query();
      if ($this->query) {
        $info = $this->query->get_aggregation_info();
        if (!empty($info[$this->options['group_type']]['method']) && function_exists($info[$this->options['group_type']]['method'])) {
          return $info[$this->options['group_type']]['method']($this->options['group_type'], $field);
        }
      }
    }

    return $field;
  }

  /**
   * Sanitize the value for output.
   *
   * @param $value
   *   The value being rendered.
   * @param $type
   *   The type of sanitization needed. If not provided, check_plain() is used.
   *
   * @return string
   *   Returns the safe value.
   */
  function sanitize_value($value, $type = NULL) {
    switch ($type) {
      case 'xss':
        $value = filter_xss($value);
        break;
      case 'xss_admin':
        $value = filter_xss_admin($value);
        break;
      case 'url':
        $value = check_url($value);
        break;
      default:
        $value = check_plain($value);
        break;
    }
    return $value;
  }

  /**
   * Transform a string by a certain method.
   *
   * @param $string
   *    The input you want to transform.
   * @param $option
   *    How do you want to transform it, possible values:
   *      - upper: Uppercase the string.
   *      - lower: lowercase the string.
   *      - ucfirst: Make the first char uppercase.
   *      - ucwords: Make each word in the string uppercase.
   *
   * @return string
   *    The transformed string.
   */
  function case_transform($string, $option) {
    global $multibyte;

    switch ($option) {
      default:
        return $string;
      case 'upper':
        return drupal_strtoupper($string);
      case 'lower':
        return drupal_strtolower($string);
      case 'ucfirst':
        return drupal_strtoupper(drupal_substr($string, 0, 1)) . drupal_substr($string, 1);
      case 'ucwords':
        if ($multibyte == UNICODE_MULTIBYTE) {
          return mb_convert_case($string, MB_CASE_TITLE);
        }
        else {
          return ucwords($string);
        }
    }
  }

  /**
   * Validate the options form.
   */
  function options_validate(&$form, &$form_state) { }

  /**
   * Build the options form.
   */
  function options_form(&$form, &$form_state) {
    // Some form elements belong in a fieldset for presentation, but can't
    // be moved into one because of the form_state['values'] hierarchy. Those
    // elements can add a #fieldset => 'fieldset_name' property, and they'll
    // be moved to their fieldset during pre_render.
    $form['#pre_render'][] = 'views_ui_pre_render_add_fieldset_markup';

    $form['ui_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Administrative title'),
      '#description' => t('This title will be displayed on the views edit page instead of the default one. This might be useful if you have the same item twice.'),
      '#default_value' => $this->options['ui_name'],
      '#fieldset' => 'more',
    );

    // This form is long and messy enough that the "Administrative title" option
    // belongs in a "more options" fieldset at the bottom of the form.
    $form['more'] = array(
      '#type' => 'fieldset',
      '#title' => t('More'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => 150,
    );
    // Allow to alter the default values brought into the form.
    drupal_alter('views_handler_options', $this->options, $view);
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function options_submit(&$form, &$form_state) { }

  /**
   * Provides the handler some groupby.
   */
  function use_group_by() {
    return TRUE;
  }
  /**
   * Provide a form for aggregation settings.
   */
  function groupby_form(&$form, &$form_state) {
    $view = &$form_state['view'];
    $display_id = $form_state['display_id'];
    $types = views_object_types();
    $type = $form_state['type'];
    $id = $form_state['id'];

    $form['#title'] = check_plain($view->display[$display_id]->display_title) . ': ';
    $form['#title'] .= t('Configure aggregation settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $this->ui_name()));

    $form['#section'] = $display_id . '-' . $type . '-' . $id;

    $view->init_query();
    $info = $view->query->get_aggregation_info();
    foreach ($info as $id => $aggregate) {
      $group_types[$id] = $aggregate['title'];
    }

    $form['group_type'] = array(
      '#type' => 'select',
      '#title' => t('Aggregation type'),
      '#default_value' => $this->options['group_type'],
      '#description' => t('Select the aggregation function to use on this field.'),
      '#options' => $group_types,
    );
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function groupby_form_submit(&$form, &$form_state) {
    $item =& $form_state['handler']->options;

    $item['group_type'] = $form_state['values']['options']['group_type'];
  }

  /**
   * If a handler has 'extra options' it will get a little settings widget and
   * another form called extra_options.
   */
  function has_extra_options() { return FALSE; }

  /**
   * Provide defaults for the handler.
   */
  function extra_options(&$option) { }

  /**
   * Provide a form for setting options.
   */
  function extra_options_form(&$form, &$form_state) { }

  /**
   * Validate the options form.
   */
  function extra_options_validate($form, &$form_state) { }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function extra_options_submit($form, &$form_state) { }

  /**
   * Determine if a handler can be exposed.
   */
  function can_expose() { return FALSE; }

  /**
   * Set new exposed option defaults when exposed setting is flipped
   * on.
   */
  function expose_options() { }

  /**
   * Get information about the exposed form for the form renderer.
   */
  function exposed_info() { }

  /**
   * Render our chunk of the exposed handler form when selecting
   */
  function exposed_form(&$form, &$form_state) { }

  /**
   * Validate the exposed handler form
   */
  function exposed_validate(&$form, &$form_state) { }

  /**
   * Submit the exposed handler form
   */
  function exposed_submit(&$form, &$form_state) { }

  /**
   * Form for exposed handler options.
   */
  function expose_form(&$form, &$form_state) { }

  /**
   * Validate the options form.
   */
  function expose_validate($form, &$form_state) { }

  /**
   * Perform any necessary changes to the form exposes prior to storage.
   * There is no need for this function to actually store the data.
   */
  function expose_submit($form, &$form_state) { }

  /**
   * Shortcut to display the expose/hide button.
   */
  function show_expose_button(&$form, &$form_state) { }

  /**
   * Shortcut to display the exposed options form.
   */
  function show_expose_form(&$form, &$form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $this->expose_form($form, $form_state);

    // When we click the expose button, we add new gadgets to the form but they
    // have no data in $_POST so their defaults get wiped out. This prevents
    // these defaults from getting wiped out. This setting will only be TRUE
    // during a 2nd pass rerender.
    if (!empty($form_state['force_expose_options'])) {
      foreach (element_children($form['expose']) as $id) {
        if (isset($form['expose'][$id]['#default_value']) && !isset($form['expose'][$id]['#value'])) {
          $form['expose'][$id]['#value'] = $form['expose'][$id]['#default_value'];
        }
      }
    }
  }

  /**
   * Check whether current user has access to this handler.
   *
   * @return boolean
   */
  function access() {
    if (isset($this->definition['access callback']) && function_exists($this->definition['access callback'])) {
      if (isset($this->definition['access arguments']) && is_array($this->definition['access arguments'])) {
        return call_user_func_array($this->definition['access callback'], $this->definition['access arguments']);
      }
      return $this->definition['access callback']();
    }

    return TRUE;
  }

  /**
   * Run before the view is built.
   *
   * This gives all the handlers some time to set up before any handler has
   * been fully run.
   */
  function pre_query() { }

  /**
   * Run after the view is executed, before the result is cached.
   *
   * This gives all the handlers some time to modify values. This is primarily
   * used so that handlers that pull up secondary data can put it in the
   * $values so that the raw data can be utilized externally.
   */
  function post_execute(&$values) { }

  /**
   * Provides a unique placeholders for handlers.
   */
  function placeholder() {
    return $this->query->placeholder($this->options['table'] . '_' . $this->options['field']);
  }

  /**
   * Called just prior to query(), this lets a handler set up any relationship
   * it needs.
   */
  function set_relationship() {
    // Ensure this gets set to something.
    $this->relationship = NULL;

    // Don't process non-existant relationships.
    if (empty($this->options['relationship']) || $this->options['relationship'] == 'none') {
      return;
    }

    $relationship = $this->options['relationship'];

    // Ignore missing/broken relationships.
    if (empty($this->view->relationship[$relationship])) {
      return;
    }

    // Check to see if the relationship has already processed. If not, then we
    // cannot process it.
    if (empty($this->view->relationship[$relationship]->alias)) {
      return;
    }

    // Finally!
    $this->relationship = $this->view->relationship[$relationship]->alias;
  }

  /**
   * Ensure the main table for this handler is in the query. This is used
   * a lot.
   */
  function ensure_my_table() {
    if (!isset($this->table_alias)) {
      if (!method_exists($this->query, 'ensure_table')) {
        vpr(t('Ensure my table called but query has no ensure_table method.'));
        return;
      }
      $this->table_alias = $this->query->ensure_table($this->table, $this->relationship);
    }
    return $this->table_alias;
  }

  /**
   * Provide text for the administrative summary
   */
  function admin_summary() { }

  /**
   * Determine if the argument needs a style plugin.
   *
   * @return TRUE/FALSE
   */
  function needs_style_plugin() { return FALSE; }

  /**
   * Determine if this item is 'exposed', meaning it provides form elements
   * to let users modify the view.
   *
   * @return TRUE/FALSE
   */
  function is_exposed() {
    return !empty($this->options['exposed']);
  }

  /**
   * Take input from exposed handlers and assign to this handler, if necessary.
   */
  function accept_exposed_input($input) { return TRUE; }

  /**
   * If set to remember exposed input in the session, store it there.
   */
  function store_exposed_input($input, $status) { return TRUE; }

  /**
   * Get the join object that should be used for this handler.
   *
   * This method isn't used a great deal, but it's very handy for easily
   * getting the join if it is necessary to make some changes to it, such
   * as adding an 'extra'.
   */
  function get_join() {
    // get the join from this table that links back to the base table.
    // Determine the primary table to seek
    if (empty($this->query->relationships[$this->relationship])) {
      $base_table = $this->query->base_table;
    }
    else {
      $base_table = $this->query->relationships[$this->relationship]['base'];
    }

    $join = views_get_table_join($this->table, $base_table);
    if ($join) {
      return clone $join;
    }
  }

  /**
   * Validates the handler against the complete View.
   *
   * This is called when the complete View is being validated. For validating
   * the handler options form use options_validate().
   *
   * @see views_handler::options_validate()
   *
   * @return
   *   Empty array if the handler is valid; an array of error strings if it is not.
   */
  function validate() { return array(); }

  /**
   * Determine if the handler is considered 'broken', meaning it's a
   * a placeholder used when a handler can't be found.
   */
  function broken() { }
}

/**
 * This many to one helper object is used on both arguments and filters.
 *
 * @todo This requires extensive documentation on how this class is to
 * be used. For now, look at the arguments and filters that use it. Lots
 * of stuff is just pass-through but there are definitely some interesting
 * areas where they interact.
 *
 * Any handler that uses this can have the following possibly additional
 * definition terms:
 * - numeric: If true, treat this field as numeric, using %d instead of %s in
 *            queries.
 *
 */
class views_many_to_one_helper {
  function views_many_to_one_helper(&$handler) {
    $this->handler = &$handler;
  }

  static function option_definition(&$options) {
    $options['reduce_duplicates'] = array('default' => FALSE, 'bool' => TRUE);
  }

  function options_form(&$form, &$form_state) {
    $form['reduce_duplicates'] = array(
      '#type' => 'checkbox',
      '#title' => t('Reduce duplicates'),
      '#description' => t('This filter can cause items that have more than one of the selected options to appear as duplicate results. If this filter causes duplicate results to occur, this checkbox can reduce those duplicates; however, the more terms it has to search for, the less performant the query will be, so use this with caution. Shouldn\'t be set on single-value fields, as it may cause values to disappear from display, if used on an incompatible field.'),
      '#default_value' => !empty($this->handler->options['reduce_duplicates']),
      '#weight' => 4,
    );
  }

  /**
   * Sometimes the handler might want us to use some kind of formula, so give
   * it that option. If it wants us to do this, it must set $helper->formula = TRUE
   * and implement handler->get_formula();
   */
  function get_field() {
    if (!empty($this->formula)) {
      return $this->handler->get_formula();
    }
    else {
      return $this->handler->table_alias . '.' . $this->handler->real_field;
    }
  }

  /**
   * Add a table to the query.
   *
   * This is an advanced concept; not only does it add a new instance of the table,
   * but it follows the relationship path all the way down to the relationship
   * link point and adds *that* as a new relationship and then adds the table to
   * the relationship, if necessary.
   */
  function add_table($join = NULL, $alias = NULL) {
    // This is used for lookups in the many_to_one table.
    $field = $this->handler->relationship . '_' . $this->handler->table . '.' . $this->handler->field;

    if (empty($join)) {
      $join = $this->get_join();
    }

    // See if there's a chain between us and the base relationship. If so, we need
    // to create a new relationship to use.
    $relationship = $this->handler->relationship;

    // Determine the primary table to seek
    if (empty($this->handler->query->relationships[$relationship])) {
      $base_table = $this->handler->query->base_table;
    }
    else {
      $base_table = $this->handler->query->relationships[$relationship]['base'];
    }

    // Cycle through the joins. This isn't as error-safe as the normal
    // ensure_path logic. Perhaps it should be.
    $r_join = clone $join;
    while ($r_join->left_table != $base_table) {
      $r_join = views_get_table_join($r_join->left_table, $base_table);
    }
    // If we found that there are tables in between, add the relationship.
    if ($r_join->table != $join->table) {
      $relationship = $this->handler->query->add_relationship($this->handler->table . '_' . $r_join->table, $r_join, $r_join->table, $this->handler->relationship);
    }

    // And now add our table, using the new relationship if one was used.
    $alias = $this->handler->query->add_table($this->handler->table, $relationship, $join, $alias);

    // Store what values are used by this table chain so that other chains can
    // automatically discard those values.
    if (empty($this->handler->view->many_to_one_tables[$field])) {
      $this->handler->view->many_to_one_tables[$field] = $this->handler->value;
    }
    else {
      $this->handler->view->many_to_one_tables[$field] = array_merge($this->handler->view->many_to_one_tables[$field], $this->handler->value);
    }

    return $alias;
  }

  function get_join() {
    return $this->handler->get_join();
  }

  /**
   * Provide the proper join for summary queries. This is important in part because
   * it will cooperate with other arguments if possible.
   */
  function summary_join() {
    $field = $this->handler->relationship . '_' . $this->handler->table . '.' . $this->handler->field;
    $join = $this->get_join();

    // shortcuts
    $options = $this->handler->options;
    $view = &$this->handler->view;
    $query = &$this->handler->query;

    if (!empty($options['require_value'])) {
      $join->type = 'INNER';
    }

    if (empty($options['add_table']) || empty($view->many_to_one_tables[$field])) {
      return $query->ensure_table($this->handler->table, $this->handler->relationship, $join);
    }
    else {
      if (!empty($view->many_to_one_tables[$field])) {
        foreach ($view->many_to_one_tables[$field] as $value) {
          $join->extra = array(
            array(
              'field' => $this->handler->real_field,
              'operator' => '!=',
              'value' => $value,
              'numeric' => !empty($this->definition['numeric']),
            ),
          );
        }
      }
      return $this->add_table($join);
    }
  }

  /**
   * Override ensure_my_table so we can control how this joins in.
   * The operator actually has influence over joining.
   */
  function ensure_my_table() {
    if (!isset($this->handler->table_alias)) {
      // Case 1: Operator is an 'or' and we're not reducing duplicates.
      // We hence get the absolute simplest:
      $field = $this->handler->relationship . '_' . $this->handler->table . '.' . $this->handler->field;
      if ($this->handler->operator == 'or' && empty($this->handler->options['reduce_duplicates'])) {
        if (empty($this->handler->options['add_table']) && empty($this->handler->view->many_to_one_tables[$field])) {
          // query optimization, INNER joins are slightly faster, so use them
          // when we know we can.
          $join = $this->get_join();
          if (isset($join)) {
            $join->type = 'INNER';
          }
          $this->handler->table_alias = $this->handler->query->ensure_table($this->handler->table, $this->handler->relationship, $join);
          $this->handler->view->many_to_one_tables[$field] = $this->handler->value;
        }
        else {
          $join = $this->get_join();
          $join->type = 'LEFT';
          if (!empty($this->handler->view->many_to_one_tables[$field])) {
            foreach ($this->handler->view->many_to_one_tables[$field] as $value) {
              $join->extra = array(
                array(
                  'field' => $this->handler->real_field,
                  'operator' => '!=',
                  'value' => $value,
                  'numeric' => !empty($this->handler->definition['numeric']),
                ),
              );
            }
          }

          $this->handler->table_alias = $this->add_table($join);
        }

        return $this->handler->table_alias;
      }

      // Case 2: it's an 'and' or an 'or'.
      // We do one join per selected value.
      if ($this->handler->operator != 'not') {
        // Clone the join for each table:
        $this->handler->table_aliases = array();
        foreach ($this->handler->value as $value) {
          $join = $this->get_join();
          if ($this->handler->operator == 'and') {
            $join->type = 'INNER';
          }
          $join->extra = array(
            array(
              'field' => $this->handler->real_field,
              'value' => $value,
              'numeric' => !empty($this->handler->definition['numeric']),
            ),
          );

          // The table alias needs to be unique to this value across the
          // multiple times the filter or argument is called by the view.
          if (!isset($this->handler->view->many_to_one_aliases[$field][$value])) {
            if (!isset($this->handler->view->many_to_one_count[$this->handler->table])) {
              $this->handler->view->many_to_one_count[$this->handler->table] = 0;
            }
            $this->handler->view->many_to_one_aliases[$field][$value] = $this->handler->table . '_value_' . ($this->handler->view->many_to_one_count[$this->handler->table]++);
          }
          $alias = $this->handler->table_aliases[$value] = $this->add_table($join, $this->handler->view->many_to_one_aliases[$field][$value]);

          // and set table_alias to the first of these.
          if (empty($this->handler->table_alias)) {
            $this->handler->table_alias = $alias;
          }
        }
      }
      // Case 3: it's a 'not'.
      // We just do one join. We'll add a where clause during
      // the query phase to ensure that $table.$field IS NULL.
      else {
        $join = $this->get_join();
        $join->type = 'LEFT';
        $join->extra = array();
        $join->extra_type = 'OR';
        foreach ($this->handler->value as $value) {
          $join->extra[] = array(
            'field' => $this->handler->real_field,
            'value' => $value,
            'numeric' => !empty($this->handler->definition['numeric']),
          );
        }

        $this->handler->table_alias = $this->add_table($join);
      }
    }
    return $this->handler->table_alias;
  }

  /**
   * Provides a unique placeholders for handlers.
   */
  function placeholder() {
    return $this->handler->query->placeholder($this->handler->options['table'] . '_' . $this->handler->options['field']);
  }

  function add_filter() {
    if (empty($this->handler->value)) {
      return;
    }
    $this->handler->ensure_my_table();

    // Shorten some variables:
    $field = $this->get_field();
    $options = $this->handler->options;
    $operator = $this->handler->operator;
    $formula = !empty($this->formula);
    $value = $this->handler->value;
    if (empty($options['group'])) {
      $options['group'] = 0;
    }

    // add_condition determines whether a single expression is enough(FALSE) or the
    // conditions should be added via an db_or()/db_and() (TRUE).
    $add_condition = TRUE;
    if ($operator == 'not') {
      $value = NULL;
      $operator = 'IS NULL';
      $add_condition = FALSE;
    }
    elseif ($operator == 'or' && empty($options['reduce_duplicates'])) {
      if (count($value) > 1) {
        $operator = 'IN';
      }
      else {
        $value = is_array($value) ? array_pop($value) : $value;
        $operator = '=';
      }
      $add_condition = FALSE;
    }

    if (!$add_condition) {
      if ($formula) {
        $placeholder = $this->placeholder();
        if ($operator == 'IN') {
          $operator = "$operator IN($placeholder)";
        }
        else {
          $operator = "$operator $placeholder";
        }
        $placeholders = array(
          $placeholder => $value,
        ) + $this->placeholders;
        $this->handler->query->add_where_expression($options['group'], "$field $operator", $placeholders);
      }
      else {
        $this->handler->query->add_where($options['group'], $field, $value, $operator);
      }
    }

    if ($add_condition) {
      $field = $this->handler->real_field;
      $clause = $operator == 'or' ? db_or() : db_and();
      foreach ($this->handler->table_aliases as $value => $alias) {
        $clause->condition("$alias.$field", $value);
      }

      // implode on either AND or OR.
      $this->handler->query->add_where($options['group'], $clause);
    }
  }
}

/**
 * Break x,y,z and x+y+z into an array. Works for strings.
 *
 * @param $str
 *   The string to parse.
 * @param $object
 *   The object to use as a base. If not specified one will
 *   be created.
 *
 * @return $object
 *   An object containing
 *   - operator: Either 'and' or 'or'
 *   - value: An array of numeric values.
 */
function views_break_phrase_string($str, &$handler = NULL) {
  if (!$handler) {
    $handler = new stdClass();
  }

  // Set up defaults:
  if (!isset($handler->value)) {
    $handler->value = array();
  }

  if (!isset($handler->operator)) {
    $handler->operator = 'or';
  }

  if ($str == '') {
    return $handler;
  }

  if (preg_match('/^(\w+[+ ])+\w+$/', $str)) {
    // The '+' character in a query string may be parsed as ' '.
    $handler->operator = 'or';
    $handler->value = preg_split('/[+ ]/', $str);
  }
  else if (preg_match('/^((\w|\s)+,)*(\w|\s)+$/', $str)) {
    $handler->operator = 'and';
    $handler->value = explode(',', $str);
  }

  // Keep an 'error' value if invalid strings were given.
  if (!empty($str) && (empty($handler->value) || !is_array($handler->value))) {
    $handler->value = array(-1);
    return $handler;
  }

  // Doubly ensure that all values are strings only.
  foreach ($handler->value as $id => $value) {
    $handler->value[$id] = (string) $value;
  }

  return $handler;
}

/**
 * Break x,y,z and x+y+z into an array. Numeric only.
 *
 * @param $str
 *   The string to parse.
 * @param $handler
 *   The handler object to use as a base. If not specified one will
 *   be created.
 *
 * @return $handler
 *   The new handler object.
 */
function views_break_phrase($str, &$handler = NULL) {
  if (!$handler) {
    $handler = new stdClass();
  }

  // Set up defaults:

  if (!isset($handler->value)) {
    $handler->value = array();
  }

  if (!isset($handler->operator)) {
    $handler->operator = 'or';
  }

  if (empty($str)) {
    return $handler;
  }

  if (preg_match('/^([0-9]+[+ ])+[0-9]+$/', $str)) {
    // The '+' character in a query string may be parsed as ' '.
    $handler->operator = 'or';
    $handler->value = preg_split('/[+ ]/', $str);
  }
  elseif (preg_match('/^([0-9]+,)*[0-9]+$/', $str)) {
    $handler->operator = 'and';
    $handler->value = explode(',', $str);
  }

  // Keep an 'error' value if invalid strings were given.
  if (!empty($str) && (empty($handler->value) || !is_array($handler->value))) {
    $handler->value = array(-1);
    return $handler;
  }

  // Doubly ensure that all values are numeric only.
  foreach ($handler->value as $id => $value) {
    $handler->value[$id] = intval($value);
  }

  return $handler;
}

// --------------------------------------------------------------------------
// Date helper functions

/**
 * Figure out what timezone we're in; needed for some date manipulations.
 */
function views_get_timezone() {
  global $user;
  if (variable_get('configurable_timezones', 1) && $user->uid && strlen($user->timezone)) {
    $timezone = $user->timezone;
  }
  else {
    $timezone = variable_get('date_default_timezone', 0);
  }

  // set up the database timezone
  $db_type = Database::getConnection()->databaseType();
  if (in_array($db_type, array('mysql', 'pgsql'))) {
    $offset = '+00:00';
    static $already_set = FALSE;
    if (!$already_set) {
      if ($db_type == 'pgsql') {
        db_query("SET TIME ZONE INTERVAL '$offset' HOUR TO MINUTE");
      }
      elseif ($db_type == 'mysql') {
        db_query("SET @@session.time_zone = '$offset'");
      }

      $already_set = true;
    }
  }

  return $timezone;
}

/**
 * Helper function to create cross-database SQL dates.
 *
 * @param $field
 *   The real table and field name, like 'tablename.fieldname'.
 * @param $field_type
 *  The type of date field, 'int' or 'datetime'.
 * @param $set_offset
 *   The name of a field that holds the timezone offset or a fixed timezone
 *   offset value. If not provided, the normal Drupal timezone handling
 *   will be used, i.e. $set_offset = 0 will make no timezone adjustment.
 * @return
 *   An appropriate SQL string for the db type and field type.
 */
function views_date_sql_field($field, $field_type = 'int', $set_offset = NULL) {
  $db_type = Database::getConnection()->databaseType();
  $offset = $set_offset !== NULL ? $set_offset : views_get_timezone();
  if (isset($offset) && !is_numeric($offset)) {
    $dtz = new DateTimeZone($offset);
    $dt = new DateTime("now", $dtz);
    $offset_seconds = $dtz->getOffset($dt);
  }

  switch ($db_type) {
    case 'mysql':
      switch ($field_type) {
        case 'int':
          $field = "DATE_ADD('19700101', INTERVAL $field SECOND)";
          break;
        case 'datetime':
          break;
      }
      if (!empty($offset)) {
        $field = "($field + INTERVAL $offset_seconds SECOND)";
      }
      return $field;
    case 'pgsql':
      switch ($field_type) {
        case 'int':
          $field = "TO_TIMESTAMP($field)";
          break;
        case 'datetime':
          break;
      }
      if (!empty($offset)) {
        $field = "($field + INTERVAL '$offset_seconds SECONDS')";
      }
      return $field;
    case 'sqlite':
      if (!empty($offset)) {
        $field = "($field + '$offset_seconds')";
      }
      return $field;
  }
}

/**
 * Helper function to create cross-database SQL date formatting.
 *
 * @param $format
 *   A format string for the result, like 'Y-m-d H:i:s'.
 * @param $field
 *   The real table and field name, like 'tablename.fieldname'.
 * @param $field_type
 *   The type of date field, 'int' or 'datetime'.
 * @param $set_offset
 *   The name of a field that holds the timezone offset or a fixed timezone
 *   offset value. If not provided, the normal Drupal timezone handling
 *   will be used, i.e. $set_offset = 0 will make no timezone adjustment.
 * @return
 *   An appropriate SQL string for the db type and field type.
 */
function views_date_sql_format($format, $field, $field_type = 'int', $set_offset = NULL) {
  $db_type = Database::getConnection()->databaseType();
  $field = views_date_sql_field($field, $field_type, $set_offset);
  switch ($db_type) {
    case 'mysql':
      $replace = array(
        'Y' => '%Y',
        'y' => '%y',
        'M' => '%b',
        'm' => '%m',
        'n' => '%c',
        'F' => '%M',
        'D' => '%a',
        'd' => '%d',
        'l' => '%W',
        'j' => '%e',
        'W' => '%v',
        'H' => '%H',
        'h' => '%h',
        'i' => '%i',
        's' => '%s',
        'A' => '%p',
        );
      $format = strtr($format, $replace);
      return "DATE_FORMAT($field, '$format')";
    case 'pgsql':
      $replace = array(
        'Y' => 'YYYY',
        'y' => 'YY',
        'M' => 'Mon',
        'm' => 'MM',
        'n' => 'MM', // no format for Numeric representation of a month, without leading zeros
        'F' => 'Month',
        'D' => 'Dy',
        'd' => 'DD',
        'l' => 'Day',
        'j' => 'DD', // no format for Day of the month without leading zeros
        'W' => 'WW',
        'H' => 'HH24',
        'h' => 'HH12',
        'i' => 'MI',
        's' => 'SS',
        'A' => 'AM',
        );
      $format = strtr($format, $replace);
      return "TO_CHAR($field, '$format')";
    case 'sqlite':
      $replace = array(
        'Y' => '%Y', // 4 digit year number
        'y' => '%Y', // no format for 2 digit year number
        'M' => '%m', // no format for 3 letter month name
        'm' => '%m', // month number with leading zeros
        'n' => '%m', // no format for month number without leading zeros
        'F' => '%m', // no format for full month name
        'D' => '%d', // no format for 3 letter day name
        'd' => '%d', // day of month number with leading zeros
        'l' => '%d', // no format for full day name
        'j' => '%d', // no format for day of month number without leading zeros
        'W' => '%W', // ISO week number
        'H' => '%H', // 24 hour hour with leading zeros
        'h' => '%H', // no format for 12 hour hour with leading zeros
        'i' => '%M', // minutes with leading zeros
        's' => '%S', // seconds with leading zeros
        'A' => '', // no format for  AM/PM
      );
      $format = strtr($format, $replace);
      return "strftime('$format', $field, 'unixepoch')";
  }
}

/**
 * Helper function to create cross-database SQL date extraction.
 *
 * @param $extract_type
 *   The type of value to extract from the date, like 'MONTH'.
 * @param $field
 *   The real table and field name, like 'tablename.fieldname'.
 * @param $field_type
 *   The type of date field, 'int' or 'datetime'.
 * @param $set_offset
 *   The name of a field that holds the timezone offset or a fixed timezone
 *   offset value. If not provided, the normal Drupal timezone handling
 *   will be used, i.e. $set_offset = 0 will make no timezone adjustment.
 * @return
 *   An appropriate SQL string for the db type and field type.
 */
function views_date_sql_extract($extract_type, $field, $field_type = 'int', $set_offset = NULL) {
  $db_type = Database::getConnection()->databaseType();
  $field = views_date_sql_field($field, $field_type, $set_offset);

  // Note there is no space after FROM to avoid db_rewrite problems
  // see http://drupal.org/node/79904.
  switch ($extract_type) {
  case('DATE'):
    return $field;
  case('YEAR'):
    return "EXTRACT(YEAR FROM($field))";
  case('MONTH'):
    return "EXTRACT(MONTH FROM($field))";
  case('DAY'):
    return "EXTRACT(DAY FROM($field))";
  case('HOUR'):
    return "EXTRACT(HOUR FROM($field))";
  case('MINUTE'):
    return "EXTRACT(MINUTE FROM($field))";
  case('SECOND'):
    return "EXTRACT(SECOND FROM($field))";
  case('WEEK'):  // ISO week number for date
    switch ($db_type) {
      case('mysql'):
        // WEEK using arg 3 in mysql should return the same value as postgres EXTRACT
        return "WEEK($field, 3)";
      case('pgsql'):
        return "EXTRACT(WEEK FROM($field))";
    }
  case('DOW'):
    switch ($db_type) {
      case('mysql'):
        // mysql returns 1 for Sunday through 7 for Saturday
        // php date functions and postgres use 0 for Sunday and 6 for Saturday
        return "INTEGER(DAYOFWEEK($field) - 1)";
      case('pgsql'):
        return "EXTRACT(DOW FROM($field))";
    }
  case('DOY'):
    switch ($db_type) {
      case('mysql'):
        return "DAYOFYEAR($field)";
      case('pgsql'):
        return "EXTRACT(DOY FROM($field))";
    }
  }
}

/**
 * @}
 */

/**
 * @defgroup views_join_handlers Views join handlers
 * @{
 * Handlers to tell Views how to join tables together.
 *
 * Here is how you do complex joins:
 *
 * @code
 * class views_join_complex extends views_join {
 *   // PHP 4 doesn't call constructors of the base class automatically from a
 *   // constructor of a derived class. It is your responsibility to propagate
 *   // the call to constructors upstream where appropriate.
 *   function construct($table, $left_table, $left_field, $field, $extra = array(), $type = 'LEFT') {
 *     parent::construct($table, $left_table, $left_field, $field, $extra, $type);
 *   }
 *
 *   function build_join($select_query, $table, $view_query) {
 *     $this->extra = 'foo.bar = baz.boing';
 *     parent::build_join($select_query, $table, $view_query);
 *   }
 * }
 * @endcode
 */

/**
 * A function class to represent a join and create the SQL necessary
 * to implement the join.
 *
 * This is the Delegation pattern. If we had PHP5 exclusively, we would
 * declare this an interface.
 *
 * Extensions of this class can be used to create more interesting joins.
 *
 * join definition
 *   - table: table to join (right table)
 *   - field: field to join on (right field)
 *   - left_table: The table we join to
 *   - left_field: The field we join to
 *   - type: either LEFT (default) or INNER
 *   - extra: An array of extra conditions on the join. Each condition is
 *     either a string that's directly added, or an array of items:
 *   - - table: If not set, current table; if NULL, no table. If you specify a
 *       table in cached definition, Views will try to load from an existing
 *       alias. If you use realtime joins, it works better.
 *   - - field: Field or formula
 *       in formulas we can reference the right table by using %alias
 *       @see SelectQueryInterface::addJoin()
 *   - - operator: defaults to =
 *   - - value: Must be set. If an array, operator will be defaulted to IN.
 *   - - numeric: If true, the value will not be surrounded in quotes.
 *   - - extra type: How all the extras will be combined. Either AND or OR. Defaults to AND.
 */
class views_join {
  var $table = NULL;
  var $left_table = NULL;
  var $left_field = NULL;
  var $field = NULL;
  var $extra = NULL;
  var $type = NULL;
  var $definition = array();

  /**
   * Construct the views_join object.
   */
  function construct($table = NULL, $left_table = NULL, $left_field = NULL, $field = NULL, $extra = array(), $type = 'LEFT') {
    $this->extra_type = 'AND';
    if (!empty($table)) {
      $this->table = $table;
      $this->left_table = $left_table;
      $this->left_field = $left_field;
      $this->field = $field;
      $this->extra = $extra;
      $this->type = strtoupper($type);
    }
    elseif (!empty($this->definition)) {
      // if no arguments, construct from definition.
      // These four must exist or it will throw notices.
      $this->table = $this->definition['table'];
      $this->left_table = $this->definition['left_table'];
      $this->left_field = $this->definition['left_field'];
      $this->field = $this->definition['field'];
      if (!empty($this->definition['extra'])) {
        $this->extra = $this->definition['extra'];
      }
      if (!empty($this->definition['extra type'])) {
        $this->extra_type = strtoupper($this->definition['extra type']);
      }

      $this->type = !empty($this->definition['type']) ? strtoupper($this->definition['type']) : 'LEFT';
    }
  }

  /**
   * Build the SQL for the join this object represents.
   *
   * When possible, try to use table alias instead of table names.
   *
   * @param $select_query
   *   An implementation of SelectQueryInterface.
   * @param $table
   *   The base table to join.
   * @param $view_query
   *   The source query, implementation of views_plugin_query.
   */
  function build_join($select_query, $table, $view_query) {
    if (empty($this->definition['table formula'])) {
      $right_table = $this->table;
    }
    else {
      $right_table = $this->definition['table formula'];
    }

    if ($this->left_table) {
      $left = $view_query->get_table_info($this->left_table);
      $left_field = "$left[alias].$this->left_field";
    }
    else {
      // This can be used if left_field is a formula or something. It should be used only *very* rarely.
      $left_field = $this->left_field;
    }

    $condition = "$left_field = $table[alias].$this->field";
    $arguments = array();

    // Tack on the extra.
    if (isset($this->extra)) {
      if (is_array($this->extra)) {
        $extras = array();
        foreach ($this->extra as $info) {
          $extra = '';
          // Figure out the table name. Remember, only use aliases provided
          // if at all possible.
          $join_table = '';
          if (!array_key_exists('table', $info)) {
            $join_table = $table['alias'] . '.';
          }
          elseif (isset($info['table'])) {
            // If we're aware of a table alias for this table, use the table
            // alias instead of the table name.
            if (isset($left) && $left['table'] == $info['table']) {
              $join_table = $left['alias'] . '.';
            }
            else {
              $join_table = $info['table'] . '.';
            }
          }

          // Convert a single-valued array of values to the single-value case,
          // and transform from IN() notation to = notation
          if (is_array($info['value']) && count($info['value']) == 1) {
            if (empty($info['operator'])) {
              $operator = '=';
            }
            else {
              $operator = $info['operator'] == 'NOT IN' ? '!=' : '=';
            }
            $info['value'] = array_shift($info['value']);
          }

          if (is_array($info['value'])) {
            // With an array of values, we need multiple placeholders and the
            // 'IN' operator is implicit.
            foreach ($info['value'] as $value) {
              $placeholder_i = ':views_join_condition_' . $select_query->nextPlaceholder();
              $arguments[$placeholder_i] = $value;
            }

            $operator = !empty($info['operator']) ? $info['operator'] : 'IN';
            $placeholder = '( ' . implode(', ', array_keys($arguments)) . ' )';
          }
          else {
            // With a single value, the '=' operator is implicit.
            $operator = !empty($info['operator']) ? $info['operator'] : '=';
            $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();
            $arguments[$placeholder] = $info['value'];
          }

          $extras[] = "$join_table$info[field] $operator $placeholder";
        }

        if ($extras) {
          if (count($extras) == 1) {
            $condition .= ' AND ' . array_shift($extras);
          }
          else {
            $condition .= ' AND (' . implode(' ' . $this->extra_type . ' ', $extras) . ')';
          }
        }
      }
      elseif ($this->extra && is_string($this->extra)) {
        $condition .= " AND ($this->extra)";
      }
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }
}

/**
 * Join handler for relationships that join with a subquery as the left field.
 * eg:
 *  LEFT JOIN node node_term_data ON ([YOUR SUBQUERY HERE]) = node_term_data.nid
 *
 * join definition
 *   same as views_join class above, except:
 *   - left_query: The subquery to use in the left side of the join clause.
 */
class views_join_subquery extends views_join {
  function construct($table = NULL, $left_table = NULL, $left_field = NULL, $field = NULL, $extra = array(), $type = 'LEFT') {
    parent::construct($table, $left_table, $left_field, $field, $extra, $type);
    $this->left_query = $this->definition['left_query'];
  }

  /**
   * Build the SQL for the join this object represents.
   *
   * @param $select_query
   *   An implementation of SelectQueryInterface.
   * @param $table
   *   The base table to join.
   * @param $view_query
   *   The source query, implementation of views_plugin_query.
   * @return
   *
   */
  function build_join($select_query, $table, $view_query) {
    if (empty($this->definition['table formula'])) {
      $right_table = "{" . $this->table . "}";
    }
    else {
      $right_table = $this->definition['table formula'];
    }

    // Add our join condition, using a subquery on the left instead of a field.
    $condition = "($this->left_query) = $table[alias].$this->field";
    $arguments = array();

    // Tack on the extra.
    // This is just copied verbatim from the parent class, which itself has a bug: http://drupal.org/node/1118100
    if (isset($this->extra)) {
      if (is_array($this->extra)) {
        $extras = array();
        foreach ($this->extra as $info) {
          $extra = '';
          // Figure out the table name. Remember, only use aliases provided
          // if at all possible.
          $join_table = '';
          if (!array_key_exists('table', $info)) {
            $join_table = $table['alias'] . '.';
          }
          elseif (isset($info['table'])) {
            $join_table = $info['table'] . '.';
          }

          $placeholder = ':views_join_condition_' . $select_query->nextPlaceholder();

          if (is_array($info['value'])) {
            $operator = !empty($info['operator']) ? $info['operator'] : 'IN';
            // Transform from IN() notation to = notation if just one value.
            if (count($info['value']) == 1) {
              $info['value'] = array_shift($info['value']);
              $operator = $operator == 'NOT IN' ? '!=' : '=';
            }
          }
          else {
            $operator = !empty($info['operator']) ? $info['operator'] : '=';
          }

          $extras[] = "$join_table$info[field] $operator $placeholder";
          $arguments[$placeholder] = $info['value'];
        }

        if ($extras) {
          if (count($extras) == 1) {
            $condition .= ' AND ' . array_shift($extras);
          }
          else {
            $condition .= ' AND (' . implode(' ' . $this->extra_type . ' ', $extras) . ')';
          }
        }
      }
      elseif ($this->extra && is_string($this->extra)) {
        $condition .= " AND ($this->extra)";
      }
    }

    $select_query->addJoin($this->type, $right_table, $table['alias'], $condition, $arguments);
  }
}

/**
 * @}
 */
