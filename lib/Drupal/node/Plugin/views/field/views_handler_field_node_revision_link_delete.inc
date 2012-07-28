<?php

/**
 * @file
 * Definition of views_handler_field_node_revision_link_delete.
 */

/**
 * Field handler to present link to delete a node revision.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_node_revision_link_delete extends views_handler_field_node_revision_link {

  function access() {
    return user_access('delete revisions') || user_access('administer nodes');
  }

  function render_link($data, $values) {
    list($node, $vid) = $this->get_revision_entity($values, 'delete');
    if (!isset($vid)) {
      return;
    }

    // Current revision cannot be deleted.
    if ($node->vid == $vid) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'node/' . $node->nid . "/revisions/$vid/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('delete');
  }
}
