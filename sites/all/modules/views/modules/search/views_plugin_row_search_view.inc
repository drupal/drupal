<?php
// $Id: views_plugin_row_search_view.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $
/**
 * @file
 * Contains the search row style plugin.
 */

/**
 * Plugin which performs a node_view on the resulting object.
 */
class views_plugin_row_search_view extends views_plugin_row {
  function option_definition() {
    $options = parent::option_definition();

    $options['score'] = array('default' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['score'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display score'),
      '#default_value' => $this->options['score'],
    );
  }

  /**
   * Override the behavior of the render() function.
   */
  function render($row) {
    return theme($this->theme_functions(),
      array(
        'view' => $this->view,
        'options' => $this->options,
        'row' => $row
      ));
  }
}
