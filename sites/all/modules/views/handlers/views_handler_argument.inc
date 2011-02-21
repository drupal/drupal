<?php
// $Id: views_handler_argument.inc,v 1.9.4.12 2010/12/17 21:42:19 merlinofchaos Exp $

/**
 * @defgroup views_argument_handlers Handlers for arguments
 * @{
 */

/**
 * Base class for arguments.
 *
 * The basic argument works for very simple arguments such as nid and uid
 *
 * Definition terms for this handler:
 * - name field: The field to use for the name to use in the summary, which is
 *               the displayed output. For example, for the node: nid argument,
 *               the argument itself is the nid, but node.title is displayed.
 * - name table: The table to use for the name, should it not be in the same
 *               table as the argument.
 * - empty field name: For arguments that can have no value, such as taxonomy
 *                     which can have "no term", this is the string which
 *                     will be displayed for this lack of value. Be sure to use
 *                     t().
 * - validate type: A little used string to allow an argument to restrict
 *                  which validator is available to just one. Use the
 *                  validator ID. This probably should not be used at all,
 *                  and may disappear or change.
 * - numeric: If set to TRUE this field is numeric and will use %d instead of
 *            %s in queries.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument extends views_handler {
  var $name_field = NULL;
  /**
   * Constructor
   */
  function construct() {
    parent::construct();

    if (!empty($this->definition['name field'])) {
      $this->name_field = $this->definition['name field'];
    }
    if (!empty($this->definition['name table'])) {
      $this->name_table = $this->definition['name table'];
    }
  }

  function init(&$view, &$options) {
    parent::init($view, $options);
  }

  /**
   * Give an argument the opportunity to modify the breadcrumb, if it wants.
   * This only gets called on displays where a breadcrumb is actually used.
   *
   * The breadcrumb will be in the form of an array, with the keys being
   * the path and the value being the already sanitized title of the path.
   */
  function set_breadcrumb(&$breadcrumb) { }

  /**
   * Determine if the argument can generate a breadcrumb
   *
   * @return TRUE/FALSE
   */
  function uses_breadcrumb() {
    $info = $this->default_actions($this->options['default_action']);
    return !empty($info['breadcrumb']);
  }

  function is_wildcard($arg = NULL) {
    if (!isset($arg)) {
      $arg = $this->argument;
    }

    return !empty($this->options['wildcard']) && $this->options['wildcard'] === $arg;
  }

  function wildcard_title() {
    return $this->options['wildcard_substitution'];
  }

  /**
   * Determine if the argument needs a style plugin.
   *
   * @return TRUE/FALSE
   */
  function needs_style_plugin() {
    $info = $this->default_actions($this->options['default_action']);
    $validate_info = $this->default_actions($this->options['validate_fail']);
    return !empty($info['style plugin']) || !empty($validate_info['style plugin']);
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['default_action'] = array('default' => 'ignore');
    $options['style_plugin'] = array('default' => 'default_summary', 'export' => 'export_style');
    $options['style_options'] = array('default' => array(), 'export' => FALSE);
    $options['wildcard'] = array('default' => 'all');
    $options['wildcard_substitution'] = array('default' => t('All'), 'translatable' => TRUE);
    $options['title'] = array('default' => '', 'translatable' => TRUE);
    $options['breadcrumb'] = array('default' => '', 'translatable' => TRUE);
    $options['default_argument_type'] = array('default' => 'fixed', 'export' => 'export_plugin');
    $options['default_argument_options'] = array('default' => array(), 'export' => FALSE);
    $options['validate_type'] = array('default' => 'none', 'export' => 'export_plugin');
    $options['validate_options'] = array('default' => array(), 'export' => FALSE);
    $options['validate_fail'] = array('default' => 'not found');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $defaults = $this->default_actions();

    $form['title'] = array(
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $this->options['title'],
      '#description' => t('The title to use when this argument is present. It will override the title of the view and titles from previous arguments. You can use percent substitution here to replace with argument titles. Use "%1" for the first argument, "%2" for the second, etc.'),
    );

    $form['breadcrumb'] = array(
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t('Breadcrumb'),
      '#default_value' => $this->options['breadcrumb'],
      '#description' => t('The Breadcrumb title to use when this argument is present. If no breadcrumb is set here, default Title values will be used, see "Title" for percent substitutions.'),
    );

    $form['clear_start'] = array(
      '#markup' => '<div class="clearfix">',
    );

    $form['defaults_start'] = array(
      '#markup' => '<div class="views-left-50">',
    );

    $form['default_action'] = array(
      '#type' => 'radios',
      '#title' => t('Action to take if argument is not present'),
      '#default_value' => $this->options['default_action'],
    );

    $form['defaults_stop'] = array(
      '#markup' => '</div>',
    );

    $form['wildcard'] = array(
      '#prefix' => '<div class="views-right-50">',
      // prefix and no suffix means these two items will be grouped together.
      '#type' => 'textfield',
      '#title' => t('Wildcard'),
      '#size' => 20,
      '#default_value' => $this->options['wildcard'],
      '#description' => t('If this value is received as an argument, the argument will be ignored; i.e, "all values"'),
    );

    $form['wildcard_substitution'] = array(
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t('Wildcard title'),
      '#size' => 20,
      '#default_value' => $this->options['wildcard_substitution'],
      '#description' => t('The title to use for the wildcard in substitutions elsewhere.'),
    );

    $form['clear_stop'] = array(
      '#markup' => '</div>',
    );

    $options = array();
    $validate_options = array();
    foreach ($defaults as $id => $info) {
      $options[$id] = $info['title'];
      if (empty($info['default only'])) {
        $validate_options[$id] = $info['title'];
      }
      if (!empty($info['form method'])) {
        $this->{$info['form method']}($form, $form_state);
      }
    }

    $form['default_action']['#options'] = $options;

    $form['validate_options_div_prefix'] = array(
      '#id' => 'views-validator-options',
      '#markup' => '<fieldset id="views-validator-options"><legend>' . t('Validator options') . '</legend>',
    );

    $form['validate_type'] = array(
      '#type' => 'select',
      '#title' => t('Validator'),
      '#default_value' => $this->options['validate_type'],
    );

    $validate_types = array('none' => t('- Basic validation -'));
    $plugins = views_fetch_plugin_data('argument validator');
    foreach ($plugins as $id => $info) {
      if (!empty($info['no ui'])) {
        continue;
      }

      $valid = TRUE;
      if (!empty($info['type'])) {
        $valid = FALSE;
        if (empty($this->definition['validate type'])) {
          continue;
        }
        foreach ((array) $info['type'] as $type) {
          if ($type == $this->definition['validate type']) {
            $valid = TRUE;
            break;
          }
        }
      }

      // If we decide this validator is ok, add it to the list.
      if ($valid) {
        $plugin = $this->get_plugin('argument validator', $id);
        if ($plugin) {
          if ($plugin->access() || $this->options['validate_type'] == $id) {
            $form['argument_validate'][$id] = array(
              '#prefix' => '<div id="edit-options-validate-options-' . $id . '-wrapper">',
              '#suffix' => '</div>',
              '#type' => 'item',
              '#input' => TRUE, // trick it into checking input to make #process run
              '#process' => array('ctools_dependent_process'),
              '#dependency' => array(
                'edit-options-validate-type' => array($id)
              ),
              '#id' => 'edit-options-validate-options-' . $id,
            );
            $plugin->options_form($form['argument_validate'][$id], $form_state);
            $validate_types[$id] = $info['title'];
          }
        }
      }
    }

    asort($validate_types);
    $form['validate_type']['#options'] = $validate_types;

    $form['validate_fail'] = array(
      '#type' => 'select',
      '#title' => t('Action to take if argument does not validate'),
      '#default_value' => $this->options['validate_fail'],
      '#options' => $validate_options,
    );

    $form['validate_options_div_suffix'] = array(
      '#markup' => '</fieldset>',
    );
  }

  function options_validate(&$form, &$form_state) {
    if (empty($form_state['values']['options'])) {
      return;
    }

    // Let the plugins do validation.
    $default_id = $form_state['values']['options']['default_argument_type'];
    $plugin = $this->get_plugin('argument default', $default_id);
    if ($plugin) {
      $plugin->options_validate($form['argument_default'][$default_id], $form_state, $form_state['values']['options']['argument_default'][$default_id]);
    }

    $validate_id = $form_state['values']['options']['validate_type'];
    $plugin = $this->get_plugin('argument validator', $validate_id);
    if ($plugin) {
      $plugin->options_validate($form['argument_validate'][$default_id], $form_state, $form_state['values']['options']['argument_validate'][$validate_id]);
    }

  }

  function options_submit(&$form, &$form_state) {
    if (empty($form_state['values']['options'])) {
      return;
    }

    // Let the plugins make submit modifications if necessary.
    $default_id = $form_state['values']['options']['default_argument_type'];
    $plugin = $this->get_plugin('argument default', $default_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['argument_default'][$default_id];
      $plugin->options_submit($form['argument_default'][$default_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['default_argument_options'] = $options;
    }

    $validate_id = $form_state['values']['options']['validate_type'];
    $plugin = $this->get_plugin('argument validator', $validate_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['argument_validate'][$validate_id];
      $plugin->options_submit($form['argument_validate'][$validate_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['validate_options'] = $options;
    }
  }

  /**
   * Provide a list of default behaviors for this argument if the argument
   * is not present.
   *
   * Override this method to provide additional (or fewer) default behaviors.
   */
  function default_actions($which = NULL) {
    $defaults = array(
      'ignore' => array(
        'title' => t('Display all values'),
        'method' => 'default_ignore',
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'not found' => array(
        'title' => t('Hide view / Page not found (404)'),
        'method' => 'default_not_found',
        'hard fail' => TRUE, // This is a hard fail condition
      ),
      'empty' => array(
        'title' => t('Display empty text'),
        'method' => 'default_empty',
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'summary asc' => array(
        'title' => t('Summary, sorted ascending'),
        'method' => 'default_summary',
        'method args' => array('asc'),
        'style plugin' => TRUE,
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'summary desc' => array(
        'title' => t('Summary, sorted descending'),
        'method' => 'default_summary',
        'method args' => array('desc'),
        'style plugin' => TRUE,
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'summary asc by count' => array(
        'title' => t('Summary, sorted by number of records ascending'),
        'method' => 'default_summary',
        'method args' => array('asc', 'num_records'),
        'style plugin' => TRUE,
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'summary desc by count' => array(
        'title' => t('Summary, sorted by number of records descending'),
        'method' => 'default_summary',
        'method args' => array('desc', 'num_records'),
        'style plugin' => TRUE,
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'default' => array(
        'title' => t('Provide default argument'),
        'method' => 'default_default',
        'form method' => 'default_argument_form',
        'has default argument' => TRUE,
        'default only' => TRUE, // this can only be used for missing argument, not validation failure
      ),
    );

    if ($which) {
      if (!empty($defaults[$which])) {
        return $defaults[$which];
      }
    }
    else {
      return $defaults;
    }
  }

  /**
   * Provide a form for selecting the default argument when the
   * default action is set to provide default argument.
   */
  function default_argument_form(&$form, &$form_state) {
    $plugins = views_fetch_plugin_data('argument default');
    $options = array();

    // This construct uses 'hidden' and not markup because process doesn't
    // run. It also has an extra div because the dependency wants to hide
    // the parent in situations like this, so we need a second div to
    // make this work.
    $form['default_options_div_prefix'] = array(
      '#type' => 'hidden',
      '#id' => 'views-default-options',
      '#prefix' => '<div id="views-default-options-wrapper"><fieldset id="views-default-options"><legend>' . t('Provide default argument options') . '</legend>',
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('radio:options[default_action]' => array('default')),
    );

    $form['default_argument_type'] = array(
      '#prefix' => '<div id="edit-options-default-argument-type-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'radios',
      '#id' => 'edit-options-default-argument-type',
      '#title' => t('Default argument type'),
      '#default_value' => $this->options['default_argument_type'],
    );

    foreach ($plugins as $id => $info) {
      if (!empty($info['no ui'])) {
        continue;
      }
      $plugin = $this->get_plugin('argument default', $id);
      if ($plugin) {
        if ($plugin->access() || $this->options['default_argument_type'] == $id) {
          $form['argument_default'][$id] = array(
            '#prefix' => '<div id="edit-options-argument-default-options-' . $id . '-wrapper">',
            '#suffix' => '</div>',
            '#id' => 'edit-options-argument-default-options-' . $id,
            '#type' => 'item',
            '#input' => TRUE, // trick it into checking input to make #process run
            '#process' => array('ctools_dependent_process'),
            '#dependency' => array(
              'radio:options[default_action]' => array('default'),
              'radio:options[default_argument_type]' => array($id)
            ),
            '#dependency_count' => 2,
          );
          $options[$id] = $info['title'];
          $plugin->options_form($form['argument_default'][$id], $form_state);
        }
      }
    }

    $form['default_options_div_suffix'] = array(
      '#markup' => '</fieldset></div>',
    );

    asort($options);
    $form['default_argument_type']['#options'] = $options;
  }

  /**
   * Handle the default action, which means our argument wasn't present.
   *
   * Override this method only with extreme care.
   *
   * @return
   *   A boolean value; if TRUE, continue building this view. If FALSE,
   *   building the view will be aborted here.
   */
  function default_action($info = NULL) {
    if (!isset($info)) {
      $info = $this->default_actions($this->options['default_action']);
    }

    if (!$info) {
      return FALSE;
    }

    if (!empty($info['method args'])) {
      return call_user_func_array(array(&$this, $info['method']), $info['method args']);
    }
    else {
      return $this->{$info['method']}();
    }
  }

  /**
   * How to act if validation failes
   */
  function validate_fail() {
    $info = $this->default_actions($this->options['validate_fail']);
    return $this->default_action($info);
  }
  /**
   * Default action: ignore.
   *
   * If an argument was expected and was not given, in this case, simply
   * ignore the argument entirely.
   */
  function default_ignore() {
    return TRUE;
  }

  /**
   * Default action: not found.
   *
   * If an argument was expected and was not given, in this case, report
   * the view as 'not found' or hide it.
   */
  function default_not_found() {
    // Set a failure condition and let the display manager handle it.
    $this->view->build_info['fail'] = TRUE;
    return FALSE;
  }

  /**
   * Default action: empty
   *
   * If an argument was expected and was not given, in this case, display
   * the view's empty text
   */
  function default_empty() {
    // We return with no query; this will force the empty text.
    $this->view->built = TRUE;
    $this->view->executed = TRUE;
    $this->view->result = array();
    return FALSE;
  }

  /**
   * This just returns true. The view argument builder will know where
   * to find the argument from.
   */
  function default_default() {
    return TRUE;
  }

  /**
   * Determine if the argument is set to provide a default argument.
   */
  function has_default_argument() {
    $info = $this->default_actions($this->options['default_action']);
    return !empty($info['has default argument']);
  }

  /**
   * Get a default argument, if available.
   */
  function get_default_argument() {
    $plugin = $this->get_plugin('argument default');
    if ($plugin) {
      return $plugin->get_argument();
    }
  }

  /**
   * Default action: summary.
   *
   * If an argument was expected and was not given, in this case, display
   * a summary query.
   */
  function default_summary($order, $by = NULL) {
    $this->view->build_info['summary'] = TRUE;
    $this->view->build_info['summary_level'] = $this->options['id'];

    // Change the display style to the summary style for this
    // argument.
    $this->view->plugin_name = $this->options['style_plugin'];
    $this->view->style_options = $this->options['style_options'];

    // Clear out the normal primary field and whatever else may have
    // been added and let the summary do the work.
    $this->query->clear_fields();
    $this->summary_query();

    $this->summary_sort($order, $by);

    // Summaries have their own sorting and fields, so tell the View not
    // to build these.
    $this->view->build_sort = $this->view->build_fields = FALSE;
    return TRUE;
  }

  /**
   * Build the info for the summary query.
   *
   * This must:
   * - add_groupby: group on this field in order to create summaries.
   * - add_field: add a 'num_nodes' field for the count. Usually it will
   *   be a count on $view->base_field
   * - set_count_field: Reset the count field so we get the right paging.
   *
   * @return
   *   The alias used to get the number of records (count) for this entry.
   */
  function summary_query() {
    $this->ensure_my_table();
    // Add the field.
    $this->base_alias = $this->query->add_field($this->table_alias, $this->real_field);

    $this->summary_name_field();
    return $this->summary_basics();
  }

  /**
   * Add the name field, which is the field displayed in summary queries.
   * This is often used when the argument is numeric.
   */
  function summary_name_field() {
    // Add the 'name' field. For example, if this is a uid argument, the
    // name field would be 'name' (i.e, the username).

    if (isset($this->name_table)) {
      // if the alias is different then we're probably added, not ensured,
      // so look up the join and add it instead.
      if ($this->table_alias != $this->table) {
        $j = views_get_table_join($this->name_table, $this->table);
        if ($j) {
          $join = clone $j;
          $join->left_table = $this->table_alias;
          $this->name_table_alias = $this->query->add_table($this->name_table, $this->relationship, $join);
        }
      }
      else {
        $this->name_table_alias = $this->query->ensure_table($this->name_table, $this->relationship);
      }
    }
    else {
      $this->name_table_alias = $this->table_alias;
    }

    if (isset($this->name_field)) {
      $this->name_alias = $this->query->add_field($this->name_table_alias, $this->name_field);
    }
    else {
      $this->name_alias = $this->base_alias;
    }
  }

  /**
   * Some basic summary behavior that doesn't need to be repeated as much as
   * code that goes into summary_query()
   */
  function summary_basics($count_field = TRUE) {
    // Add the number of nodes counter
    $distinct = ($this->view->display_handler->get_option('distinct') && empty($this->query->no_distinct));

    $count_alias = $this->query->add_field($this->query->base_table, $this->query->base_field, 'num_records',
                                           array('count' => TRUE, 'distinct' => $distinct));
    $this->query->add_groupby($this->name_alias);

    if ($count_field) {
      $this->query->set_count_field($this->table_alias, $this->real_field);
    }

    $this->count_alias = $count_alias;
  }

  /**
   * Sorts the summary based upon the user's selection. The base variant of
   * this is usually adequte.
   *
   * @param $order
   *   The order selected in the UI.
   */
  function summary_sort($order, $by = NULL) {
    $this->query->add_orderby(NULL, NULL, $order, (!empty($by) ? $by : $this->name_alias));
  }

  /**
   * Provide the argument to use to link from the summary to the next level;
   * this will be called once per row of a summary, and used as part of
   * $view->get_url().
   *
   * @param $data
   *   The query results for the row.
   */
  function summary_argument($data) {
    return $data->{$this->base_alias};
  }

  /**
   * Provides the name to use for the summary. By default this is just
   * the name field.
   *
   * @param $data
   *   The query results for the row.
   */
  function summary_name($data) {
    $value = $data->{$this->name_alias};
    if (empty($value) && !empty($this->definition['empty field name'])) {
      $value = $this->definition['empty field name'];
    }
    return check_plain($value);
  }

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  function query() {
    $this->ensure_my_table();
    $this->query->add_where(0, "$this->table_alias.$this->real_field", $this->argument);
  }

  /**
   * Get the title this argument will assign the view, given the argument.
   *
   * This usually needs to be overridden to provide a proper title.
   */
  function title() {
    return check_plain($this->argument);
  }

  /**
   * Called by the view object to get the title. This may be set by a
   * validator so we don't necessarily call through to title().
   */
  function get_title() {
    if (isset($this->validated_title)) {
      return $this->validated_title;
    }
    else {
      return $this->title();
    }
  }

  /**
   * Validate that this argument works. By default, all arguments are valid.
   */
  function validate_arg($arg) {
    // By using % in URLs, arguments could be validated twice; this eases
    // that pain.
    if (isset($this->argument_validated)) {
      return $this->argument_validated;
    }

    if ($this->is_wildcard($arg)) {
      return $this->argument_validated = TRUE;
    }

    if ($this->options['validate_type'] == 'none') {
      return $this->argument_validated = $this->validate_argument_basic($arg);
    }

    $plugin = $this->get_plugin('argument validator');
    if ($plugin) {
      return $this->argument_validated = $plugin->validate_argument($arg);
    }

    // If the plugin isn't found, fall back to the basic validation path:
    return $this->argument_validated = $this->validate_argument_basic($arg);
  }

  /**
   * Called by the menu system to validate an argument.
   *
   * This checks to see if this is a 'soft fail', which means that if the
   * argument fails to validate, but there is an action to take anyway,
   * then validation cannot actually fail.
   */
  function validate_argument($arg) {
    $validate_info = $this->default_actions($this->options['validate_fail']);
    if (empty($validate_info['hard fail'])) {
      return TRUE;
    }

    $rc = $this->validate_arg($arg);

    // If the validator has changed the validate fail condition to a
    // soft fail, deal with that:
    $validate_info = $this->default_actions($this->options['validate_fail']);
    if (empty($validate_info['hard fail'])) {
      return TRUE;
    }

    return $rc;
  }

  /**
   * Provide a basic argument validation.
   *
   * This can be overridden for more complex types; the basic
   * validator only checks to see if the argument is not NULL
   * or is numeric if the definition says it's numeric.
   */
  function validate_argument_basic($arg) {
    if (!isset($arg) || $arg === '') {
      return FALSE;
    }

    if (!empty($this->definition['numeric']) && !isset($this->options['break_phrase']) && !is_numeric($arg)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Set the input for this argument
   *
   * @return TRUE if it successfully validates; FALSE if it does not.
   */
  function set_argument($arg) {
    $this->argument = $arg;
    return $this->validate_arg($arg);
  }

  /**
   * Get the value of this argument.
   */
  function get_value() {
    // If we already processed this argument, we're done.
    if (isset($this->argument)) {
      return $this->argument;
    }

    // Otherwise, we have to pretend to process ourself to find the value.
    $value = NULL;
    // Find the position of this argument within the view.
    $position = 0;
    foreach ($this->view->argument as $id => $argument) {
      if ($id == $this->options['id']) {
        break;
      }
      $position++;
    }

    $arg = isset($this->view->args[$position]) ? $this->view->args[$position] : NULL;
    $this->position = $position;

    // Clone ourselves so that we don't break things when we're really
    // processing the arguments.
    $argument = clone $this;
    if (!isset($arg) && $argument->has_default_argument()) {
      $arg = $argument->get_default_argument();
    }
    // Set the argument, which will also validate that the argument can be set.
    if ($argument->set_argument($arg)) {
      $value = $argument->argument;
    }
    unset($argument);
    return $value;
  }

  /**
   * Special handling for the style export.
   *
   * Arguments can have styles for the summary view. This special export
   * handler makes sure this works properly.
   */
  function export_style($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    $name = $storage[$option];
    $options = $storage['style_options'];

    $plugin = views_get_plugin('style', $name);
    if ($plugin) {
      $plugin->init($this->view, $this->display, $options);
      // Write which plugin to use.
      $output .= $indent . $prefix . "['$option'] = '$name';\n";

      // Pass off to the plugin to export itself.
      $output .= $plugin->export_options($indent, $prefix . "['style_options']");
    }

    return $output;
  }

  /**
   * Special handling for the style export.
   *
   * Arguments can have styles for the summary view. This special export
   * handler makes sure this works properly.
   */
  function export_plugin($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    if ($option == 'default_argument_type') {
      $type = 'argument default';
      $option_name = 'default_argument_options';
    }
    else {
      $type = 'argument validator';
      $option_name = 'validate_options';
    }
    $plugin = $this->get_plugin($type);
    $name = $this->options[$option];

    if ($plugin) {
      // Write which plugin to use.
      $output .= $indent . $prefix . "['$option'] = '$name';\n";

      // Pass off to the plugin to export itself.
      $output .= $plugin->export_options($indent, $prefix . "['$option_name']");
    }

    return $output;
  }

  /**
   * Get the display or row plugin, if it exists.
   */
  function get_plugin($type = 'argument default', $name = NULL) {
    $options = array();
    switch ($type) {
      case 'argument default':
        $plugin_name = 'default_argument_type';
        $options_name = 'default_argument_options';
        break;
      case 'argument validator':
        $plugin_name = 'validate_type';
        $options_name = 'validate_options';
    }

    if (!$name) {
      $name = $this->options[$plugin_name];
    }

    // we only fetch the options if we're fetching the plugin actually
    // in use.
    if ($name == $this->options[$plugin_name]) {
      $options = $this->options[$options_name];
    }

    $plugin = views_get_plugin($type, $name);
    if ($plugin) {
      $plugin->init($this->view, $this, $options);
      return $plugin;
    }
  }
}

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_broken extends views_handler_argument {
  function ui_name($short = FALSE) {
    return t('Broken/missing handler');
  }

  function ensure_my_table() { /* No table to ensure! */ }
  function query() { /* No query to run */ }
  function options_form(&$form, &$form_state) {
    $form['markup'] = array(
      '#markup' => '<div class="form-item description">' . t('The handler for this item is broken or missing and cannot be used. If a module provided the handler and was disabled, re-enabling the module may restore it. Otherwise, you should probably delete this item.') . '</div>',
    );
  }

  /**
   * Determine if the handler is considered 'broken'
   */
  function broken() { return TRUE; }
}

/**
 * @}
 */
