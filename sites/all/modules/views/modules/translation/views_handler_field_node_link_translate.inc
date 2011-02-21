<?php
// $Id: views_handler_field_node_link_translate.inc,v 1.1.6.2 2010/05/11 21:41:18 dereine Exp $
/**
 * Field handler to present a link node translate.
 */
class views_handler_field_node_link_translate extends views_handler_field_node_link {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
    $this->additional_fields['type'] = 'type';
    $this->additional_fields['language'] = 'language';
    $this->additional_fields['format'] = array('table' => 'node_revisions', 'field' => 'format');
  }

  function render($values) {
    // ensure user has access to edit this node.
    $node = new stdClass();
    $node->nid = $values->{$this->aliases['nid']};
    $node->uid = $values->{$this->aliases['uid']};
    $node->type = $values->{$this->aliases['type']};
    $node->format = $values->{$this->aliases['format']};
    $node->language = $values->{$this->aliases['language']};
    $node->status = 1; // unpublished nodes ignore access control
    if (empty($node->language) || !translation_supported_type($node->type) || !node_access('view', $node) || !user_access('translate content')) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('translate');
    return l($text, "node/$node->nid/translate", array('query' => drupal_get_destination()));
  }
}

