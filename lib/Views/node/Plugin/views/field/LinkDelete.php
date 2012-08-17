<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\field\LinkDelete.
 */

namespace Views\node\Plugin\views\field;

use Views\node\Plugin\views\field\Link;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to delete a node.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_link_delete",
 *   module = "node"
 * )
 */
class LinkDelete extends Link {

  /**
   * Renders the link.
   */
  function render_link($node, $values) {
    // Ensure user has access to delete this node.
    if (!node_access('delete', $node)) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/$node->nid/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
    return $text;
  }

}
