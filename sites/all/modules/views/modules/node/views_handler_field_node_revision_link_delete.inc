<?php
// $Id: views_handler_field_node_revision_link_delete.inc,v 1.1.6.2 2010/11/18 07:29:03 dereine Exp $
/**
 * Field handler to present delete a node revision.
 */
class views_handler_field_node_revision_link_delete extends views_handler_field_node_link {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'node', 'field' => 'uid');
    $this->additional_fields['node_vid'] = array('table' => 'node', 'field' => 'vid');
    $this->additional_fields['vid'] = 'vid';
  }

  function access() {
    return user_access('delete revisions') || user_access('administer nodes');
  }

  function render_link($data, $values) {
    // ensure user has access to delete this node.
    $node = new stdClass();
    $node->nid = $values->{$this->aliases['nid']};
    $node->vid = $values->{$this->aliases['vid']};
    $node->uid = $values->{$this->aliases['uid']};
    $node->status = 1; // unpublished nodes ignore access control
    if (!node_access('delete', $node)) {
      return;
    }

    // Current revision cannot be deleted.
    if ($node->vid == $values->{$this->aliases['node_vid']}) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/$node->nid/revisions/$node->vid/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }
}
