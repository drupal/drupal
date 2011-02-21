<?php
// $Id: views_content_plugin_display_panel_pane.inc,v 1.7 2011/01/01 01:27:38 merlinofchaos Exp $

/**
 * The plugin that handles a panel_pane.
 */
class views_content_plugin_display_panel_pane extends views_plugin_display {
  /**
   * If this variable is true, this display counts as a panel pane. We use
   * this variable so that other modules can create alternate pane displays.
   */
  var $panel_pane_display = TRUE;
  var $has_pane_conf = NULL;

  function option_definition() {
    $options = parent::option_definition();

    $options['pane_title'] = array('default' => '', 'translatable' => TRUE);
    $options['pane_description'] = array('default' => '', 'translatable' => TRUE);
    $options['pane_category'] = array(
      'contains' => array(
        'name' => array('default' => 'View panes', 'translatable' => TRUE),
        'weight' => array('default' => 0),
      ),
    );

    $options['allow'] = array(
      'contains' => array(
        'use_pager'       => array('default' => FALSE),
        'items_per_page'  => array('default' => FALSE),
        'offset'          => array('default' => FALSE),
        'link_to_view'    => array('default' => FALSE),
        'more_link'       => array('default' => FALSE),
        'path_override'   => array('default' => FALSE),
        'title_override'  => array('default' => FALSE),
        'exposed_form'    => array('default' => FALSE),
        'fields_override' => array('default' => FALSE),
       ),
    );

    $options['argument_input'] = array('default' => array());
    $options['link_to_view'] = array('default' => 0);
    $options['inherit_panels_path'] = array('default' => 0);

    return $options;
  }

  function has_pane_conf() {
    return isset($this->has_pane_conf);
  }

