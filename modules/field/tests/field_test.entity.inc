<?php
// $Id: field_test.entity.inc,v 1.13 2010/09/11 06:03:11 webchick Exp $

/**
 * @file
 * Defines an entity type.
 */

/**
 * Implements hook_entity_info().
 */
function field_test_entity_info() {
  $bundles = variable_get('field_test_bundles', array('test_bundle' => array('label' => 'Test Bundle')));
  $test_entity_modes = array(
    'full' => array(
      'label' => t('Full object'),
      'custom settings' => TRUE,
    ),
    'teaser' => array(
      'label' => t('Teaser'),
      'custom settings' => TRUE,
    ),
  );

  return array(
    'test_entity' => array(
      'name' => t('Test Entity'),
      'fieldable' => TRUE,
      'field cache' => FALSE,
      'base table' => 'test_entity',
      'revision table' => 'test_entity_revision',
      'entity keys' => array(
        'id' => 'ftid',
        'revision' => 'ftvid',
        'bundle' => 'fttype',
      ),
      'bundles' => $bundles,
      'view modes' => $test_entity_modes,
    ),
    // This entity type doesn't get form handling for now...
    'test_cacheable_entity' => array(
      'name' => t('Test Entity, cacheable'),
      'fieldable' => TRUE,
      'field cache' => TRUE,
      'entity keys' => array(
        'id' => 'ftid',
        'revision' => 'ftvid',
        'bundle' => 'fttype',
      ),
      'bundles' => $bundles,
      'view modes' => $test_entity_modes,
    ),
    'test_entity_bundle_key' => array(
      'name' => t('Test Entity with a bundle key.'),
      'base table' => 'test_entity_bundle_key',
      'fieldable' => TRUE,
      'field cache' => FALSE,
      'entity keys' => array(
        'id' => 'ftid',
        'bundle' => 'fttype',
      ),
      'bundles' => array('bundle1' => array('label' => 'Bundle1'), 'bundle2' => array('label' => 'Bundle2')),
      'view modes' => $test_entity_modes,
    ),
    'test_entity_bundle' => array(
      'name' => t('Test Entity with a specified bundle.'),
      'base table' => 'test_entity_bundle',
      'fieldable' => TRUE,
      'field cache' => FALSE,
      'entity keys' => array(
        'id' => 'ftid',
      ),
      'bundles' => array('test_entity_2' => array('label' => 'Test entity 2')),
      'view modes' => $test_entity_modes,
    ),
    // @see EntityPropertiesTestCase::testEntityLabel()
    'test_entity_no_label' => array(
      'name' => t('Test entity without label'),
      'fieldable' => TRUE,
      'field cache' => FALSE,
      'base table' => 'test_entity',
      'entity keys' => array(
        'id' => 'ftid',
        'revision' => 'ftvid',
        'bundle' => 'fttype',
      ),
      'bundles' => $bundles,
      'view modes' => $test_entity_modes,
    ),
    'test_entity_label' => array(
      'name' => t('Test entity label'),
      'fieldable' => TRUE,
      'field cache' => FALSE,
      'base table' => 'test_entity',
      'entity keys' => array(
        'id' => 'ftid',
        'revision' => 'ftvid',
        'bundle' => 'fttype',
        'label' => 'ftlabel',
      ),
      'bundles' => $bundles,
      'view modes' => $test_entity_modes,
    ),
    'test_entity_label_callback' => array(
      'name' => t('Test entity label callback'),
      'fieldable' => TRUE,
      'field cache' => FALSE,
      'base table' => 'test_entity',
      'label callback' => 'field_test_entity_label_callback',
      'entity keys' => array(
        'id' => 'ftid',
        'revision' => 'ftvid',
        'bundle' => 'fttype',
      ),
      'bundles' => $bundles,
      'view modes' => $test_entity_modes,
    ),
  );
}

/**
 * Implements hook_entity_info_alter().
 */
function field_test_entity_info_alter(&$entity_info) {
  // Enable/disable field_test as a translation handler.
  foreach (field_test_entity_info_translatable() as $entity_type => $translatable) {
    $entity_info[$entity_type]['translation']['field_test'] = $translatable;
  }
  // Disable locale as a translation handler.
  foreach ($entity_info as $entity_type => $info) {
    $entity_info[$entity_type]['translation']['locale'] = FALSE;
  }
}

