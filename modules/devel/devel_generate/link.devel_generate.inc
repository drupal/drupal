<?php
/**
 * @file
 * Support file for generating link field content.
 */

function link_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_link_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _link_devel_generate($object, $field, $instance, $bundle);
  }
}

function _link_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();

  // Set of possible top-level domains.
  $tlds = array('com', 'net', 'gov', 'org', 'edu', 'biz', 'info');

  // Set random length for the domain name.
  $domain_length = mt_rand(7, 15);

  // Get the title settings from the field instance.
  $allow_title = $instance['settings']['title'];
  switch ($allow_title) {
    case DRUPAL_DISABLED:
      $generate_title = FALSE;
      break;
    case DRUPAL_REQUIRED:
      $generate_title = TRUE;
      break;
    case DRUPAL_OPTIONAL:
      // In case of optional title, randomize its generation.
      $generate_title = mt_rand(0,1);
      break;
  }

  // Set the title value as the presave function is expecting it but only
  // input content if needed.
  $object_field['title'] = '';
  if ($generate_title == TRUE) {
    $object_field['title'] = devel_create_greeking(4);
  }

  $object_field['url'] = 'http://www.' . devel_generate_word($domain_length) . '.' . $tlds[mt_rand(0, (sizeof($tlds)-1))];
  return $object_field;
}
