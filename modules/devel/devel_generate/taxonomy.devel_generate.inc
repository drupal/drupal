<?php

function taxonomy_devel_generate($object, $field, $instance, $bundle) {
  if (field_behaviors_widget('multiple values', $instance) == FIELD_BEHAVIOR_CUSTOM) {
    return devel_generate_multiple('_taxonomy_devel_generate', $object, $field, $instance, $bundle);
  }
  else {
    return _taxonomy_devel_generate($object, $field, $instance, $bundle);
  }
}

function _taxonomy_devel_generate($object, $field, $instance, $bundle) {
  $object_field = array();
  // TODO: For free tagging vocabularies that do not already have terms, this
  // will not result in any tags being added.
  $machine_name = $field['settings']['allowed_values'][0]['vocabulary'];
  $vocabulary = entity_load('taxonomy_vocabulary', $machine_name);
  if ($max = db_query('SELECT MAX(tid) FROM {taxonomy_term_data} WHERE vid = :vid', array(':vid' => $vocabulary->vid))->fetchField()) {
    $candidate = mt_rand(1, $max);
    $query = db_select('taxonomy_term_data', 't');
    $tid = $query
              ->fields('t', array('tid'))
              ->condition('t.vid', $vocabulary->vid, '=')
              ->condition('t.tid', $candidate, '>=')
              ->range(0,1)
              ->execute()
              ->fetchField();
    // If there are no terms for the taxonomy, the query will fail, in which
    // case we return NULL.
    if ($tid === FALSE) {
      return NULL;
    }
    $object_field['tid'] = (int) $tid;
    return $object_field;
  }
}
