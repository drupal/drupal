<?php

/**
 * @file
 * @todo.
 */

/**
 * @defgroup views_argument_handlers Views argument handlers
 * Handlers to tell Views how to contextually filter queries.
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
  var $validator = NULL;
  var $argument = NULL;
  var $value = NULL;

  /**
   * The table to use for the name, should it not be in the same table as the argument.
   * @var string
   */
  var $name_table;

  /**
   * The field to use for the name to use in the summary, which is
   * the displayed output. For example, for the node: nid argument,
   * the argument itself is the nid, but node.title is displayed.
   * @var string
   */
  var $name_field;

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

    // Compatibility: The new UI changed several settings.
    if (!empty($options['wildcard']) && !isset($options['exception']['value'])) {
      $this->options['exception']['value'] = $options['wildcard'];
    }
    if (!empty($options['wildcard_substitution']) && !isset($options['exception']['title'])) {
      // Enable the checkbox if the title is filled in.
      $this->options['exception']['title_enable'] = 1;
      $this->options['exception']['title'] = $options['wildcard_substitution'];
    }

    if (!isset($options['summary']['format']) && !empty($options['style_plugin'])) {
      $this->options['summary']['format'] = $options['style_plugin'];
    }

    // Setup default value.
    $options['style_options'] = isset($options['style_options']) ? $options['style_options'] : array();

    if (!isset($options['summary']['sort_order']) && !empty($options['default_action']) && $options['default_action'] == 'summary asc') {
      $this->options['default_action'] = 'summary';
      $this->options['summary']['sort_order'] = 'asc';
      $this->options['summary']['number_of_records'] = 0;
      $this->options['summary_options'] = $options['style_options'];
    }
    elseif (!isset($options['summary']['sort_order']) && !empty($options['default_action']) && $options['default_action'] == 'summary desc') {
      $this->options['default_action'] = 'summary';
      $this->options['summary']['sort_order'] = 'desc';
      $this->options['summary']['number_of_records'] = 0;
      $this->options['summary_options'] = $options['style_options'];
    }
    elseif (!isset($options['summary']['sort_order']) && !empty($options['default_action']) && $options['default_action'] == 'summary asc by count') {
      $this->options['default_action'] = 'summary';
      $this->options['summary']['sort_order'] = 'asc';
      $this->options['summary']['number_of_records'] = 1;
      $this->options['summary_options'] = $options['style_options'];
    }
    elseif (!isset($options['summary']['sort_order']) && !empty($options['default_action']) && $options['default_action'] == 'summary desc by count') {
      $this->options['default_action'] = 'summary';
      $this->options['summary']['sort_order'] = 'desc';
      $this->options['summary']['number_of_records'] = 1;
      $this->options['summary_options'] = $options['style_options'];
    }

    if (!empty($options['title']) && !isset($options['title_enable'])) {
      $this->options['title_enable'] = 1;
    }
    if (!empty($options['breadcrumb']) && !isset($options['breadcrumb_enable'])) {
      $this->options['breadcrumb_enable'] = 1;
    }

    if (!empty($options['validate_type']) && !isset($options['validate']['type'])) {
      $this->options['validate']['type'] = $options['validate_type'];
      $this->options['specify_validation'] = 1;
    }
    if (!empty($options['validate_fail']) && !isset($options['validate']['fail'])) {
      $this->options['validate']['fail'] = $options['validate_fail'];
      $this->options['specify_validation'] = 1;
    }
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

  function is_exception($arg = NULL) {
    if (!isset($arg)) {
      $arg = isset($this->argument) ? $this->argument : NULL;
    }
    return !empty($this->options['exception']['value']) && $this->options['exception']['value'] === $arg;
  }

  function exception_title() {
    // If title overriding is off for the exception, return the normal title.
    if (empty($this->options['exception']['title_enable'])) {
      return $this->get_title();
    }
    return $this->options['exception']['title'];
  }

  /**
   * Determine if the argument needs a style plugin.
   *
   * @return TRUE/FALSE
   */
  function needs_style_plugin() {
    $info = $this->default_actions($this->options['default_action']);
    $validate_info = $this->default_actions($this->options['validate']['fail']);
    return !empty($info['style plugin']) || !empty($validate_info['style plugin']);
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['default_action'] = array('default' => 'ignore');
    $options['exception'] = array(
      'contains' => array(
        'value' => array('default' => 'all'),
        'title_enable' => array('default' => FALSE, 'bool' => TRUE),
        'title' => array('default' => t('All'), 'translatable' => TRUE),
      ),
    );
    $options['title_enable'] = array('default' => FALSE, 'bool' => TRUE);
    $options['title'] = array('default' => '', 'translatable' => TRUE);
    $options['breadcrumb_enable'] = array('default' => FALSE, 'bool' => TRUE);
    $options['breadcrumb'] = array('default' => '', 'translatable' => TRUE);
    $options['default_argument_type'] = array('default' => 'fixed', 'export' => 'export_plugin');
    $options['default_argument_options'] = array('default' => array(), 'export' => FALSE);
    $options['default_argument_skip_url'] = array('default' => FALSE, 'bool' => TRUE);
    $options['summary_options'] = array('default' => array(), 'export' => FALSE);
    $options['summary'] = array(
      'contains' => array(
        'sort_order' => array('default' => 'asc'),
        'number_of_records' => array('default' => 0),
        'format' => array('default' => 'default_summary', 'export' => 'export_summary'),
      ),
    );
    $options['specify_validation'] = array('default' => FALSE, 'bool' => TRUE);
    $options['validate'] = array(
      'contains' => array(
        'type' => array('default' => 'none', 'export' => 'export_validation'),
        'fail' => array('default' => 'not found'),
      ),
    );
    $options['validate_options'] = array('default' => array(), 'export' => FALSE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $argument_text = $this->view->display_handler->get_argument_text();

    $form['#pre_render'][] = 'views_ui_pre_render_move_argument_options';

    $form['description'] = array(
      '#markup' => $argument_text['description'],
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('description')),
    );

    $form['no_argument'] = array(
      '#type' => 'fieldset',
      '#title' => $argument_text['filter value not present'],
    );
    // Everything in the fieldset is floated, so the last element needs to
    // clear those floats.
    $form['no_argument']['clearfix'] = array(
      '#weight' => 1000,
      '#markup' => '<div class="clearfix"></div>',
    );
    $form['default_action'] = array(
      '#type' => 'radios',
      '#process' => array('views_ui_process_container_radios'),
      '#default_value' => $this->options['default_action'],
      '#fieldset' => 'no_argument',
    );

    $form['exception'] = array(
      '#type' => 'fieldset',
      '#title' => t('Exceptions'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#fieldset' => 'no_argument',
    );
    $form['exception']['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Exception value'),
      '#size' => 20,
      '#default_value' => $this->options['exception']['value'],
      '#description' => t('If this value is received, the filter will be ignored; i.e, "all values"'),
    );
    $form['exception']['title_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override title'),
      '#default_value' => $this->options['exception']['title_enable'],
    );
    $form['exception']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Override title'),
      '#title_display' => 'invisible',
      '#size' => 20,
      '#default_value' => $this->options['exception']['title'],
      '#description' => t('Override the view and other argument titles. Use "%1" for the first argument, "%2" for the second, etc.'),
      '#dependency' => array(
        'edit-options-exception-title-enable' => array('1'),
      ),
    );

    $options = array();
    $defaults = $this->default_actions();
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

    $form['argument_present'] = array(
      '#type' => 'fieldset',
      '#title' => $argument_text['filter value present'],
    );
    $form['title_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override title'),
      '#default_value' => $this->options['title_enable'],
      '#fieldset' => 'argument_present',
    );
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide title'),
      '#title_display' => 'invisible',
      '#default_value' => $this->options['title'],
      '#description' => t('Override the view and other argument titles. Use "%1" for the first argument, "%2" for the second, etc.'),
      '#dependency' => array(
        'edit-options-title-enable' => array('1'),
      ),
      '#fieldset' => 'argument_present',
    );

    $form['breadcrumb_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override breadcrumb'),
      '#default_value' => $this->options['breadcrumb_enable'],
      '#fieldset' => 'argument_present',
    );
    $form['breadcrumb'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide breadcrumb'),
      '#title_display' => 'invisible',
      '#default_value' => $this->options['breadcrumb'],
      '#description' => t('Enter a breadcrumb name you would like to use. See "Title" for percent substitutions.'),
      '#dependency' => array(
        'edit-options-breadcrumb-enable' => array('1'),
      ),
      '#fieldset' => 'argument_present',
    );

    $form['specify_validation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Specify validation criteria'),
      '#default_value' => $this->options['specify_validation'],
      '#fieldset' => 'argument_present',
    );

    $form['validate'] = array(
      '#type' => 'container',
      '#fieldset' => 'argument_present',
    );
    // @todo The mockup wanted to use "Validate using" here, but it doesn't
    // work well with many options (they'd need to be changed as well)
    $form['validate']['type'] = array(
      '#type' => 'select',
      '#title' => t('Validator'),
      '#default_value' => $this->options['validate']['type'],
      '#dependency' => array(
        'edit-options-specify-validation' => array('1'),
      ),
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
          if ($plugin->access() || $this->options['validate']['type'] == $id) {
            $form['validate']['options'][$id] = array(
              '#prefix' => '<div id="edit-options-validate-options-' . $id . '-wrapper">',
              '#suffix' => '</div>',
              '#type' => 'item',
              // Even if the plugin has no options add the key to the form_state.
              '#input' => TRUE, // trick it into checking input to make #process run
              '#dependency' => array(
                'edit-options-specify-validation' => array('1'),
                'edit-options-validate-type' => array($id),
              ),
              '#dependency_count' => 2,
              '#id' => 'edit-options-validate-options-' . $id,
            );
            $plugin->options_form($form['validate']['options'][$id], $form_state);
            $validate_types[$id] = $info['title'];
          }
        }
      }
    }

    asort($validate_types);
    $form['validate']['type']['#options'] = $validate_types;

    $form['validate']['fail'] = array(
      '#type' => 'select',
      '#title' => t('Action to take if filter value does not validate'),
      '#default_value' => $this->options['validate']['fail'],
      '#options' => $validate_options,
      '#dependency' => array(
        'edit-options-specify-validation' => array('1'),
      ),
      '#fieldset' => 'argument_present',
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

    // summary plugin
    $summary_id = $form_state['values']['options']['summary']['format'];
    $plugin = $this->get_plugin('style', $summary_id);
    if ($plugin) {
      $plugin->options_validate($form['summary']['options'][$summary_id], $form_state, $form_state['values']['options']['summary']['options'][$summary_id]);
    }

    $validate_id = $form_state['values']['options']['validate']['type'];
    $plugin = $this->get_plugin('argument validator', $validate_id);
    if ($plugin) {
      $plugin->options_validate($form['validate']['options'][$default_id], $form_state, $form_state['values']['options']['validate']['options'][$validate_id]);
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

    // summary plugin
    $summary_id = $form_state['values']['options']['summary']['format'];
    $plugin = $this->get_plugin('style', $summary_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['summary']['options'][$summary_id];
      $plugin->options_submit($form['summary']['options'][$summary_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['summary_options'] = $options;
    }

    $validate_id = $form_state['values']['options']['validate']['type'];
    $plugin = $this->get_plugin('argument validator', $validate_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['validate']['options'][$validate_id];
      $plugin->options_submit($form['validate']['options'][$validate_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['validate_options'] = $options;
    }

    // Clear out the content of title if it's not enabled.
    $options =& $form_state['values']['options'];
    if (empty($options['title_enable'])) {
      $options['title'] = '';
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
        'title' => t('Display all results for the specified field'),
        'method' => 'default_ignore',
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'default' => array(
        'title' => t('Provide default value'),
        'method' => 'default_default',
        'form method' => 'default_argument_form',
        'has default argument' => TRUE,
        'default only' => TRUE, // this can only be used for missing argument, not validation failure
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'not found' => array(
        'title' => t('Hide view'),
        'method' => 'default_not_found',
        'hard fail' => TRUE, // This is a hard fail condition
      ),
      'summary' => array(
        'title' => t('Display a summary'),
        'method' => 'default_summary',
        'form method' => 'default_summary_form',
        'style plugin' => TRUE,
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'empty' => array(
        'title' => t('Display contents of "No results found"'),
        'method' => 'default_empty',
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'access denied' => array(
        'title' => t('Display "Access Denied"'),
        'method' => 'default_access_denied',
        'breadcrumb' => FALSE, // generate a breadcrumb to here
      ),
    );

    if ($this->view->display_handler->has_path()) {
      $defaults['not found']['title'] = t('Show "Page not found"');
    }

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

    $form['default_argument_skip_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip default argument for view URL'),
      '#default_value' => $this->options['default_argument_skip_url'],
      '#description' => t('Select whether to include this default argument when constructing the URL for this view. Skipping default arguments is useful e.g. in the case of feeds.')
    );

    $form['default_argument_type'] = array(
      '#prefix' => '<div id="edit-options-default-argument-type-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#id' => 'edit-options-default-argument-type',
      '#title' => t('Type'),
      '#default_value' => $this->options['default_argument_type'],

      '#dependency' => array('radio:options[default_action]' => array('default')),
      // Views custom key, moves this element to the appropriate container
      // under the radio button.
      '#argument_option' => 'default',
    );

    foreach ($plugins as $id => $info) {
      if (!empty($info['no ui'])) {
        continue;
      }
      $plugin = $this->get_plugin('argument default', $id);
      if ($plugin) {
        if ($plugin->access() || $this->options['default_argument_type'] == $id) {
          $form['argument_default']['#argument_option'] = 'default';
          $form['argument_default'][$id] = array(
            '#prefix' => '<div id="edit-options-argument-default-options-' . $id . '-wrapper">',
            '#suffix' => '</div>',
            '#id' => 'edit-options-argument-default-options-' . $id,
            '#type' => 'item',
            // Even if the plugin has no options add the key to the form_state.
            '#input' => TRUE,
            '#dependency' => array(
              'radio:options[default_action]' => array('default'),
              'edit-options-default-argument-type' => array($id)
            ),
            '#dependency_count' => 2,
          );
          $options[$id] = $info['title'];
          $plugin->options_form($form['argument_default'][$id], $form_state);
        }
      }
    }

    asort($options);
    $form['default_argument_type']['#options'] = $options;
  }

  /**
   * Provide a form for selecting further summary options when the
   * default action is set to display one.
   */
  function default_summary_form(&$form, &$form_state) {
    $style_plugins = views_fetch_plugin_data('style');
    $summary_plugins = array();
    $format_options = array();
    foreach ($style_plugins as $key => $plugin) {
      if (isset($plugin['type']) && $plugin['type'] == 'summary') {
        $summary_plugins[$key] = $plugin;
        $format_options[$key] = $plugin['title'];
      }
    }

    $form['summary'] = array(
      // Views custom key, moves this element to the appropriate container
      // under the radio button.
      '#argument_option' => 'summary',
    );
    $form['summary']['sort_order'] = array(
      '#type' => 'radios',
      '#title' => t('Sort order'),
      '#options' => array('asc' => t('Ascending'), 'desc' => t('Descending')),
      '#default_value' => $this->options['summary']['sort_order'],
      '#dependency' => array('radio:options[default_action]' => array('summary')),
    );
    $form['summary']['number_of_records'] = array(
      '#type' => 'radios',
      '#title' => t('Sort by'),
      '#default_value' => $this->options['summary']['number_of_records'],
      '#options' => array(
        0 => $this->get_sort_name(),
        1 => t('Number of records')
      ),
      '#dependency' => array('radio:options[default_action]' => array('summary')),
    );

    $form['summary']['format'] = array(
      '#type' => 'radios',
      '#title' => t('Format'),
      '#options' => $format_options,
      '#default_value' => $this->options['summary']['format'],
      '#dependency' => array('radio:options[default_action]' => array('summary')),
    );

    foreach ($summary_plugins as $id => $info) {
      if (empty($info['uses options'])) {
        continue;
      }
      $plugin = $this->get_plugin('style', $id);
      if ($plugin) {
        $form['summary']['options'][$id] = array(
          '#prefix' => '<div id="edit-options-summary-options-' . $id . '-wrapper">',
          '#suffix' => '</div>',
          '#id' => 'edit-options-summary-options-' . $id,
          '#type' => 'item',
          '#input' => TRUE, // trick it into checking input to make #process run
          '#dependency' => array(
            'radio:options[default_action]' => array('summary'),
            'radio:options[summary][format]' => array($id),
          ),
          '#dependency_count' => 2,
        );
        $options[$id] = $info['title'];
        $plugin->options_form($form['summary']['options'][$id], $form_state);
      }
    }
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
    $info = $this->default_actions($this->options['validate']['fail']);
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
   * Default action: access denied.
   *
   * If an argument was expected and was not given, in this case, report
   * the view as 'access denied'.
   */
  function default_access_denied() {
    $this->view->build_info['denied'] = TRUE;
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
   * Process the summary arguments for display.
   *
   * For example, the validation plugin may want to alter an argument for use in
   * the URL.
   */
  function process_summary_arguments(&$args) {
    if ($this->options['validate']['type'] != 'none') {
      if (isset($this->validator) || $this->validator = $this->get_plugin('argument validator')) {
        $this->validator->process_summary_arguments($args);
      }
    }
  }

  /**
   * Default action: summary.
   *
   * If an argument was expected and was not given, in this case, display
   * a summary query.
   */
  function default_summary() {
    $this->view->build_info['summary'] = TRUE;
    $this->view->build_info['summary_level'] = $this->options['id'];

    // Change the display style to the summary style for this
    // argument.
    $this->view->plugin_name = $this->options['summary']['format'];
    $this->view->style_options = $this->options['summary_options'];

    // Clear out the normal primary field and whatever else may have
    // been added and let the summary do the work.
    $this->query->clear_fields();
    $this->summary_query();

    $by = $this->options['summary']['number_of_records'] ? 'num_records' : NULL;
    $this->summary_sort($this->options['summary']['sort_order'], $by);

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
      if ($this->table_alias != $this->name_table) {
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
  function query($group_by = FALSE) {
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

    if ($this->is_exception($arg)) {
      return $this->argument_validated = TRUE;
    }

    if ($this->options['validate']['type'] == 'none') {
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
    $validate_info = $this->default_actions($this->options['validate']['fail']);
    if (empty($validate_info['hard fail'])) {
      return TRUE;
    }

    $rc = $this->validate_arg($arg);

    // If the validator has changed the validate fail condition to a
    // soft fail, deal with that:
    $validate_info = $this->default_actions($this->options['validate']['fail']);
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

      // remember that this argument was computed, not passed on the URL.
      $this->is_default = TRUE;
    }
    // Set the argument, which will also validate that the argument can be set.
    if ($argument->set_argument($arg)) {
      $value = $argument->argument;
    }
    unset($argument);
    return $value;
  }

  /**
   * Export handler for summary export.
   *
   * Arguments can have styles for the summary view. This special export
   * handler makes sure this works properly.
   */
  function export_summary($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    $name = $this->options['summary'][$option];
    $options = $this->options['summary_options'];

    $plugin = views_get_plugin('style', $name);
    if ($plugin) {
      $plugin->init($this->view, $this->view->display_handler->display, $options);
      // Write which plugin to use.
      $output .= $indent . $prefix . "['summary']['$option'] = '$name';\n";

      // Pass off to the plugin to export itself.
      $output .= $plugin->export_options($indent, $prefix . "['summary_options']");
    }

    return $output;
  }

  /**
   * Export handler for validation export.
   *
   * Arguments use validation plugins. This special export handler makes sure
   * this works properly.
   */
  function export_validation($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    $name = $this->options['validate'][$option];
    $options = $this->options['validate_options'];

    $plugin = views_get_plugin('argument validator', $name);
    if ($plugin) {
      $plugin->init($this->view, $this->display, $options);
      // Write which plugin to use.
      $output .= $indent . $prefix . "['validate']['$option'] = '$name';\n";

      // Pass off to the plugin to export itself.
      $output .= $plugin->export_options($indent, $prefix . "['validate_options']");
    }

    return $output;
  }

  /**
   * Generic plugin export handler.
   *
   * Since style and validation plugins have their own export handlers, this
   * one is currently only used for default argument plugins.
   */
  function export_plugin($indent, $prefix, $storage, $option, $definition, $parents) {
    $output = '';
    if ($option == 'default_argument_type') {
      $type = 'argument default';
      $option_name = 'default_argument_options';
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
        $plugin_name = $this->options['default_argument_type'];
        $options_name = 'default_argument_options';
        break;
      case 'argument validator':
        $plugin_name = $this->options['validate']['type'];
        $options_name = 'validate_options';
        break;
      case 'style':
        $plugin_name = $this->options['summary']['format'];
        $options_name = 'summary_options';
    }

    if (!$name) {
      $name = $plugin_name;
    }

    // we only fetch the options if we're fetching the plugin actually
    // in use.
    if ($name == $plugin_name) {
      $options = $this->options[$options_name];
    }

    $plugin = views_get_plugin($type, $name);
    if ($plugin) {
      // Style plugins expects different parameters as argument related plugins.
      if ($type == 'style') {
        $plugin->init($this->view, $this->view->display_handler->display, $options);
      }
      else {
        $plugin->init($this->view, $this, $options);
      }
      return $plugin;
    }
  }

  /**
   * Return a description of how the argument would normally be sorted.
   *
   * Subclasses should override this to specify what the default sort order of
   * their argument is (e.g. alphabetical, numeric, date).
   */
  function get_sort_name() {
    return t('Default sort', array(), array('context' => 'Sort order'));
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
  function query($group_by = FALSE) { /* No query to run */ }
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
