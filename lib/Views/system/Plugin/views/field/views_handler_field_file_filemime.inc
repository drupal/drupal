<?php

/**
 * @file
 * Definition of views_handler_field_file_filemime.
 */

/**
 * Field handler to add rendering MIME type images as an option on the filemime field.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_file_filemime extends views_handler_field_file {
  function option_definition() {
    $options = parent::option_definition();
    $options['filemime_image'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['filemime_image'] = array(
      '#title' => t('Display an icon representing the file type, instead of the MIME text (such as "image/jpeg")'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['filemime_image']),
    );
    parent::options_form($form, $form_state);
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
