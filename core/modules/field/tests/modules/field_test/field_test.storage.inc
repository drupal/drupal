<?php

/**
 * @file
 * Defines a field storage backend.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_field_storage_info().
 */
function field_test_field_storage_info() {
  return array(
    'field_test_storage' => array(
      'label' => t('Test storage'),
      'description' => t('Dummy test storage backend. Stores field values in the variable table.'),
    ),
    'field_test_storage_failure' => array(
      'label' => t('Test storage failure'),
      'description' => t('Dummy test storage backend. Always fails to create fields.'),
    ),
  );
}

/**
 * Implements hook_field_storage_details().
 */
function field_test_field_storage_details($field) {
  $details = array();

  // Add field columns.
  $columns = array();
  foreach ((array) $field['columns'] as $column_name => $attributes) {
    $columns[$column_name] = $column_name;
  }
  return array(
    'drupal_variables' => array(
      'field_test_storage_data[FIELD_LOAD_CURRENT]' => $columns,
      'field_test_storage_data[FIELD_LOAD_REVISION]' => $columns,
    ),
  );
}

/**
 * Implements hook_field_storage_details_alter().
 *
 * @see FieldAttachStorageTestCase::testFieldStorageDetailsAlter()
 */
function field_test_field_storage_details_alter(&$details, $field) {

  // For testing, storage details are changed only because of the field name.
  if ($field['field_name'] == 'field_test_change_my_details') {
    $columns = array();
    foreach ((array) $field['columns'] as $column_name => $attributes) {
      $columns[$column_name] = $column_name;
    }
    $details['drupal_variables'] = array(
      FIELD_LOAD_CURRENT => array(
        'moon' => $columns,
      ),
      FIELD_LOAD_REVISION => array(
        'mars' => $columns,
      ),
    );
  }
}

/**
 * Helper function: stores or retrieves data from the 'storage backend'.
 */
function _field_test_storage_data($data = NULL) {
  if (!isset($data)) {
    return Drupal::state()->get('field_test.storage_data');
  }
  else {
    Drupal::state()->set('field_test.storage_data', $data);
  }
}

/**
 * Implements hook_field_storage_load().
 */
function field_test_field_storage_load($entity_type, $entities, $age, $fields, $options) {
  $data = _field_test_storage_data();

  $load_current = $age == FIELD_LOAD_CURRENT;

  foreach ($fields as $field_id => $ids) {
    $field = field_info_field_by_id($field_id);
    $field_name = $field['field_name'];
    $field_data = $data[$field['uuid']];
    $sub_table = $load_current ? 'current' : 'revisions';
    $delta_count = array();
    foreach ($field_data[$sub_table] as $row) {
      if ($row->type == $entity_type && (!$row->deleted || $options['deleted'])) {
        if (($load_current && in_array($row->entity_id, $ids)) || (!$load_current && in_array($row->revision_id, $ids))) {
          if (in_array($row->langcode, field_available_languages($entity_type, $field))) {
            if (!isset($delta_count[$row->entity_id][$row->langcode])) {
              $delta_count[$row->entity_id][$row->langcode] = 0;
            }
            if ($field['cardinality'] == FIELD_CARDINALITY_UNLIMITED || $delta_count[$row->entity_id][$row->langcode] < $field['cardinality']) {
              $item = array();
              foreach ($field['columns'] as $column => $attributes) {
                $item[$column] = $row->{$column};
              }
              $entities[$row->entity_id]->{$field_name}[$row->langcode][] = $item;
              $delta_count[$row->entity_id][$row->langcode]++;
            }
          }
        }
      }
    }
  }
}

/**
 * Implements hook_field_storage_write().
 */
