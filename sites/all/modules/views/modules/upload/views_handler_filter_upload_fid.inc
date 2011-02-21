<?php
// $Id: views_handler_filter_upload_fid.inc,v 1.1 2008/09/03 19:21:30 merlinofchaos Exp $

/**
 * Filter by whether or not a node has attached files from the upload module
 */
class views_handler_filter_upload_fid extends views_handler_filter_boolean_operator {
  function construct() {
    parent::construct();
    $this->value_value = t('Has attached files');
  }

  function query() {
    $this->ensure_my_table();
    $this->query->add_where($this->options['group'], "(SELECT COUNT(*) FROM {upload} u WHERE u.vid = $this->table_alias.$this->real_field) " . (empty($this->value) ? '=' : '<>') . " 0");
  }
}

