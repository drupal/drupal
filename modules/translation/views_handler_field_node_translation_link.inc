<?php

/**
 * @file
 * Definition of views_handler_field_node_translation_link.
 */

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
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
    $value = $this->get_value($values, 'tnid');
    return $this->render_link($this->sanitize_value($value), $values);
  }

  function render_link($data, $values) {
    $language_interface = language_manager(LANGUAGE_TYPE_INTERFACE);

    $tnid = $this->get_value($values, 'tnid');
    // Only load translations if the node isn't in the current language.
    if ($this->get_value($values, 'language') != $language_interface->langcode) {
      $translations = translation_node_get_translations($tnid);
      if (isset($translations[$language_interface->langcode])) {
        $values->{$this->aliases['nid']} = $translations[$language_interface->langcode]->nid;
        $values->{$this->aliases['title']} = $translations[$language_interface->langcode]->title;
      }
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/" . $this->get_value($values, 'nid');
    return $this->get_value($values, 'title');
  }
}
