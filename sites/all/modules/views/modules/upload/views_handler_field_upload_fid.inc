<?php
// $Id: views_handler_field_upload_fid.inc,v 1.10.4.5 2010/12/03 20:46:32 dereine Exp $
/**
 * Field handler to provide a list of roles.
 */
class views_handler_field_upload_fid extends views_handler_field_prerender_list {
  function construct() {
    parent::construct();
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_file'] = array('default' => FALSE);
    $options['only_listed'] = array('default' => FALSE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_file'] = array(
      '#title' => t('Link this field to download the file'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_file']),
    );

    $form['only_listed'] = array(
      '#title' => t('Only show "listed" file attachments'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['only_listed']),
    );
  }

  function pre_render(&$values) {
    $vids = array();
    $this->items = array();

    foreach ($values as $result) {
      $vids[] = $result->{$this->field_alias};
    }

    if ($vids) {
      // Support "only listed files" option.
      $where = '';
      if (!empty($this->options['only_listed'])) {
        $where = " AND u.list <> 0";
      }
      $result = db_query("SELECT u.vid, u.fid, f.filename, f.uri, f.filesize, f.filemime, u.description FROM {upload} u LEFT JOIN {file_managed} f ON f.fid = u.fid WHERE u.vid IN (" . implode(', ', $vids) . ")$where ORDER BY u.weight")->fetchAssoc();
      foreach ($result as $file) {
        $file['filename'] = check_plain($file['filename']);
        $file['filemime'] = check_plain($file['filemime']);
        $file['description'] = check_plain($file['description']);
        $file['filesize'] = format_size($file['filesize']);
        $file['uri'] = file_create_url($file['uri']);
        if (!empty($this->options['link_to_file']) ) {
          $file['make_link'] = TRUE;
          $file['path'] = $file['uri'];
        }
        $this->items[$file['vid']][$file['fid']] = $file;
      }
    }
  }

  function render_item($count, $item) {
    return $item['description'];
  }

  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-fid' . ']'] = t('The file ID for the file.');
    $tokens['[' . $this->options['id'] . '-name' . ']'] = t('The name of the attached file.');
    $tokens['[' . $this->options['id'] . '-type' . ']'] = t('The MIME type of the attached file.');
    $tokens['[' . $this->options['id'] . '-description' . ']'] = t('The name of the attached file.');
    $tokens['[' . $this->options['id'] . '-path' . ']'] = t('The path of the attached file.');
    $tokens['[' . $this->options['id'] . '-url' . ']'] = t('The url of the attached file.');
    $tokens['[' . $this->options['id'] . '-uri' . ']'] = t('The path of the attached file.');
    $tokens['[' . $this->options['id'] . '-size' . ']'] = t('The size of the attached file.');
  }

  function add_self_tokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-fid' . ']'] = $item['fid'];
    $tokens['[' . $this->options['id'] . '-name' . ']'] = $item['filename'];
    $tokens['[' . $this->options['id'] . '-type' . ']'] = $item['filemime'];
    $tokens['[' . $this->options['id'] . '-description' . ']'] = $item['description'];
    $tokens['[' . $this->options['id'] . '-path' . ']'] = $item['uri'];
    $tokens['[' . $this->options['id'] . '-url' . ']'] = url($item['uri']);
    $tokens['[' . $this->options['id'] . '-uri' . ']'] = $item['uri'];
    $tokens['[' . $this->options['id'] . '-size' . ']'] = $item['filesize'];
  }
}
