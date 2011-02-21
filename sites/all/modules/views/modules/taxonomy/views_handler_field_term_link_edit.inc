<?php
// $Id: views_handler_field_term_link_edit.inc,v 1.1.4.3 2010/12/19 10:33:13 dereine Exp $

/**
 * Field handler to present a term edit link .
 */
class views_handler_field_term_link_edit extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['tid'] = 'tid';
    $this->additional_fields['vid'] = 'vid';
    $this->additional_fields['vocabulary_machine_name'] = array(
      'table' => 'taxonomy_vocabulary',
      'field' => 'machine_name',
    );
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    // Mock a term object for taxonomy_term_edit_access(). Use machine name and
    // vid to ensure compatibility with vid based and machine name based
    // access checks. See http://drupal.org/node/995156
    $term = new stdClass();
    $term->vid = $values->{$this->aliases['vid']};
    $term->vocabulary_machine_name = $values->{$this->aliases['vocabulary_machine_name']};
    if (taxonomy_term_edit_access($term)) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');
      return l($text, 'taxonomy/term/'. $values->{$this->aliases['tid']} .'/edit', array('query' => drupal_get_destination()));
    }
  }
}

