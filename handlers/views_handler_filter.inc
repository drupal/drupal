<?php

/**
 * @file
 * @todo.
 */

/**
 * @defgroup views_filter_handlers Views filter handlers
 * @{
 * Handlers to tell Views how to filter queries.
 *
 * Definition items:
 * - allow empty: If true, the 'IS NULL' and 'IS NOT NULL' operators become
 *   available as standard operators.
 *
 * Object flags:
 *  You can set some specific behavior by setting up the following flags on
 *  your custom class.
 *
 * - always_multiple:
 *    Disable the possibility to force a single value.
 * - no_operator:
 *    Disable the possibility to use operators.
 * - always_required:
 *    Disable the possibility to allow a exposed input to be optional.
 */

/**
 * Base class for filters.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter extends views_handler {
  /**
   * Contains the actual value of the field,either configured in the views ui
   * or entered in the exposed filters.
   */
  var $value = NULL;

  /**
   * Contains the operator which is used on the query.
   */
  var $operator = '=';

  /**
   * @var bool
   * Disable the possibility to force a single value.
   */
  var $always_multiple = FALSE;

  /**
   * @var bool
   * Disable the possibility to use operators.
   */
  var $no_operator = FALSE;

  /**
   * @var bool
   * Disable the possibility to allow a exposed input to be optional.
   */
  var $always_required = FALSE;

  /**
   * Provide some extra help to get the operator/value easier to use.
   *
   * This likely has to be overridden by filters which are more complex
   * than simple operator/value.
   */
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->operator = $this->options['operator'];
    $this->value = $this->options['value'];

    // Compatibility: The new UI changed several settings.
    if (!empty($options['exposed']) && !empty($options['expose']['optional']) && !isset($options['expose']['required'])) {
      $this->options['expose']['required'] = !$options['expose']['optional'];
    }
    if (!empty($options['exposed']) && !empty($options['expose']['single']) && !isset($options['expose']['multiple'])) {
      $this->options['expose']['multiple'] = !$options['expose']['single'];
    }
    if (!empty($options['exposed']) && !empty($options['expose']['operator']) && !isset($options['expose']['operator_id'])) {
      $this->options['expose']['operator_id'] = $options['expose']['operator_id'] = $options['expose']['operator'];
    }

    // If there are relationships in the view, allow empty should be true
    // so that we can do IS NULL checks on items. Not all filters respect
    // allow empty, but string and numeric do and that covers enough.
    if ($this->view->display_handler->get_option('relationships')) {
      $this->definition['allow empty'] = TRUE;
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['operator'] = array('default' => '=');
    $options['value'] = array('default' => '');
    $options['group'] = array('default' => '1');
    $options['exposed'] = array('default' => FALSE, 'bool' => TRUE);
    $options['expose'] = array(
      'contains' => array(
        'operator_id' => array('default' => FALSE),
        'label' => array('default' => '', 'translatable' => TRUE),
        'use_operator' => array('default' => FALSE, 'bool' => TRUE),
        'operator' => array('default' => ''),
        'identifier' => array('default' => ''),
        'required' => array('default' => FALSE, 'bool' => TRUE),
        'remember' => array('default' => FALSE, 'bool' => TRUE),
        'multiple' => array('default' => FALSE, 'bool' => TRUE),
      ),
    );

    return $options;
  }

  /**
   * Display the filter on the administrative summary
   */
  function admin_summary() {
    return check_plain((string) $this->operator) . ' ' . check_plain((string) $this->value);
  }

  /**
   * Determine if a filter can be exposed.
   */
  function can_expose() { return TRUE; }

  /**
   * Provide the basic form which calls through to subforms.
   * If overridden, it is best to call through to the parent,
   * or to at least make sure all of the functions in this form
   * are called.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if ($this->can_expose()) {
      $this->show_expose_button($form, $form_state);
    }
    $form['clear_markup_start'] = array(
      '#markup' => '<div class="clearfix">',
    );
    // Add the subform from operator_form().
    $this->show_operator_form($form, $form_state);
    // Add the subform from value_form().
    $this->show_value_form($form, $form_state);
    $form['clear_markup_end'] = array(
      '#markup' => '</div>',
    );
    if ($this->can_expose()) {
      // Add the subform from expose_form().
      $this->show_expose_form($form, $form_state);
    }
  }

  /**
   * Simple validate handler
   */
  function options_validate(&$form, &$form_state) {
    $this->operator_validate($form, $form_state);
    $this->value_validate($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->expose_validate($form, $form_state);
    }
  }

  /**
   * Simple submit handler
   */
  function options_submit(&$form, &$form_state) {
    unset($form_state['values']['expose_button']); // don't store this.
    $this->operator_submit($form, $form_state);
    $this->value_submit($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->expose_submit($form, $form_state);
    }
  }

  /**
   * Shortcut to display the operator form.
   */
  function show_operator_form(&$form, &$form_state) {
    $this->operator_form($form, $form_state);
    $form['operator']['#prefix'] = '<div class="views-group-box views-left-30">';
    $form['operator']['#suffix'] = '</div>';
  }

  /**
   * Options form subform for setting the operator.
   *
   * This may be overridden by child classes, and it must
   * define $form['operator'];
   *
   * @see options_form()
   */
  function operator_form(&$form, &$form_state) {
    $options = $this->operator_options();
    if (!empty($options)) {
      $form['operator'] = array(
        '#type' => count($options) < 10 ? 'radios' : 'select',
        '#title' => t('Operator'),
        '#default_value' => $this->operator,
        '#options' => $options,
      );
    }
  }

  /**
   * Provide a list of options for the default operator form.
   * Should be overridden by classes that don't override operator_form
   */
  function operator_options() { return array(); }

  /**
   * Validate the operator form.
   */
  function operator_validate($form, &$form_state) { }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function operator_submit($form, &$form_state) { }

  /**
   * Shortcut to display the value form.
   */
  function show_value_form(&$form, &$form_state) {
    $this->value_form($form, $form_state);
    if (empty($this->no_operator)) {
      $form['value']['#prefix'] = '<div class="views-group-box views-right-70">' . (isset($form['value']['#prefix']) ? $form['value']['#prefix'] : '');
      $form['value']['#suffix'] = (isset($form['value']['#suffix']) ? $form['value']['#suffix'] : '') . '</div>';
    }
  }

  /**
   * Options form subform for setting options.
   *
   * This should be overridden by all child classes and it must
   * define $form['value']
   *
   * @see options_form()
   */
  function value_form(&$form, &$form_state) { $form['value'] = array(); }

  /**
   * Validate the options form.
   */
  function value_validate($form, &$form_state) { }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function value_submit($form, &$form_state) { }

  /**
   * Shortcut to display the expose/hide button.
   */
  function show_expose_button(&$form, &$form_state) {
    $form['expose_button'] = array(
      '#prefix' => '<div class="views-expose clearfix">',
      '#suffix' => '</div>',
      // Should always come after the description and the relationship.
      '#weight' => -200,
    );

    // Add a checkbox for JS users, which will have behavior attached to it
    // so it can replace the button.
    $form['expose_button']['checkbox'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('js-only')),
    );
    $form['expose_button']['checkbox']['checkbox'] = array(
      '#title' => t('Expose this filter to visitors, to allow them to change it'),
      '#type' => 'checkbox',
    );

    // Then add the button itself.
    if (empty($this->options['exposed'])) {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description">' . t('This filter is not exposed. Expose it to allow the users to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => t('Expose filter'),
        '#submit' => array('views_ui_config_item_form_expose'),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 0;
    }
    else {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description">' . t('This filter is exposed. If you hide it, users will not be able to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => t('Hide filter'),
        '#submit' => array('views_ui_config_item_form_expose'),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 1;
    }
  }

  /**
   * Options form subform for exposed filter options.
   *
   * @see options_form()
   */
  function expose_form(&$form, &$form_state) {
    $form['#theme'] = 'views_ui_expose_filter_form';
    // #flatten will move everything from $form['expose'][$key] to $form[$key]
    // prior to rendering. That's why the pre_render for it needs to run first,
    // so that when the next pre_render (the one for fieldsets) runs, it gets
    // the flattened data.
    array_unshift($form['#pre_render'], 'views_ui_pre_render_flatten_data');
    $form['expose']['#flatten'] = TRUE;

    if (empty($this->always_required)) {
      $form['expose']['required'] = array(
        '#type' => 'checkbox',
        '#title' => t('Required'),
        '#default_value' => $this->options['expose']['required'],
      );
    }
    else {
      $form['expose']['required'] = array(
        '#type' => 'value',
        '#value' => TRUE,
      );
    }
    $form['expose']['label'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['label'],
      '#title' => t('Label'),
      '#size' => 40,
    );

    if (!empty($form['operator']['#type'])) {
       // Increase the width of the left (operator) column.
      $form['operator']['#prefix'] = '<div class="views-group-box views-left-40">';
      $form['operator']['#suffix'] = '</div>';
      $form['value']['#prefix'] = '<div class="views-group-box views-right-60">';
      $form['value']['#suffix'] = '</div>';

      $form['expose']['use_operator'] = array(
        '#type' => 'checkbox',
        '#title' => t('Expose operator'),
        '#description' => t('Allow the user to choose the operator.'),
        '#default_value' => !empty($this->options['expose']['use_operator']),
      );
      $form['expose']['operator_id'] = array(
        '#type' => 'textfield',
        '#default_value' => $this->options['expose']['operator_id'],
        '#title' => t('Operator identifier'),
        '#size' => 40,
        '#description' => t('This will appear in the URL after the ? to identify this operator.'),
        '#dependency' => array(
          'edit-options-expose-use-operator' => array(1)
        ),
        '#fieldset' => 'more',
      );
    }
    else {
      $form['expose']['operator_id'] = array(
        '#type' => 'value',
        '#value' => '',
      );
    }

    if (empty($this->always_multiple)) {
      $form['expose']['multiple'] = array(
        '#type' => 'checkbox',
        '#title' => t('Allow multiple selections'),
        '#description' => t('Enable to allow users to select multiple items.'),
        '#default_value' => $this->options['expose']['multiple'],
      );
    }
    $form['expose']['remember'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remember the last selection'),
      '#description' => t('Enable to remember the last selection made by the user.'),
      '#default_value' => $this->options['expose']['remember'],
    );

    $form['expose']['identifier'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['identifier'],
      '#title' => t('Filter identifier'),
      '#size' => 40,
      '#description' => t('This will appear in the URL after the ? to identify this filter. Cannot be blank.'),
      '#fieldset' => 'more',
    );
  }

  /**
   * Validate the options form.
   */
  function expose_validate($form, &$form_state) {
    if (empty($form_state['values']['options']['expose']['identifier'])) {
      form_error($form['expose']['identifier'], t('The identifier is required if the filter is exposed.'));
    }

    if (!empty($form_state['values']['options']['expose']['identifier']) && $form_state['values']['options']['expose']['identifier'] == 'value') {
      form_error($form['expose']['identifier'], t('This identifier is not allowed.'));
    }

    if (!$this->view->display_handler->is_identifier_unique($form_state['id'], $form_state['values']['options']['expose']['identifier'])) {
      form_error($form['expose']['identifier'], t('This identifier is used by another handler.'));
    }
  }

  /**
   * Provide default options for exposed filters.
   */
  function expose_options() {
    $this->options['expose'] = array(
      'use_operator' => FALSE,
      'operator' => $this->options['id'] . '_op',
      'identifier' => $this->options['id'],
      'label' => $this->definition['title'],
      'remember' => FALSE,
      'multiple' => FALSE,
      'required' => FALSE,
    );
  }

  /**
   * Render our chunk of the exposed filter form when selecting
   *
   * You can override this if it doesn't do what you expect.
   */
  function exposed_form(&$form, &$form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    // Build the exposed form, when its based on an operator.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = $this->options['expose']['operator_id'];
      $this->operator_form($form, $form_state);
      $form[$operator] = $form['operator'];

      if (isset($form[$operator]['#title'])) {
        unset($form[$operator]['#title']);
      }

      $this->exposed_translate($form[$operator], 'operator');

      unset($form['operator']);
    }

    // Build the form and set the value based on the identifier.
    if (!empty($this->options['expose']['identifier'])) {
      $value = $this->options['expose']['identifier'];
      $this->value_form($form, $form_state);
      $form[$value] = $form['value'];

      if (isset($form[$value]['#title']) && !empty($form[$value]['#type']) && $form[$value]['#type'] != 'checkbox') {
        unset($form[$value]['#title']);
      }

      $this->exposed_translate($form[$value], 'value');

      if (!empty($form['#type']) && ($form['#type'] == 'checkboxes' || ($form['#type'] == 'select' && !empty($form['#multiple'])))) {
        unset($form[$value]['#default_value']);
      }

      if (!empty($form['#type']) && $form['#type'] == 'select' && empty($form['#multiple'])) {
        $form[$value]['#default_value'] = 'All';
      }

      if ($value != 'value') {
        unset($form['value']);
      }
    }
  }

  /**
   * Make some translations to a form item to make it more suitable to
   * exposing.
   */
  function exposed_translate(&$form, $type) {
    if (!isset($form['#type'])) {
      return;
    }

    if ($form['#type'] == 'radios') {
      $form['#type'] = 'select';
    }
    // Checkboxes don't work so well in exposed forms due to GET conversions.
    if ($form['#type'] == 'checkboxes') {
      if (empty($form['#no_convert']) || empty($this->options['expose']['multiple'])) {
        $form['#type'] = 'select';
      }
      if (!empty($this->options['expose']['multiple'])) {
        $form['#multiple'] = TRUE;
      }
    }
    if (empty($this->options['expose']['multiple']) && isset($form['#multiple'])) {
      unset($form['#multiple']);
      $form['#size'] = NULL;
    }

    // Cleanup in case the translated element's (radios or checkboxes) display value contains html.
    if ($form['#type'] == 'select') {
      $this->prepare_filter_select_options($form['#options']);
    }

    if ($type == 'value' && empty($this->always_required) && empty($this->options['expose']['required']) && $form['#type'] == 'select' && empty($form['#multiple'])) {
      $any_label = variable_get('views_exposed_filter_any_label', 'new_any') == 'old_any' ? t('<Any>') : t('- Any -');
      $form['#options'] = array('All' => $any_label) + $form['#options'];
      $form['#default_value'] = 'All';
    }

    if (!empty($this->options['expose']['required'])) {
      $form['#required'] = TRUE;
    }
  }



  /**
   * Sanitizes the HTML select element's options.
   *
   * The function is recursive to support optgroups.
   */
  function prepare_filter_select_options(&$options) {
    foreach ($options as $value => $label) {
      // Recurse for optgroups.
      if (is_array($label)) {
        $this->prepare_filter_select_options($options[$value]);
      }
      // FAPI has some special value to allow hierarchy.
      // @see _form_options_flatten
      elseif (is_object($label)) {
        $this->prepare_filter_select_options($options[$value]->option);
      }
      else {
        $options[$value] = strip_tags(decode_entities($label));
      }
    }
  }

  /**
   * Tell the renderer about our exposed form. This only needs to be
   * overridden for particularly complex forms. And maybe not even then.
   *
   * @return array|null
   *   An array with the following keys:
   *   - operator: The $form key of the operator. Set to NULL if no operator.
   *   - value: The $form key of the value. Set to NULL if no value.
   *   - label: The label to use for this piece.
   */
  function exposed_info() {
    if (empty($this->options['exposed'])) {
      return;
    }

    return array(
      'operator' => $this->options['expose']['operator_id'],
      'value' => $this->options['expose']['identifier'],
      'label' => $this->options['expose']['label'],
    );
  }

  /**
   * Check to see if input from the exposed filters should change
   * the behavior of this filter.
   */
  function accept_exposed_input($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }


    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    if (!empty($this->options['expose']['identifier'])) {
      $value = $input[$this->options['expose']['identifier']];

      // Various ways to check for the absence of non-required input.
      if (empty($this->options['expose']['required'])) {
        if (($this->operator == 'empty' || $this->operator == 'not empty') && $value === '') {
          $value = ' ';
        }

        if ($this->operator != 'empty' && $this->operator != 'not empty') {
          if ($value == 'All' || $value === array()) {
            return FALSE;
          }
        }

        if (!empty($this->always_multiple) && $value === '') {
          return FALSE;
        }
      }


      if (isset($value)) {
        $this->value = $value;
        if (empty($this->always_multiple) && empty($this->options['expose']['multiple'])) {
          $this->value = array($value);
        }
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

  function store_exposed_input($input, $status) {
    if (empty($this->options['exposed']) || empty($this->options['expose']['identifier'])) {
      return TRUE;
    }

    if (empty($this->options['expose']['remember'])) {
      return;
    }

    // Figure out which display id is responsible for the filters, so we
    // know where to look for session stored values.
    $display_id = ($this->view->display_handler->is_defaulted('filters')) ? 'default' : $this->view->current_display;

    // shortcut test.
    $operator = !empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']);

    // false means that we got a setting that means to recuse ourselves,
    // so we should erase whatever happened to be there.
    if (!$status && isset($_SESSION['views'][$this->view->name][$display_id])) {
      $session = &$_SESSION['views'][$this->view->name][$display_id];
      if ($operator && isset($session[$this->options['expose']['operator_id']])) {
        unset($session[$this->options['expose']['operator_id']]);
      }

      if (isset($session[$this->options['expose']['identifier']])) {
        unset($session[$this->options['expose']['identifier']]);
      }
    }

    if ($status) {
      if (!isset($_SESSION['views'][$this->view->name][$display_id])) {
        $_SESSION['views'][$this->view->name][$display_id] = array();
      }

      $session = &$_SESSION['views'][$this->view->name][$display_id];

      if ($operator && isset($input[$this->options['expose']['operator_id']])) {
        $session[$this->options['expose']['operator_id']] = $input[$this->options['expose']['operator_id']];
      }

      $session[$this->options['expose']['identifier']] = $input[$this->options['expose']['identifier']];
    }
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  function query() {
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field", $this->value, $this->operator);
  }

  /**
   * Can this filter be used in OR groups?
   *
   * Some filters have complicated where clauses that cannot be easily used
   * with OR groups. Some filters must also use HAVING which also makes
   * them not groupable. These filters will end up in a special group
   * if OR grouping is in use.
   *
   * @return bool
   */
   function can_group() {
     return TRUE;
   }
}


/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_broken extends views_handler_filter {
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