/**
 * Helper function to enable entity translations.
 */
function field_test_entity_info_translatable($entity_type = NULL, $translatable = NULL) {
  drupal_static_reset('field_has_translation_handler');
  $stored_value = &drupal_static(__FUNCTION__, array());
  if (isset($entity_type)) {
    $stored_value[$entity_type] = $translatable;
    entity_info_cache_clear();
  }
  return $stored_value;
}

/**
 * Creates a new bundle for test_entity entities.
 *
 * @param $bundle
 *   The machine-readable name of the bundle.
 * @param $text
 *   The human-readable name of the bundle. If none is provided, the machine
 *   name will be used.
 */
function field_test_create_bundle($bundle, $text = NULL) {
  $bundles = variable_get('field_test_bundles', array('test_bundle' => array('label' => 'Test Bundle')));
  $bundles += array($bundle => array('label' => $text ? $text : $bundle));
  variable_set('field_test_bundles', $bundles);

  $info = field_test_entity_info();
  foreach ($info as $type => $type_info) {
    field_attach_create_bundle($type, $bundle);
  }
}

/**
 * Renames a bundle for test_entity entities.
 *
 * @param $bundle_old
 *   The machine-readable name of the bundle to rename.
 * @param $bundle_new
 *   The new machine-readable name of the bundle.
 */
function field_test_rename_bundle($bundle_old, $bundle_new) {
  $bundles = variable_get('field_test_bundles', array('test_bundle' => array('label' => 'Test Bundle')));
  $bundles[$bundle_new] = $bundles[$bundle_old];
  unset($bundles[$bundle_old]);
  variable_set('field_test_bundles', $bundles);

  $info = field_test_entity_info();
  foreach ($info as $type => $type_info) {
    field_attach_rename_bundle($type, $bundle_old, $bundle_new);
  }
}

/**
 * Deletes a bundle for test_entity objects.
 *
 * @param $bundle
 *   The machine-readable name of the bundle to delete.
 */
function field_test_delete_bundle($bundle) {
  $bundles = variable_get('field_test_bundles', array('test_bundle' => array('label' => 'Test Bundle')));
  unset($bundles[$bundle]);
  variable_set('field_test_bundles', $bundles);

  $info = field_test_entity_info();
  foreach ($info as $type => $type_info) {
    field_attach_delete_bundle($type, $bundle);
  }
}

/**
 * Creates a basic test_entity entity.
 */
function field_test_create_stub_entity($id = 1, $vid = 1, $bundle = 'test_bundle', $label = '') {
  $entity = new stdClass();
  // Only set id and vid properties if they don't come as NULL (creation form).
  if (isset($id)) {
    $entity->ftid = $id;
  }
  if (isset($vid)) {
    $entity->ftvid = $vid;
  }
  $entity->fttype = $bundle;

  $label = !empty($label) ? $label : $bundle . ' label';
  $entity->ftlabel = $label;

  return $entity;
}

/**
 * Loads a test_entity.
 *
 * @param $ftid
 *   The id of the entity to load.
 * @param $ftvid
 *   (Optional) The revision id of the entity to load. If not specified, the
 *   current revision will be used.
 * @return
 *   The loaded entity.
 */
function field_test_entity_test_load($ftid, $ftvid = NULL) {
  // Load basic strucure.
  $query = db_select('test_entity', 'fte', array())
    ->condition('fte.ftid', $ftid);

  if ($ftvid) {
    $query->join('test_entity_revision', 'fter', 'fte.ftid = fter.ftid');
    $query->addField('fte', 'ftid');
    $query->addField('fte', 'fttype');
    $query->addField('fter', 'ftvid');
    $query->condition('fter.ftvid', $ftvid);
  }
  else {
    $query->fields('fte');
  }

  $entities = $query->execute()->fetchAllAssoc('ftid');

  // Attach fields.
  if ($ftvid) {
    field_attach_load_revision('test_entity', $entities);
  }
  else {
    field_attach_load('test_entity', $entities);
  }

  return $entities[$ftid];
}

