<?php

/**
 * @file
 * Definition of Views\translation\Plugin\views\field\NodeLinkTranslate.
 */

namespace Views\translation\Plugin\views\field;

use Views\node\Plugin\views\field\Link;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link node translate.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_link_translate",
 *   module = "translation"
 * )
 */
class NodeLinkTranslate extends Link {

  function render_link($data, $values) {
    // ensure user has access to edit this node.
    $node = $this->get_value($values);
    $node->status = 1; // unpublished nodes ignore access control
    if (empty($node->langcode) || !translation_supported_type($node->type) || !node_access('view', $node) || !user_access('translate content')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/$node->nid/translate";
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('translate');
    return $text;
  }

}
