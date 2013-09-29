<?php
// Id$

function number_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_number_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _number_devel_generate($object, $field, $instance, $bundle);
  }
}

function _number_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();
  // Make sure the instance settings are all set.
  foreach (array('min', 'max', 'precision', 'scale') as $key) {
    if (empty($instance['settings'][$key])) {
      $instance['settings'][$key] = NULL;
    }
  }
  $min = is_numeric($instance['settings']['min']) ? $instance['settings']['min'] : 0;
  switch ($field['type']) {
    case 'number_integer':
      $max = is_numeric($instance['settings']['max']) ? $instance['settings']['max'] : 10000;
      $decimal = 0;
      $scale = 0;
      break;

    case 'number_decimal':
      $precision = is_numeric($instance['settings']['precision']) ? $instance['settings']['precision'] : 10;
      $scale = is_numeric($instance['settings']['scale']) ? $instance['settings']['scale'] : 2;
      $max = is_numeric($instance['settings']['max']) ? $instance['settings']['max'] : pow(10, ($precision - $scale));
      $decimal = rand(0, (10 * $scale)) / 100;
      break;

    case 'number_float':
      $precision = rand(10, 32);
      $scale = rand(0, 2);
      $decimal = rand(0, (10 * $scale)) / 100;
      $max = is_numeric($instance['settings']['max']) ? $instance['settings']['max'] : pow(10, ($precision - $scale));
      break;
  }
  $object_field['value'] = round((rand($min, $max) + $decimal), $scale);
  return $object_field;
}
