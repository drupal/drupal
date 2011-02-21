<?php
// $Id: views_handler_area.inc,v 1.1.4.3 2010/12/17 21:42:19 merlinofchaos Exp $
/**
 * @file
 * Views area handlers.
 */

/**
 * @defgroup views_area_handlers Views' relationship handlers
 * @{
 * Handlers to tell Views what can display in header, footer
 * and empty text in a view.
 */

class views_handler_area extends views_handler {
  /**
   * Get this field's label.
   */
  function label() {
    if (!isset($this->options['label'])) {
      return $this->ui_name();
    }
    return $this->options['label'];
  }

  function option_definition() {
    $options = parent::option_definition();

    $this->definition['field'] = !empty($this->definition['field']) ? $this->definition['field'] : '';
    $label = !empty($this->definition['label']) ? $this->definition['label'] : $this->definition['field'];
    $options['label'] = array('default' => $label, 'translatable' => TRUE);
    $options['empty'] = array('default' => 0, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide extra data to the administration form
   */
  function admin_summary() {
    return $this->label();
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => isset($this->options['label']) ? $this->options['label'] : '',
      '#description' => t('The label for this area that will be displayed only administratively.'),
    );

    if ($form_state['type'] != 'empty') {
      $form['empty'] = array(
        '#type' => 'checkbox',
        '#title' => t('Display even if view has no result'),
        '#default_value' => isset($this->options['empty']) ? $this->options['empty'] : 0,
        '#description' => t('If checked this area will be rended, even if the views has no results.'),
      );
    }
  }

  /**
   * Don't run a query
   */
  function query() { }

  /**
   * Render the area
   */
  function render($empty = FALSE) {
    return '';
  }
}

/**
 * A special handler to take the place of missing or broken handlers.
 */
class views_handler_area_broken extends views_handler_area {
  function ui_name($short = FALSE) {
    return t('Broken/missing handler');
  }

  function ensure_my_table() { /* No table to ensure! */ }
  function query() { /* No query to run */ }
  function render($empty = FALSE) { return ''; }
  function options_form(&$form, &$form_state) {
    $form['markup'] = array(
      '#prefix' => '<div class="form-item description">',
      '#value' => t('The handler for this item is broken or missing and cannot be used. If a module provided the handler and was disabled, re-enabling the module may restore it. Otherwise, you should probably delete this item.'),
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
