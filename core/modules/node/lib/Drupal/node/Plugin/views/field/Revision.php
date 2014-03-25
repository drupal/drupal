<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Revision.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\node\Plugin\views\field\Node;

/**
 * A basic node_revision handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revision")
 */
class Revision extends Node {

  /**
   * Overrides \Drupal\node\Plugin\views\field\Node::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['link_to_node_revision'])) {
      $this->additional_fields['vid'] = 'vid';
      $this->additional_fields['nid'] = 'nid';
    }
  }
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_node_revision'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to revision option.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_node_revision'] = array(
      '#title' => t('Link this field to its content revision'),
      '#description' => t('This will override any other link you have set.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_node_revision']),
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the node revision.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_node_revision']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $nid = $this->getValue($values, 'nid');
      $vid = $this->getValue($values, 'vid');
      $this->options['alter']['path'] = "node/" . $nid . '/revisions/' . $vid . '/view';
    }
    else {
      return parent::renderLink($data, $values);
    }
    return $data;
  }

}