function field_test_field_storage_write(EntityInterface $entity, $op, $fields) {
  $data = _field_test_storage_data();

  $id = $entity->id();
  $vid = $entity->getRevisionId();
  $bundle = $entity->bundle();

  foreach ($fields as $field_id) {
    $field = field_info_field_by_id($field_id);
    $field_name = $field['field_name'];
    $field_data = &$data[$field_id];

    $all_langcodes = field_available_languages($entity->entityType(), $field);
    $field_langcodes = array_intersect($all_langcodes, array_keys((array) $entity->$field_name));

    // Delete and insert, rather than update, in case a value was added.
    if ($op == FIELD_STORAGE_UPDATE) {
      // Delete languages present in the incoming $entity->$field_name.
      // Delete all languages if $entity->$field_name is empty.
      $langcodes = !empty($entity->$field_name) ? $field_langcodes : $all_langcodes;
      if ($langcodes) {
        foreach ($field_data['current'] as $key => $row) {
          if ($row->type == $entity->entityType() && $row->entity_id == $id && in_array($row->langcode, $langcodes)) {
            unset($field_data['current'][$key]);
          }
        }
        if (isset($vid)) {
          foreach ($field_data['revisions'] as $key => $row) {
            if ($row->type == $entity->entityType() && $row->revision_id == $vid) {
              unset($field_data['revisions'][$key]);
            }
          }
        }
      }
    }

    foreach ($field_langcodes as $langcode) {
      $items = (array) $entity->{$field_name}[$langcode];
      $delta_count = 0;
      foreach ($items as $delta => $item) {
        $row = (object) array(
          'field_id' => $field_id,
          'type' => $entity->entityType(),
          'entity_id' => $id,
          'revision_id' => $vid,
          'bundle' => $bundle,
          'delta' => $delta,
          'deleted' => FALSE,
          'langcode' => $langcode,
        );
        foreach ($field['columns'] as $column => $attributes) {
          $row->{$column} = isset($item[$column]) ? $item[$column] : NULL;
        }

        $field_data['current'][] = $row;
        if (isset($vid)) {
          $field_data['revisions'][] = $row;
        }

        if ($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED && ++$delta_count == $field['cardinality']) {
          break;
        }
      }
    }
  }

  _field_test_storage_data($data);
}

/**
 * Implements hook_field_storage_delete().
 */
function field_test_field_storage_delete(EntityInterface $entity, $fields) {
  // Note: reusing field_test_storage_purge(), like field_sql_storage.module
  // does, is highly inefficient in our case...
  foreach (field_info_instances($entity->entityType(), $entity->bundle()) as $instance) {
    if (isset($fields[$instance['field_id']])) {
      $field = field_info_field_by_id($instance['field_id']);
      field_test_field_storage_purge($entity, $field, $instance);
    }
  }
}

/**
 * Implements hook_field_storage_purge().
 */
function field_test_field_storage_purge(EntityInterface $entity, $field, $instance) {
  $data = _field_test_storage_data();

  $field_data = &$data[$field['uuid']];
  foreach (array('current', 'revisions') as $sub_table) {
    foreach ($field_data[$sub_table] as $key => $row) {
      if ($row->type == $entity->entityType() && $row->entity_id == $entity->id()) {
        unset($field_data[$sub_table][$key]);
      }
    }
  }

  _field_test_storage_data($data);
}

/**
 * Implements hook_field_storage_delete_revision().
 */
function field_test_field_storage_delete_revision(EntityInterface $entity, $fields) {
  $data = _field_test_storage_data();

  foreach ($fields as $field_id) {
    $field_data = &$data[$field_id];
    foreach (array('current', 'revisions') as $sub_table) {
      foreach ($field_data[$sub_table] as $key => $row) {
        if ($row->type == $entity->entityType() && $row->entity_id == $entity->id() && $row->revision_id == $entity->getRevisionId()) {
          unset($field_data[$sub_table][$key]);
        }
      }
    }
  }

  _field_test_storage_data($data);
}

/**
 * Implements hook_field_storage_query().
 */
function field_test_field_storage_query($field_id, $conditions, $count, &$cursor = NULL, $age) {
  $data = _field_test_storage_data();

  $load_current = $age == FIELD_LOAD_CURRENT;

  $field = field_info_field_by_id($field_id);
  $field_columns = array_keys($field['columns']);

  $field_data = $data[$field['uuid']];
  $sub_table = $load_current ? 'current' : 'revisions';
  // We need to sort records by entity type and entity id.
  usort($field_data[$sub_table], '_field_test_field_storage_query_sort_helper');

    // Initialize results array.
  $return = array();
  $entity_count = 0;
  $rows_count = 0;
  $rows_total = count($field_data[$sub_table]);
  $skip = $cursor;
  $skipped = 0;

  foreach ($field_data[$sub_table] as $row) {
    if ($count != FIELD_QUERY_NO_LIMIT && $entity_count >= $count) {
      break;
    }

    if ($row->field_id == $field['uuid']) {
      $match = TRUE;
      $condition_deleted = FALSE;
      // Add conditions.
      foreach ($conditions as $condition) {
        @list($column, $value, $operator) = $condition;
        if (empty($operator)) {
          $operator = is_array($value) ? 'IN' : '=';
        }
        switch ($operator) {
          case '=':
            $match = $match && $row->{$column} == $value;
            break;
          case '<>':
          case '<':
          case '<=':
          case '>':
          case '>=':
            eval('$match = $match && ' . $row->{$column} . ' ' . $operator . ' '. $value);
            break;
          case 'IN':
            $match = $match && in_array($row->{$column}, $value);
            break;
          case 'NOT IN':
            $match = $match && !in_array($row->{$column}, $value);
            break;
          case 'BETWEEN':
            $match = $match && $row->{$column} >= $value[0] && $row->{$column} <= $value[1];
            break;
          case 'STARTS_WITH':
          case 'ENDS_WITH':
          case 'CONTAINS':
            // Not supported.
            $match = FALSE;
            break;
        }
        // Track condition on 'deleted'.
        if ($column == 'deleted') {
          $condition_deleted = TRUE;
        }
      }

      // Exclude deleted data unless we have a condition on it.
      if (!$condition_deleted && $row->deleted) {
        $match = FALSE;
      }

      if ($match) {
        if (!isset($skip) || $skipped >= $skip) {
          $cursor++;
          // If querying all revisions and the entity type has revisions, we need
          // to key the results by revision_ids.
          $entity_type = entity_get_info($row->type);
          $id = ($load_current || empty($entity_type['entity_keys']['revision'])) ? $row->entity_id : $row->revision_id;

          if (!isset($return[$row->type][$id])) {
            $return[$row->type][$id] = (object) array('entity_id' => $row->entity_id, 'revision_id' => $row->revision_id, 'bundle' => $row->bundle);
            $entity_count++;
          }
        }
        else {
          $skipped++;
        }
      }
    }
    $rows_count++;

    // The query is complete if we walked the whole array.
    if ($count != FIELD_QUERY_NO_LIMIT && $rows_count >= $rows_total) {
      $cursor = FIELD_QUERY_COMPLETE;
    }
  }

  return $return;
}

