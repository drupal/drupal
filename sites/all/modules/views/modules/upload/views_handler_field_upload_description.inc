<?php
// $Id: views_handler_field_upload_description.inc,v 1.5.4.3 2010/12/03 20:46:32 dereine Exp $

/**
 * Field handler to provide a list of roles.
 */
class views_handler_field_upload_description extends views_handler_field {
  function init(&$view, &$options) {
    parent::init($view, $options);
    if (!empty($options['link_to_file'])) {
      $this->additional_fields['fid'] = 'fid';
    }
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_file'] = array('default' => FALSE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_file'] = array(
      '#title' => t('Link this field to download the file'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_file']),
    );
  }

  function pre_render(&$values) {
    if (empty($this->options['link_to_file'])) {
      return;
    }

    $fids = array();
    $this->items = array();

    $data = array();
    foreach ($values as $result) {
      if ($result->{$this->aliases['fid']}) {
        $fids[] = $result->{$this->aliases['fid']};
      }
    }

    if ($fids) {
      $result = db_query("SELECT f.fid, f.uri FROM {files} f WHERE f.fid IN (:fids)", array(':fids' => $fids));
      foreach ($result as $file) {
        $this->items[$file->fid] = $file;
      }
    }
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }

  /**
   * Render whatever the data is as a link to the file.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $value) {
    if (!empty($this->options['link_to_file']) && $value->{$this->aliases['fid']} && $data !== NULL && $data !== '') {
      $values = $this->items[$value->{$this->aliases['fid']}];
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = file_create_url($values->uri);
    }
    else {
      $this->options['alter']['make_link'] = FALSE;
    }
    return $data;
  }
}
