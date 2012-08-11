<?php

/**
 * @file
 * Definition of views_handler_field_node_type.
 */

namespace Views\node\Plugin\views\field;

use Views\node\Plugin\views\field\Node;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   id = "node_type"
 * )
 */
class Type extends Node {
  function option_definition() {
    $options = parent::option_definition();
    $options['machine_name'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide machine_name option for to node type display.
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['machine_name'] = array(
      '#title' => t('Output machine name'),
      '#description' => t('Display field as the content type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
    * Render node type as human readable name, unless using machine_name option.
    */
  function render_name($data, $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      return t($this->sanitize_value(node_type_get_name($data)));
    }
    return $this->sanitize_value($data);
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->render_name($value, $values), $values);
  }
}
