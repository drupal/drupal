<?php

/**
 * @file
 * Definition of views_handler_field_node_revision_link_revert.
 */

namespace Views\node\Plugin\views\field;

use Views\node\Plugin\views\field\RevisionLink;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to revert a node to a revision.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   id = "node_revision_link_revert"
 * )
 */
class RevisionLinkRevert extends RevisionLink {

  function access() {
    return user_access('revert revisions') || user_access('administer nodes');
  }

  function render_link($data, $values) {
    list($node, $vid) = $this->get_revision_entity($values, 'update');
    if (!isset($vid)) {
      return;
    }

    // Current revision cannot be reverted.
    if ($node->vid == $vid) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'node/' . $node->nid . "/revisions/$vid/revert";
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('revert');
  }
}
