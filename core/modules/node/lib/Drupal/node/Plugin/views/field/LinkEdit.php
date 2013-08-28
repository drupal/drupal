<?php

/**
 * @file
 * Definition of views_handler_field_node_link_edit.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\node\Plugin\views\field\Link;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link node edit.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_link_edit")
 */
class LinkEdit extends Link {

  /**
   * Renders the link.
   */
  protected function renderLink($node, ResultRow $values) {
    // Ensure user has access to edit this node.
    if (!node_access('update', $node)) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/" . $node->id() . "/edit";
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
    return $text;
  }

}
