<?php
// $Id: views_plugin_exposed_form.inc,v 1.2.2.18 2010/12/01 20:05:06 dereine Exp $

/**
 * The base plugin to handle exposed filter forms.
 */
class views_plugin_exposed_form extends views_plugin {

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
   * Return a string to display as the clickable title for the
   * control.
   */
  function summary_title() {
    return t('Unknown');
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['submit_button'] = array('default' => t('Apply'), 'translatable' => TRUE);
    $options['reset_button'] = array('default' => FALSE, 'bool' => TRUE);
    $options['reset_button_label'] = array('default' => 'Reset', 'translatable' => TRUE);
    $options['exposed_sorts_label'] = array('default' => 'Sort By', 'translatable' => TRUE);
    $options['sort_asc_label'] = array('default' => 'Asc', 'translatable' => TRUE);
    $options['sort_desc_label'] = array('default' => 'Desc', 'translatable' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['submit_button'] = array(
      '#type' => 'textfield',
      '#title' => t('Submit button text'),
      '#description' => t('Text to display in the submit button of the exposed form.'),
      '#default_value' => $this->options['submit_button'],
      '#required' => TRUE,
    );

    $form['reset_button'] = array (
      '#type' => 'checkbox',
      '#title' => t('Include reset button'),
      '#description' => t('If checked the exposed form will provide a button to reset all the applied exposed filters'),
      '#default_value' => $this->options['reset_button'],
    );

    $form['reset_button_label'] = array(
     '#type' => 'textfield',
      '#title' => t('Reset button label'),
      '#description' => t('Text to display in the reset button of the exposed form.'),
      '#default_value' => $this->options['reset_button_label'],
      '#required' => TRUE,
      '#dependency' => array(
        'edit-exposed-form-options-reset-button' => array(1)
      ),
      '#process' => array('ctools_dependent_process'),
    );

    $form['exposed_sorts_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Exposed sorts label'),
      '#description' => t('Text to display as the label of the exposed sort select box.'),
      '#default_value' => $this->options['exposed_sorts_label'],
      '#required' => TRUE,
    );

    $form['sort_asc_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Ascending'),
      '#description' => t('Text to use when exposed sort is ordered ascending.'),
      '#default_value' => $this->options['sort_asc_label'],
      '#required' => TRUE,
    );

    $form['sort_desc_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Descending'),
      '#description' => t('Text to use when exposed sort is ordered descending.'),
      '#default_value' => $this->options['sort_desc_label'],
      '#required' => TRUE,
    );
  }

  /**
   * Render the exposed filter form.
   *
   * This actually does more than that; because it's using FAPI, the form will
   * also assign data to the appropriate handlers for use in building the
   * query.
   */
  function render_exposed_form($block = FALSE) {
    // Deal with any exposed filters we may have, before building.
    $form_state = array(
      'view' => &$this->view,
      'display' => &$this->display,
      'method' => 'get',
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'always_process' => TRUE,
    );

    // Some types of displays (eg. attachments) may wish to use the exposed
    // filters of their parent displays instead of showing an additional
    // exposed filter form for the attachment as well as that for the parent.
    if (!$this->view->display_handler->displays_exposed() || (!$block && $this->view->display_handler->get_option('exposed_block'))) {
      unset($form_state['rerender']);
    }

    if (!empty($this->ajax)) {
      $form_state['ajax'] = TRUE;
    }

    $form_state['exposed_form_plugin'] = $this;
    $form = drupal_build_form('views_exposed_form', $form_state);
    $output = drupal_render($form);
    if (!empty($form_state['js settings'])) {
      $this->view->js_settings = $form_state['js settings'];
    }

    if (!$this->view->display_handler->displays_exposed() || (!$block && $this->view->display_handler->get_option('exposed_block'))) {
      return "";
    }
    else {
      return $output;
    }
  }

  function query() {
    $view = $this->view;
    $exposed_data = isset($view->exposed_data) ? $view->exposed_data : array();
    $sort_by = isset($exposed_data['sort_by']) ? $exposed_data['sort_by'] : NULL;
    if (!empty($sort_by)) {
      $handler = $view->sort[$sort_by];
      if (isset($handler)) {
        $view->query->orderby = array();
        if (isset($exposed_data['sort_order']) && in_array($exposed_data['sort_order'], array('ASC', 'DESC'))) {
          $handler->options['order'] = $exposed_data['sort_order'];
        }
        $handler->query();
        foreach ($view->sort as $sort) {
          if (!$sort->is_exposed()) {
            $sort->query();
          }
        }
      }
    }
  }

  function pre_render($values) { }

  function post_render(&$output) { }

  function pre_execute() { }

  function post_execute() { }

  function exposed_form_alter(&$form, &$form_state) {
    if (!empty($this->options['reset_button'])) {
      $form['reset'] = array(
        '#value' => $this->options['reset_button_label'],
        '#type' => 'submit',
      );
    }

    $form['submit']['#value'] = $this->options['submit_button'];
    // Check if there is exposed sorts for this view
    $exposed_sorts = array();
    foreach ($this->view->sort as $id => $handler) {
      if ($handler->can_expose() && $handler->is_exposed()) {
        $exposed_sorts[$id] = check_plain($handler->options['expose']['label']);
      }
    }

    if (count($exposed_sorts)) {
      $form['sort_by'] = array(
        '#type' => 'select',
        '#options' => $exposed_sorts,
        '#title' => t($this->options['exposed_sorts_label']),
      );
      $sort_order = array(
        'ASC' => $this->options['sort_asc_label'],
        'DESC' => $this->options['sort_desc_label'],
      );
      $first_sort = reset($this->view->sort);
      $form['sort_order'] = array(
        '#type' => 'select',
        '#options' => $sort_order,
        '#title' => t('Order'),
        '#default_value' => $first_sort->options['order'],
      );
      $form['submit']['#weight'] = 10;
      if (isset($form['reset'])) {
        $form['reset']['#weight'] = 10;
      }
    }

    $pager = $this->view->display_handler->get_plugin('pager');
    if ($pager) {
      $pager->exposed_form_alter($form, $form_state);
      $form_state['pager_plugin'] = $pager;
    }
  }

  function exposed_form_validate(&$form, &$form_state) {
    if (isset($form_state['pager_plugin'])) {
      $form_state['pager_plugin']->exposed_form_validate($form, $form_state);
    }
  }

  /**
  * This function is executed when exposed form is submited.
  *
  * @param $form
  *   Nested array of form elements that comprise the form.
  * @param $form_state
  *   A keyed array containing the current state of the form.
  * @param $exclude
  *   Nested array of keys to exclude of insert into
  *   $view->exposed_raw_input
  */
  function exposed_form_submit(&$form, &$form_state, &$exclude) {
    if (!empty($form_state['values']['op']) && $form_state['values']['op'] == $this->options['reset_button_label']) {
      $this->reset_form($form, $form_state);
    }
    if (isset($form_state['pager_plugin'])) {
      $form_state['pager_plugin']->exposed_form_submit($form, $form_state, $exclude);
      $exclude[] = 'pager_plugin';
    }
  }

  function reset_form(&$form, &$form_state) {
    unset($_SESSION['views'][$this->view->name][$this->view->current_display]);
    $form_state = array();
    $form_state['values'] = array();
  }
}
