<?php

/**
 * @file
 * @todo.
 */

/**
 * @defgroup views_sort_handlers Views sort handlers
 * @{
 * Handlers to tell Views how to sort queries.
 */

/**
 * Base sort handler that has no options and performs a simple sort.
 *
 * @ingroup views_sort_handlers
 */
class views_handler_sort extends views_handler {

  /**
   * Determine if a sort can be exposed.
   */
  function can_expose() { return TRUE; }

  /**
   * Called to add the sort to a query.
   */
  function query() {
    $this->ensure_my_table();
    // Add the field.
    $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order']);
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['order'] = array('default' => 'ASC');
    $options['exposed'] = array('default' => FALSE, 'bool' => TRUE);
    $options['expose'] = array(
      'contains' => array(
        'label' => array('default' => '', 'translatable' => TRUE),
      ),
    );
    return $options;
  }

  /**
   * Display whether or not the sort order is ascending or descending
   */
  function admin_summary() {
    if (!empty($this->options['exposed'])) {
      return t('Exposed');
    }
    switch ($this->options['order']) {
      case 'ASC':
      case 'asc':
      default:
        return t('asc');
        break;
      case 'DESC';
      case 'desc';
        return t('desc');
        break;
    }
  }

  /**
   * Basic options for all sort criteria
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if ($this->can_expose()) {
      $this->show_expose_button($form, $form_state);
    }
    $form['op_val_start'] = array('#value' => '<div class="clearfix">');
    $this->show_sort_form($form, $form_state);
    $form['op_val_end'] = array('#value' => '</div>');
    if ($this->can_expose()) {
      $this->show_expose_form($form, $form_state);
    }
  }

  /**
   * Shortcut to display the expose/hide button.
   */
  function show_expose_button(&$form, &$form_state) {
    $form['expose_button'] = array(
      '#prefix' => '<div class="views-expose clearfix">',
      '#suffix' => '</div>',
      // Should always come first
      '#weight' => -1000,
    );

    // Add a checkbox for JS users, which will have behavior attached to it
    // so it can replace the button.
    $form['expose_button']['checkbox'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('js-only')),
    );
    $form['expose_button']['checkbox']['checkbox'] = array(
      '#title' => t('Expose this sort to visitors, to allow them to change it'),
      '#type' => 'checkbox',
    );

    // Then add the button itself.
    if (empty($this->options['exposed'])) {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description" style="float: left; margin-right:10px">' . t('This sort is not exposed. Expose it to allow the users to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => t('Expose sort'),
        '#submit' => array('views_ui_config_item_form_expose'),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 0;
    }
    else {
      $form['expose_button']['markup'] = array(
        '#markup' => '<div class="description exposed-description">' . t('This sort is exposed. If you hide it, users will not be able to change it.') . '</div>',
      );
      $form['expose_button']['button'] = array(
        '#limit_validation_errors' => array(),
        '#type' => 'submit',
        '#value' => t('Hide sort'),
        '#submit' => array('views_ui_config_item_form_expose'),
      );
      $form['expose_button']['checkbox']['checkbox']['#default_value'] = 1;
    }
  }

  /**
   * Simple validate handler
   */
  function options_validate(&$form, &$form_state) {
    $this->sort_validate($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->expose_validate($form, $form_state);
    }

  }

  /**
   * Simple submit handler
   */
  function options_submit(&$form, &$form_state) {
    unset($form_state['values']['expose_button']); // don't store this.
    $this->sort_submit($form, $form_state);
    if (!empty($this->options['exposed'])) {
      $this->expose_submit($form, $form_state);
    }
  }

  /**
   * Shortcut to display the value form.
   */
  function show_sort_form(&$form, &$form_state) {
    $options = $this->sort_options();
    if (!empty($options)) {
      $form['order'] = array(
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => $this->options['order'],
      );
    }
  }

  function sort_validate(&$form, &$form_state) { }

  function sort_submit(&$form, &$form_state) { }

  /**
   * Provide a list of options for the default sort form.
   * Should be overridden by classes that don't override sort_form
   */
  function sort_options() {
    return array(
      'ASC' => t('Sort ascending'),
      'DESC' => t('Sort descending'),
    );
  }

  function expose_form(&$form, &$form_state) {
    // #flatten will move everything from $form['expose'][$key] to $form[$key]
    // prior to rendering. That's why the pre_render for it needs to run first,
    // so that when the next pre_render (the one for fieldsets) runs, it gets
    // the flattened data.
    array_unshift($form['#pre_render'], 'views_ui_pre_render_flatten_data');
    $form['expose']['#flatten'] = TRUE;

    $form['expose']['label'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->options['expose']['label'],
      '#title' => t('Label'),
      '#required' => TRUE,
      '#size' => 40,
      '#weight' => -1,
   );
  }

  /**
   * Provide default options for exposed sorts.
   */
  function expose_options() {
    $this->options['expose'] = array(
      'order' => $this->options['order'],
      'label' => $this->definition['title'],
    );
  }
}

/**
 * A special handler to take the place of missing or broken handlers.
 *
 * @ingroup views_sort_handlers
 */
class views_handler_sort_broken extends views_handler_sort {
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
