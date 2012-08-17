<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\field\Link.
 */

namespace Views\node\Plugin\views\field;

use Drupal\views\Plugin\views\field\Entity;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_link",
 *   module = "node"
 * )
 */
class Link extends Entity {

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::options_form($form, $form_state);

    // The path is set by render_link function so don't allow to set it.
    $form['alter']['path'] = array('#access' => FALSE);
    $form['alter']['external'] = array('#access' => FALSE);
  }

  function render($values) {
    if ($entity = $this->get_value($values)) {
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
