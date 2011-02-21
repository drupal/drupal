<?php
// $Id: views_handler_field_node_translation_link.inc,v 1.1.6.3 2010/12/09 21:45:49 dereine Exp $
/**
 * Field handler to present a link to the node.
 */
class views_handler_field_node_translation_link extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['nid'] = 'nid';
    $this->additional_fields['tnid'] = 'tnid';
    $this->additional_fields['title'] = 'title';
    $this->additional_fields['language'] = 'language';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->aliases['tnid']}), $values);
  }

  function render_link($data, $values) {
    global $language;
    $tnid = $values->{$this->aliases['tnid']};
    // Only load translations if the node isn't in the current language.
    if ($values->{$this->aliases['language']} != $language->language) {
      $translations = translation_node_get_translations($tnid);
      if (isset($translations[$language->language])) {
        $values->{$this->aliases['nid']} = $translations[$language->language]->nid;
        $values->{$this->aliases['title']} = $translations[$language->language]->title;
      }
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/" . $values->{$this->aliases['nid']};
    return $values->{$this->aliases['title']};
  }
}
