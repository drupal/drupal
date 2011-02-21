<?php
// $Id: views_handler_field_locale_link_edit.inc,v 1.1.6.2 2010/11/30 20:36:01 merlinofchaos Exp $

/**
 * Field handler to present a link to edit a translation.
 */
class views_handler_field_locale_link_edit extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['lid'] = 'lid';
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

  function access() {
    // Ensure user has access to edit translations.
    return user_access('translate interface');
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->aliases['lid']}), $values);
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'admin/build/translate/edit/' . $values->{$this->aliases['lid']};
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }
}
