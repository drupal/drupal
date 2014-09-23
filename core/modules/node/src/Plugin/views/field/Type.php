<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Type.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Plugin\views\field\Node;
use Drupal\views\ResultRow;

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_type")
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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['machine_name'] = array(
      '#title' => $this->t('Output machine name'),
      '#description' => $this->t('Display field as the content type machine name.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['machine_name']),
    );
  }

  /**
   * Render node type as human readable name, unless using machine_name option.
   */
  function render_name($data, $values) {
    if ($this->options['machine_name'] != 1 && $data !== NULL && $data !== '') {
      $type = entity_load('node_type', $data);
      return $type ? $this->t($this->sanitizeValue($type->label())) : '';
    }
    return $this->sanitizeValue($data);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->render_name($value, $values), $values);
  }

}
