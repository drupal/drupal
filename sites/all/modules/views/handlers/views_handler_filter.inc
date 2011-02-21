<?php
// $Id: views_handler_filter.inc,v 1.10.4.15 2010/12/24 13:42:00 dereine Exp $
/**
 * @defgroup views_filter_handlers Views' filter handlers
 * @{
 * Handlers to tell Views how to filter queries.
 *
 * Definition items:
 * - allow empty: If true, the 'IS NULL' and 'IS NOT NULL' operators become
 *   available as standard operators.
 * -
 */

/**
 * Base class for filters.
 */
class views_handler_filter extends views_handler {
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

    // Compatibility: Set use_operator to true if the old way of using
    // the operator is set and use_operator is NULL (was never set).
    if (!empty($options['exposed']) && !empty($options['expose']['operator']) && !isset($options['expose']['use_operator'])) {
      $this->options['expose']['use_operator'] = TRUE;
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
    $options['group'] = array('default' => '0');
    $options['exposed'] = array('default' => FALSE);
    $options['expose'] = array(
      'contains' => array(
        'operator' => array('default' => FALSE),
        'label' => array('default' => '', 'translatable' => TRUE),
        'use_operator' => array('default' => 0),
        'operator' => array('default' => ''),
        'identifier' => array('default' => ''),
        'optional' => array('default' => 1),
        'remember' => array('default' => 0),
        'single' => array('default' => 1),
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
    $form['op_val_start'] = array('#markup' => '<div class="clearfix">');
    $this->show_operator_form($form, $form_state);
    $this->show_value_form($form, $form_state);
    $form['op_val_end'] = array('#markup' => '</div>');
    if ($this->can_expose()) {
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
    $form['operator']['#prefix'] = '<div class="views-left-30">';
    $form['operator']['#suffix'] = '</div>';
  }

  /**
   * Provide a form for setting the operator.
   *
   * This may be overridden by child classes, and it must
   * define $form['operator'];
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
      $form['value']['#prefix'] = '<div class="views-right-70">' . (isset($form['value']['#prefix']) ? $form['value']['#prefix'] : '');
      $form['value']['#suffix'] = (isset($form['value']['#suffix']) ? $form['value']['#suffix'] : '') . '</div>';
    }
  }

  /**
   * Provide a form for setting options.
   *
   * This should be overridden by all child classes and it must
   * define $form['value']
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
   * Handle the 'left' side fo the exposed options form.
   */
  function expose_form_left(&$form, &$form_state) {
    if (!empty($form['operator']['#type'])) {
      $form['expose']['use_operator'] = array(
        '#type' => 'checkbox',
        '#title' => t('Unlock operator'),
        '#description' => t('When checked, the operator will be exposed to the user'),
        '#default_value' => !empty($this->options['expose']['use_operator']),
      );
      $form['expose']['operator'] = array(
        '#type' => 'textfield',
        '#default_value' => $this->options['expose']['operator'],
        '#title' => t('Operator identifier'),
        '#size' => 40,
        '#description' => t('This will appear in the URL after the ? to identify this operator.'),
        '#process' => array('ctools_dependent_process'),
        '#dependency' => array(
          'edit-options-expose-use-operator' => array(1)
        ),
      );
    }
    else {
      $form['expose']['operator'] = array(
        '#type' => 'value',
        '#value' => '',
      );
    }

    $form['expose']['identifier'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['identifier'],
      '#title' => t('Filter identifier'),
      '#size' => 40,
      '#description' => t('This will appear in the URL after the ? to identify this filter. Cannot be blank.'),
    );
    $form['expose']['label'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['label'],
      '#title' => t('Label'),
      '#size' => 40,
    );
  }

  /**
   * Handle the 'right' side fo the exposed options form.
   */
  function expose_form_right(&$form, &$form_state) {
    $form['expose']['optional'] = array(
      '#type' => 'checkbox',
      '#title' => t('Optional'),
      '#description' => t('This exposed filter is optional and will have added options to allow it not to be set.'),
      '#default_value' => $this->options['expose']['optional'],
    );
    if (empty($this->no_single)) {
      $form['expose']['single'] = array(
        '#type' => 'checkbox',
        '#title' => t('Force single'),
        '#description' => t('Force this exposed filter to accept only one option.'),
        '#default_value' => $this->options['expose']['single'],
      );
    }
    $form['expose']['remember'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remember'),
      '#description' => t('Remember the last setting the user gave this filter.'),
      '#default_value' => $this->options['expose']['remember'],
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
      'label' => $this->ui_name(),
      'remember' => FALSE,
      'single' => TRUE,
      'optional' => TRUE,
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
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator'])) {
      $operator = $this->options['expose']['operator'];
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
      if (empty($form['#no_convert']) || !empty($this->options['expose']['single'])) {
        $form['#type'] = 'select';
      }
      if (empty($this->options['expose']['single'])) {
        $form['#multiple'] = TRUE;
      }
    }
    if (!empty($this->options['expose']['single']) && isset($form['#multiple'])) {
      unset($form['#multiple']);
      $form['#size'] = NULL;
    }

    if ($type == 'value' && !empty($this->options['expose']['optional']) && $form['#type'] == 'select' && empty($form['#multiple'])) {
      $any_label = variable_get('views_exposed_filter_any_label', 'old_any') == 'old_any' ? t('<Any>') : t('- Any -');
      $form['#options'] = array('All' => $any_label) + $form['#options'];
      $form['#default_value'] = 'All';
    }
  }

  /**
   * Tell the renderer about our exposed form. This only needs to be
   * overridden for particularly complex forms. And maybe not even then.
   *
   * @return
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
      'operator' => $this->options['expose']['operator'],
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


    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator']) && isset($input[$this->options['expose']['operator']])) {
      $this->operator = $input[$this->options['expose']['operator']];
    }

    if (!empty($this->options['expose']['identifier'])) {
      $value = $input[$this->options['expose']['identifier']];

      // Various ways to check for the absence of optional input.
      if (!empty($this->options['expose']['optional'])) {

        if (($this->operator == 'empty' || $this->operator == 'not empty') && $value === '') {
          $value = ' ';
        }

        if ($this->operator != 'empty' && $this->operator != 'not empty') {
          if ($value == 'All' || $value === array()) {
            return FALSE;
          }
        }

        if (!empty($this->no_single) && $value === '') {
          return FALSE;
        }
      }


      if (isset($value)) {
        $this->value = $value;
        if (empty($this->no_single) && !empty($this->options['expose']['single'])) {
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
    $operator = !empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator']);

    // false means that we got a setting that means to recuse ourselves,
    // so we should erase whatever happened to be there.
    if (!$status && isset($_SESSION['views'][$this->view->name][$display_id])) {
      $session = &$_SESSION['views'][$this->view->name][$display_id];
      if ($operator && isset($session[$this->options['expose']['operator']])) {
        unset($session[$this->options['expose']['operator']]);
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

      if ($operator && isset($input[$this->options['expose']['operator']])) {
        $session[$this->options['expose']['operator']] = $input[$this->options['expose']['operator']];
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
   */
   function can_group() {
     return TRUE;
   }
}


/**
 * A special handler to take the place of missing or broken handlers.
 */
class views_handler_filter_broken extends views_handler_filter {
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
