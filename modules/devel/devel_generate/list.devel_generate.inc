<?php
// Id$

function list_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_list_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _list_devel_generate($object, $field, $instance, $bundle);
  }
}

function _list_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();
  if ($allowed_values = list_allowed_values($field)) {
    $keys = array_keys($allowed_values);
    $object_field['value'] = $keys[mt_rand(0, count($allowed_values) - 1)];
  }
  return $object_field;
}
