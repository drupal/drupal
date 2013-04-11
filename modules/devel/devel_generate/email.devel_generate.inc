<?php
/**
 * @file
 * Support file for generating email field content.
 */

function email_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_email_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _email_devel_generate($object, $field, $instance, $bundle);
  }
}

function _email_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();

  // Set of possible top-level domains.
  $tlds = array('com', 'net', 'gov', 'org', 'edu', 'biz', 'info');

  // Set random lengths for the user and domain as the email field doesn't have
  // any setting for length.
  $user_length = mt_rand(5, 10);
  $domain_length = mt_rand(7, 15);

  $object_field['value'] = devel_generate_word($user_length) . '@' . devel_generate_word($domain_length) . '.' . $tlds[mt_rand(0, (sizeof($tlds)-1))];
  return $object_field;
}
