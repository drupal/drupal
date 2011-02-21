<?php
// $Id: views_handler_filter_field_list.inc,v 1.1.2.2 2010/09/30 20:07:59 dereine Exp $

class views_handler_filter_field_list extends views_handler_filter_in_operator {
  function get_value_options() {
    $field = field_info_field($this->definition['field_name']);
    $this->value_options = list_allowed_values($field);
  }
}