  function set_pane_conf($conf = array()) {
    $this->set_option('pane_conf', $conf);
    $this->has_pane_conf = TRUE;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  function options_summary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::options_summary($categories, $options);

    $categories['panel_pane'] = array(
      'title' => t('Pane settings'),
    );

    $pane_title = $this->get_option('pane_title');
    if (empty($pane_title)) {
      $pane_title = t('Use view name');
    }

    if (strlen($pane_title) > 16) {
      $pane_title = substr($pane_title, 0, 16) . '...';
    }

    $options['pane_title'] = array(
      'category' => 'panel_pane',
      'title' => t('Admin title'),
      'value' => $pane_title,
    );

    $pane_description = $this->get_option('pane_description');
    if (empty($pane_description)) {
      $pane_description = t('Use view description');
    }

    if (strlen($pane_description) > 16) {
      $pane_description = substr($pane_description, 0, 16) . '...';
    }

    $options['pane_description'] = array(
      'category' => 'panel_pane',
      'title' => t('Admin desc'),
      'value' => $pane_description,
    );

    $category = $this->get_option('pane_category');
    $pane_category = $category['name'];
    if (empty($pane_category)) {
      $pane_category = t('View panes');
    }

    if (strlen($pane_category) > 16) {
      $pane_category = substr($pane_category, 0, 16) . '...';
    }

    $options['pane_category'] = array(
      'category' => 'panel_pane',
      'title' => t('Category'),
      'value' => $pane_category,
    );

    $options['link_to_view'] = array(
      'category' => 'panel_pane',
      'title' => t('Link to view'),
      'value' => $this->get_option('link_to_view') ? t('Yes') : t('No'),
    );

    $options['inherit_panels_path'] = array(
      'category' => 'panel_pane',
      'title' => t('Use Panel path'),
      'value' => $this->get_option('inherit_panels_path') ? t('Yes') : t('No'),
    );

    $options['argument_input'] = array(
      'category' => 'panel_pane',
      'title' => t('Argument input'),
      'value' => t('Edit'),
    );

    $allow = $this->get_option('allow');
    $filtered_allow = array_filter($allow);

    $options['allow'] = array(
      'category' => 'panel_pane',
      'title' => t('Allow settings'),
      'value' => empty($filtered_allow) ? t('None') : ($allow === $filtered_allow ? t('All') : t('Some')),
    );
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_form($form, $form_state);

    switch ($form_state['section']) {
      case 'allow':
        $form['#title'] .= t('Allow settings');
        $form['description'] = array(
          '#value' => '<div class="form-item description">' . t('Checked settings will be available in the panel pane config dialog for modification by the panels user. Unchecked settings will not be available and will only use the settings in this display.') . '</div>',
        );

        $options = array(
          'use_pager' => t('Use pager'),
          'items_per_page' => t('Items per page'),
          'offset' => t('Pager offset'),
          'link_to_view' => t('Link to view'),
          'more_link' => t('More link'),
          'path_override' => t('Path override'),
          'title_override' => t('Title override'),
          'exposed_form' => t('Use exposed widgets form as pane configuration'),
          'fields_override' => t('Fields override'),
        );

        $allow = array_filter($this->get_option('allow'));
        $form['allow'] = array(
          '#type' => 'checkboxes',
          '#default_value' => $allow,
          '#options' => $options,
        );
        break;
      case 'pane_title':
        $form['#title'] .= t('Administrative title');

        $form['pane_title'] = array(
          '#type' => 'textfield',
          '#default_value' => $this->get_option('pane_title'),
          '#description' => t('This is the title that will appear for this view pane in the add content dialog. If left blank, the view name will be used.'),
        );
        break;

      case 'pane_description':
        $form['#title'] .= t('Administrative description');

        $form['pane_description'] = array(
          '#type' => 'textfield',
          '#default_value' => $this->get_option('pane_description'),
          '#description' => t('This is text that will be displayed when the user mouses over the pane in the add content dialog. If blank the view description will be used.'),
        );
        break;

      case 'pane_category':
        $form['#title'] .= t('Administrative description');

        $cat = $this->get_option('pane_category');
        $form['pane_category']['#tree'] = TRUE;
        $form['pane_category']['name'] = array(
          '#type' => 'textfield',
          '#default_value' => $cat['name'],
          '#description' => t('This is category the pane will appear in on the add content dialog.'),
        );
        $form['pane_category']['weight'] = array(
          '#title' => t('Weight'),
          '#type' => 'textfield',
          '#default_value' => $cat['weight'],
          '#description' => t('This is the default weight of the category. Note that if the weight of a category is defined in multiple places, only the first one Panels sees will get that definition, so if the weight does not appear to be working, check other places that the weight might be set.'),
        );
        break;

      case 'link_to_view':
        $form['#title'] .= t('Link pane title to view');

        $form['link_to_view'] = array(
          '#type' => 'select',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->get_option('link_to_view'),
        );
        break;

      case 'inherit_panels_path':
        $form['#title'] .= t('Inherit path from panel display');

        $form['inherit_panels_path'] = array(
          '#type' => 'select',
          '#options' => array(1 => t('Yes'), 0 => t('No')),
          '#default_value' => $this->get_option('inherit_panels_path'),
          '#description' => t('If yes, all links generated by Views, such as more links, summary links, and exposed input links will go to the panels display path, not the view, if the display has a path.'),
        );
        break;

      case 'argument_input':
        $form['#title'] .= t('Choose the data source for view arguments');
        $argument_input = $this->get_argument_input();
        ctools_include('context');
        ctools_include('dependent');
        $form['argument_input']['#tree'] = TRUE;

        foreach ($argument_input as $id => $argument) {
          $form['argument_input'][$id] = array(
            '#tree' => TRUE,
          );

          $safe = str_replace(array('][', '_', ' '), '-', $id);
          $type_id = 'edit-argument-input-' . $safe;

          $form['argument_input'][$id]['type'] = array(
            '#type' => 'select',
            '#options' => array(
              'none' => t('No argument'),
              'wildcard' => t('Argument wildcard'),
              'context' => t('From context'),
              'panel' => t('From panel argument'),
              'fixed' => t('Fixed'),
              'user' => t('Input on pane config'),
            ),
            '#id' => $type_id,
            '#title' => t('@arg source', array('@arg' => $argument['name'])),
            '#default_value' => $argument['type'],
          );

          $form['argument_input'][$id]['context'] = array(
            '#type' => 'select',
            '#title' => t('Required context'),
            '#description' => t('If "From context" is selected, which type of context to use.'),
            '#default_value' => $argument['context'],
            '#options' => ctools_context_get_all_converters(),
            '#process' => array('form_process_select', 'ctools_dependent_process'),
            '#dependency' => array($type_id => array('context')),
          );

          $form['argument_input'][$id]['context_optional'] = array(
            '#type' => 'checkbox',
            '#title' => t('Context is optional'),
            '#description' => t('This context need not be present for the pane to function. If you plan to use this, ensure that the argument handler can handle empty values gracefully.'),
            '#default_value' => $argument['context_optional'],
            '#process' => array('form_process_checkbox', 'ctools_dependent_process'),
            '#dependency' => array($type_id => array('context')),
          );

          $form['argument_input'][$id]['panel'] = array(
            '#type' => 'select',
            '#title' => t('Panel argument'),
            '#description' => t('If "From panel argument" is selected, which panel argument to use.'),
            '#default_value' => $argument['panel'],
            '#options' => array(0 => t('First'), 1 => t('Second'), 2 => t('Third'), 3 => t('Fourth'), 4 => t('Fifth'), 5 => t('Sixth')),
            '#process' => array('form_process_select', 'ctools_dependent_process'),
            '#dependency' => array($type_id => array('panel')),
          );

          $form['argument_input'][$id]['fixed'] = array(
            '#type' => 'textfield',
            '#title' => t('Fixed argument'),
            '#description' => t('If "Fixed" is selected, what to use as an argument.'),
            '#default_value' => $argument['fixed'],
            '#process' => array('ctools_dependent_process'),
            '#dependency' => array($type_id => array('fixed')),
          );

          $form['argument_input'][$id]['label'] = array(
            '#type' => 'textfield',
            '#title' => t('Label'),
            '#description' => t('If this argument is presented to the panels user, what label to apply to it.'),
            '#default_value' => empty($argument['label']) ? $argument['name'] : $argument['label'],
            '#process' => array('ctools_dependent_process'),
            '#dependency' => array($type_id => array('user')),
          );
        }
        break;
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  function options_submit(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::options_submit($form, $form_state);
    switch ($form_state['section']) {
      case 'allow':
      case 'argument_input':
      case 'link_to_view':
      case 'inherit_panels_path':
      case 'pane_title':
      case 'pane_description':
      case 'pane_category':
        $this->set_option($form_state['section'], $form_state['values'][$form_state['section']]);
        break;
    }
  }

  /**
   * Adjust the array of argument input to match the current list of
   * arguments available for this display. This ensures that changing
   * the arguments doesn't cause the argument input field to just
   * break.
   */
  function get_argument_input() {
    $arguments = $this->get_option('argument_input');
    $handlers = $this->get_handlers('argument');

    // We use a separate output so as to seamlessly discard info for
    // arguments that no longer exist.
    $output = array();

    foreach ($handlers as $id => $handler) {
      if (empty($arguments[$id])) {
        $output[$id] = array(
          'type' => 'none',
          'context' => 'any',
          'context_optional' => FALSE,
          'panel' => 0,
          'fixed' => '',
          'name' => $handler->ui_name(),
        );
      }
      else {
        $output[$id] = $arguments[$id];
        $output[$id]['name'] = $handler->ui_name();
      }
    }

    return $output;
  }

  function use_more() {
    $allow = $this->get_option('allow');
    if (!$allow['more_link'] || !$this->has_pane_conf()) {
      return parent::use_more();
    }
    $conf = $this->get_option('pane_conf');
    return (bool) $conf['more_link'];
  }

  function get_path() {
    if (empty($this->view->override_path)) {
      return parent::get_path();
    }
    return $this->view->override_path;
  }

  /**
   * Determine if this display should display the exposed
   * filters widgets, so the view will know whether or not
   * to render them.
   *
   * Regardless of what this function
   * returns, exposed filters will not be used nor
   * displayed unless uses_exposed() returns TRUE.
   */
  function displays_exposed() {
    $conf = $this->get_option('allow');
    // If this is set, the exposed form is part of pane configuration, not
    // rendered normally.
    return empty($conf['exposed_form']);
  }

}

