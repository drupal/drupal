<?php
// $Id: views_handler_field_file_filemime.inc,v 1.1.2.1 2010/10/06 17:47:37 dereine Exp $

/**
 * Field handler to add rendering MIME type images as an option on the filemime field.
 */
class views_handler_field_file_filemime extends views_handler_field_file {
  function option_definition() {
    $options = parent::option_definition();
    $options['filemime_image'] = array('default' => FALSE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['filemime_image'] = array(
      '#title' => t('Display an image representing the MIME type instead of the MIME text.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['filemime_image']),
    );
  }

  function render($values) {
    $data = $values->{$this->field_alias};
    if (!empty($this->options['filemime_image']) && $data !== NULL && $data !== '') {
      $fake_file = (object) array('filemime' => $data);
      $data = theme('file_icon', array('file' => $fake_file));
    }

    return $this->render_link($data, $values);
  }
}