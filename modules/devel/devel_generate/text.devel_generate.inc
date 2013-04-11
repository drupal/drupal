<?php

function text_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_text_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _text_devel_generate($object, $field, $instance, $bundle);
  }
}

function _text_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();
  if (!empty($instance['settings']['text_processing'])) {
    $formats = filter_formats();
    $format = array_rand($formats);
  }
  else {
    $format = filter_fallback_format();
  }

  if (empty($field['settings']['max_length'])) {
    // Textarea handling
    $object_field['value'] = devel_create_content($format);
    if ($instance['widget']['type'] == 'text_textarea_with_summary' && !empty($instance['display_summary'])) {
      $object_field['summary'] = devel_create_content($format);
    }
  }
  else {
    // Textfield handling.
    $object_field['value'] = substr(devel_create_greeking(mt_rand(1, $field['settings']['max_length'] / 6), FALSE), 0, $field['settings']['max_length']);
  }
  $object_field['format'] = $format;
  return $object_field;
}