/**
 * Sort helper for field_test_field_storage_query().
 *
 * Sorts by entity type and entity id.
 */
function _field_test_field_storage_query_sort_helper($a, $b) {
  if ($a->type == $b->type) {
    if ($a->entity_id == $b->entity_id) {
      return 0;
    }
    else {
      return $a->entity_id < $b->entity_id ? -1 : 1;
    }
  }
  else {
    return $a->type < $b->type ? -1 : 1;
  }
}

/**
 * Implements hook_field_storage_create_field().
 */
function field_test_field_storage_create_field($field) {
  if ($field['storage']['type'] == 'field_test_storage_failure') {
    throw new Exception('field_test_storage_failure engine always fails to create fields');
  }

  $data = _field_test_storage_data();

  $data[$field['uuid']] = array(
    'current' => array(),
    'revisions' => array(),
  );

  _field_test_storage_data($data);
}

/**
 * Implements hook_field_storage_delete_field().
 */
function field_test_field_storage_delete_field($field) {
  $data = _field_test_storage_data();

  $field_data = &$data[$field['uuid']];
  foreach (array('current', 'revisions') as $sub_table) {
    foreach ($field_data[$sub_table] as &$row) {
      $row->deleted = TRUE;
    }
  }

  _field_test_storage_data($data);
}

/**
 * Implements hook_field_storage_delete_instance().
 */
function field_test_field_storage_delete_instance($instance) {
  $data = _field_test_storage_data();

  $field = field_info_field($instance['field_name']);
  $field_data = &$data[$field['uuid']];
  foreach (array('current', 'revisions') as $sub_table) {
    foreach ($field_data[$sub_table] as &$row) {
      if ($row->bundle == $instance['bundle']) {
        $row->deleted = TRUE;
      }
    }
  }

  _field_test_storage_data($data);
}

/**
 * Implements hook_entity_bundle_rename().
 */
function field_test_entity_bundle_rename($entity_type, $bundle_old, $bundle_new) {
  $data = _field_test_storage_data();

  // We need to account for deleted or inactive fields and instances.
  $instances = field_read_instances(array('bundle' => $bundle_new), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
  foreach ($instances as $field_name => $instance) {
    $field = field_info_field_by_id($instance['field_id']);
    if ($field && $field['storage']['type'] == 'field_test_storage') {
      $field_data = &$data[$field['uuid']];
      foreach (array('current', 'revisions') as $sub_table) {
        foreach ($field_data[$sub_table] as &$row) {
          if ($row->bundle == $bundle_old) {
            $row->bundle = $bundle_new;
          }
        }
      }
    }
  }

  _field_test_storage_data($data);
}

/**
 * Implements hook_field_delete_instance().
 */
function field_test_field_delete_instance($instance) {
  $data = _field_test_storage_data();

  $field = field_info_field($instance['field_name']);
  if ($field['storage']['type'] == 'field_test_storage') {
    $field_data = &$data[$field['uuid']];
    foreach (array('current', 'revisions') as $sub_table) {
      foreach ($field_data[$sub_table] as &$row) {
        if ($row->bundle == $instance['bundle']) {
          $row->deleted = TRUE;
        }
      }
    }
  }

  _field_test_storage_data($data);
}
