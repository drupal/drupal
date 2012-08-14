<?php

/**
 * @file
 * Definition of views_handler_field_file_uri.
 */

namespace Views\file\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to add rendering file paths as file URLs instead of as internal file URIs.
 *
 * @Plugin(
 *   id = "file_uri",
 *   module = "file"
 * )
 */
class Uri extends File {
  function option_definition() {
    $options = parent::option_definition();
    $options['file_download_path'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['file_download_path'] = array(
      '#title' => t('Display download path instead of file storage URI'),
      '#description' => t('This will provide the full download URL rather than the internal filestream address.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['file_download_path']),
    );
    parent::options_form($form, $form_state);
  }

  function render($values) {
    $data = $values->{$this->field_alias};
    if (!empty($this->options['file_download_path']) && $data !== NULL && $data !== '') {
      $data = file_create_url($data);
    }
    return $this->render_link($data, $values);
  }
}
