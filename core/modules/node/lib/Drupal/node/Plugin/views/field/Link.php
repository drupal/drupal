<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Link.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_link")
 */
class Link extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);

    // The path is set by render_link function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->add_additional_fields();
  }

  function render($values) {
    if ($entity = $this->get_entity($values)) {
      return $this->render_link($entity, $values);
    }
  }

  function render_link($node, $values) {
    if (node_access('view', $node)) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = "node/$node->nid";
      $text = !empty($this->options['text']) ? $this->options['text'] : t('view');
      return $text;
    }
  }

}
