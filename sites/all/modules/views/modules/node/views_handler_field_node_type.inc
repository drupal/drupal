<?php
// $Id: views_handler_field_node_type.inc,v 1.2.4.4 2010/04/29 18:33:29 merlinofchaos Exp $

/**
 * Field handler to translate a node type into its readable form.
 */
class views_handler_field_node_type extends views_handler_field_node {
  function option_definition() {
    $options = parent::option_definition();
    $options['machine_name'] = array('default' => FALSE);

    return $options;
  }

  /**
   * Provide machine_name option for to node type display.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['machine_name'] = array(
      '#title' => t('Output machine name'),
      '#description' => t('Display field as the node type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
    * Render node type as human readable name, unless using machine_name option.
    */
  function render_name($data, $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      return t(check_plain(node_type_get_name($data)));
    }
    return check_plain($data);
  }

  function render($values) {
    return $this->render_link($this->render_name($values->{$this->field_alias}, $values), $values);
  }
}

