<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\field\Revision.
 */

namespace Views\node\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Views\node\Plugin\views\field\Node;
use Drupal\Core\Annotation\Plugin;

/**
 * A basic node_revision handler.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_revision",
 *   module = "node"
 * )
 */
class Revision extends Node {

  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['link_to_node_revision'])) {
      $this->additional_fields['vid'] = 'vid';
      $this->additional_fields['nid'] = 'nid';
      if (module_exists('translation')) {
        $this->additional_fields['langcode'] = array('table' => 'node', 'field' => 'langcode');
      }
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
   * Render whatever the data is as a link to the node.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    if (!empty($this->options['link_to_node_revision']) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $nid = $this->get_value($values, 'nid');
      $vid = $this->get_value($values, 'vid');
      $this->options['alter']['path'] = "node/" . $nid . '/revisions/' . $vid . '/view';
      if (module_exists('translation')) {
        $langcode = $this->get_value($values, 'langcode');
        $languages = language_list();
        if (isset($languages[$langcode])) {
          $this->options['alter']['langcode'] = $languages[$langcode];
        }
      }
    }
    else {
      return parent::render_link($data, $values);
    }
    return $data;
  }

}