/**
 * Saves a test_entity.
 *
 * A new entity is created if $entity->ftid and $entity->is_new are both empty.
 * A new revision is created if $entity->revision is not empty.
 *
 * @param $entity
 *   The entity to save.
 */
function field_test_entity_save(&$entity) {
  field_attach_presave('test_entity', $entity);

  if (!isset($entity->is_new)) {
    $entity->is_new = empty($entity->ftid);
  }

  if (!$entity->is_new && !empty($entity->revision)) {
    $entity->old_ftvid = $entity->ftvid;
    unset($entity->ftvid);
  }

  $update_entity = TRUE;
  if ($entity->is_new) {
    drupal_write_record('test_entity', $entity);
    drupal_write_record('test_entity_revision', $entity);
    $op = 'insert';
  }
  else {
    drupal_write_record('test_entity', $entity, 'ftid');
    if (!empty($entity->revision)) {
      drupal_write_record('test_entity_revision', $entity);
    }
    else {
      drupal_write_record('test_entity_revision', $entity, 'ftvid');
      $update_entity = FALSE;
    }
    $op = 'update';
  }
  if ($update_entity) {
    db_update('test_entity')
      ->fields(array('ftvid' => $entity->ftvid))
      ->condition('ftid', $entity->ftid)
      ->execute();
  }

  // Save fields.
  $function = "field_attach_$op";
  $function('test_entity', $entity);
}

/**
 * Menu callback: displays the 'Add new test_entity' form.
 */
function field_test_entity_add($fttype) {
  $fttype = str_replace('-', '_', $fttype);
  $entity = (object)array('fttype' => $fttype);
  drupal_set_title(t('Create test_entity @bundle', array('@bundle' => $fttype)), PASS_THROUGH);
  return drupal_get_form('field_test_entity_form', $entity, TRUE);
}

/**
 * Menu callback: displays the 'Edit exiisting test_entity' form.
 */
function field_test_entity_edit($entity) {
  drupal_set_title(t('test_entity @ftid revision @ftvid', array('@ftid' => $entity->ftid, '@ftvid' => $entity->ftvid)), PASS_THROUGH);
  return drupal_get_form('field_test_entity_form', $entity);
}

/**
 * Test_entity form.
 */
function field_test_entity_form($form, &$form_state, $entity, $add = FALSE) {
  // During initial form build, add the entity to the form state for use during
  // form building and processing. During a rebuild, use what is in the form
  // state.
  if (!isset($form_state['test_entity'])) {
    $form_state['test_entity'] = $entity;
  }
  else {
    $entity = $form_state['test_entity'];
  }

  foreach (array('ftid', 'ftvid', 'fttype') as $key) {
    $form[$key] = array(
      '#type' => 'value',
      '#value' => isset($entity->$key) ? $entity->$key : NULL,
    );
  }

  // Add field widgets.
  $form['#builder_function'] = 'field_test_entity_form_submit_builder';
  field_attach_form('test_entity', $entity, $form, $form_state);

  if (!$add) {
    $form['revision'] = array(
      '#access' => user_access('administer field_test content'),
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => FALSE,
      '#weight' => 100,
    );
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 101,
  );

  return $form;
}

/**
 * Validate handler for field_test_entity_form().
 */
function field_test_entity_form_validate($form, &$form_state) {
  entity_form_field_validate('test_entity', $form, $form_state);
}

/**
 * Submit handler for field_test_entity_form().
 */
function field_test_entity_form_submit($form, &$form_state) {
  $entity = $form['#builder_function']($form, $form_state);
  $insert = empty($entity->ftid);
  field_test_entity_save($entity);

  $message = $insert ? t('test_entity @id has been created.', array('@id' => $entity->ftid)) : t('test_entity @id has been updated.', array('@id' => $entity->ftid));
  drupal_set_message($message);

  if ($entity->ftid) {
    $form_state['redirect'] = 'test-entity/' . $entity->ftid . '/edit';
  }
  else {
    // Error on save.
    drupal_set_message(t('The entity could not be saved.'), 'error');
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Updates the form state's entity by processing this submission's values.
 */
function field_test_entity_form_submit_builder($form, &$form_state) {
  $entity = $form_state['test_entity'];
  entity_form_submit_build_entity('test_entity', $entity, $form, $form_state);
  return $entity;
}
