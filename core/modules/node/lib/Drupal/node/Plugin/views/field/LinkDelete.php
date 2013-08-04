<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\LinkDelete.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\node\Plugin\views\field\Link;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete a node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_link_delete")
 */
class LinkDelete extends Link {

  /**
   * Renders the link.
   */
  protected function renderLink($node, ResultRow $values) {
    // Ensure user has access to delete this node.
    if (!node_access('delete', $node)) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'node/' . $node->id() . '/delete';
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');
    return $text;
  }

}
