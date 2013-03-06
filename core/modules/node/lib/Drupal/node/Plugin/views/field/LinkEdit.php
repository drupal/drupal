<?php

/**
 * @file
 * Definition of views_handler_field_node_link_edit.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\node\Plugin\views\field\Link;
use Drupal\Component\Annotation\Plugin;

/**
 * Field handler to present a link node edit.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_link_edit",
 *   module = "node"
 * )
 */
class LinkEdit extends Link {

  /**
   * Renders the link.
   */
  function render_link($node, $values) {
    // Ensure user has access to edit this node.
    if (!node_access('update', $node)) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/$node->nid/edit";
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    return $text;
  }

}
