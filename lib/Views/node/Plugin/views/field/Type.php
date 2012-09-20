<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\field\Type.
 */

namespace Views\node\Plugin\views\field;

use Views\node\Plugin\views\field\Node;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_type",
 *   module = "node"
 * )
 */
class Type extends Node {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['machine_name'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide machine_name option for to node type display.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

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
      return t($this->sanitizeValue(node_type_get_label($data)));
    }
    return $this->sanitizeValue($data);
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->render_name($value, $values), $values);
  }

}
