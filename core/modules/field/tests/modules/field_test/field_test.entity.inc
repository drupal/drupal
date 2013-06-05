<?php

/**
 * @file
 * Defines an entity type.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\field_test\Plugin\Core\Entity\TestEntity;

/**
 * Implements hook_entity_info().
 */
function field_test_entity_info() {
  // If requested, clear the field cache while this is being called. See
  // Drupal\field\Tests\FieldInfoTest::testFieldInfoCache().
  if (Drupal::state()->get('field_test.clear_info_cache_in_hook_entity_info')) {
    field_info_cache_clear();
  }
}

/**
 * Implements hook_entity_info_alter().
 */
function field_test_entity_info_alter(&$entity_info) {
  foreach (field_test_entity_info_translatable() as $entity_type => $translatable) {
    $entity_info[$entity_type]['translatable'] = $translatable;
  }
}

/**
 * Implements hook_entity_view_mode_info_alter().
 */
function field_test_entity_view_mode_info_alter(&$view_modes) {
  $entity_info = entity_get_info();
  foreach ($entity_info as $entity_type => $info) {
    if ($entity_info[$entity_type]['module'] == 'field_test') {
      $view_modes[$entity_type] = array(
        'full' => array(
          'label' => t('Full object'),
          'status' => TRUE,
        ),
        'teaser' => array(
          'label' => t('Teaser'),
          'status' => TRUE,
        ),
      );
    }
  }
}

/**
 * Implements hook_entity_bundle_info_alter().
 */
function field_test_entity_bundle_info_alter(&$bundles) {
  $entity_info = entity_get_info();
  foreach ($bundles as $entity_type => $info) {
    if ($entity_info[$entity_type]['module'] == 'field_test') {
      $bundles[$entity_type] = Drupal::state()->get('field_test_bundles') ?: array('test_bundle' => array('label' => 'Test Bundle'));
      if ($entity_type == 'test_entity_bundle') {
        $bundles[$entity_type] += array('test_entity_2' => array('label' => 'Test entity 2'));
      }
      if ($entity_type == 'test_entity_bundle_key') {
        $bundles[$entity_type] += array('bundle1' => array('label' => 'Bundle1'), 'bundle2' => array('label' => 'Bundle2'));
      }
    }
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
  $bundles = Drupal::state()->get('field_test.bundles') ?: array('test_bundle' => array('label' => 'Test Bundle'));
  $bundles += array($bundle => array('label' => $text ? $text : $bundle));
  Drupal::state()->set('field_test.bundles', $bundles);

  $info = entity_get_info();
  foreach ($info as $type => $type_info) {
    if ($type_info['module'] == 'field_test') {
      entity_invoke_bundle_hook('create', $type, $bundle);
    }
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
  $bundles = Drupal::state()->get('field_test.bundles') ?: array('test_bundle' => array('label' => 'Test Bundle'));
  $bundles[$bundle_new] = $bundles[$bundle_old];
  unset($bundles[$bundle_old]);
  Drupal::state()->set('field_test.bundles', $bundles);

  $info = entity_get_info();
  foreach ($info as $type => $type_info) {
    if ($type_info['module'] == 'field_test') {
      entity_invoke_bundle_hook('rename', $type, $bundle_old, $bundle_new);
    }
  }
}

/**
 * Deletes a bundle for test_entity objects.
 *
 * @param $bundle
 *   The machine-readable name of the bundle to delete.
 */
function field_test_delete_bundle($bundle) {
  $bundles = Drupal::state()->get('field_test.bundles') ?: array('test_bundle' => array('label' => 'Test Bundle'));
  unset($bundles[$bundle]);
  Drupal::state()->set('field_test.bundles', $bundles);

  $info = entity_get_info();
  foreach ($info as $type => $type_info) {
    if ($type_info['module'] == 'field_test') {
      entity_invoke_bundle_hook('delete', $type, $bundle);
    }
  }
}

/**
 * Creates a basic test_entity entity.
 */
function field_test_create_entity($id = 1, $vid = 1, $bundle = 'test_bundle', $label = '') {
  $entity = entity_create('test_entity', array('fttype' => $bundle));
  // Only set id and vid properties if they don't come as NULL (creation form).
  if (isset($id)) {
    $entity->ftid = $id;
  }
  if (isset($vid)) {
    $entity->ftvid = $vid;
    // Flag to make sure that the provided vid is used for a new revision.
    $entity->use_provided_revision_id = $vid;
  }

  $label = !empty($label) ? $label : $bundle . ' label';
  $entity->ftlabel = $label;
  // Make sure the entity will saved even if a primary key is provided.
  $entity->enforceIsNew();
  $entity->setNewRevision();
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
  // Prevent this from being called as hook_entity_test_load().
  if (is_array($ftid)) {
    return;
  }
  $ids = (isset($ftid) ? array($ftid) : array());
  $conditions = (isset($ftvid) ? array('ftvid' => $ftvid) : array());
  $test_entity = entity_load_multiple('test_entity', $ids, $conditions);
  return $test_entity ? reset($test_entity) : FALSE;
}

/**
 * Saves a test_entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to save.
 */
function field_test_entity_save(EntityInterface $entity) {
  $entity->save();
}

/**
 * Menu callback: displays the 'Add new test_entity' form.
 */
function field_test_entity_add($fttype) {
  $fttype = str_replace('-', '_', $fttype);
  $entity = field_test_create_entity(NULL, NULL, $fttype);
  drupal_set_title(t('Create test_entity @bundle', array('@bundle' => $fttype)), PASS_THROUGH);
  return entity_get_form($entity);
}

/**
 * Menu callback: displays the 'Edit exiisting test_entity' form.
 */
function field_test_entity_edit(TestEntity $entity) {
  drupal_set_title(t('test_entity @ftid revision @ftvid', array('@ftid' => $entity->ftid, '@ftvid' => $entity->ftvid)), PASS_THROUGH);
  return entity_get_form($entity);
}

/**
 * Form combining two separate entities.
 */
function field_test_entity_nested_form($form, &$form_state, $entity_1, $entity_2) {
  // First entity.
  foreach (array('ftid', 'ftvid', 'fttype') as $key) {
    $form[$key] = array(
      '#type' => 'value',
      '#value' => $entity_1->$key,
    );
  }
  $form_state['form_display'] = entity_get_form_display($entity_1->entityType(), $entity_1->bundle(), 'default');
  field_attach_form($entity_1, $form, $form_state);

  // Second entity.
  $form['entity_2'] = array(
    '#type' => 'details',
    '#title' => t('Second entity'),
    '#tree' => TRUE,
    '#parents' => array('entity_2'),
    '#weight' => 50,
  );
  foreach (array('ftid', 'ftvid', 'fttype') as $key) {
    $form['entity_2'][$key] = array(
      '#type' => 'value',
      '#value' => $entity_2->$key,
    );
  }
  $form_state['form_display'] = entity_get_form_display($entity_1->entityType(), $entity_1->bundle(), 'default');
  field_attach_form($entity_2, $form['entity_2'], $form_state);

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 100,
  );

  return $form;
}

/**
 * Validate handler for field_test_entity_nested_form().
 */
function field_test_entity_nested_form_validate($form, &$form_state) {
  $entity_1 = entity_create('test_entity', $form_state['values']);
  field_attach_extract_form_values($entity_1, $form, $form_state);
  field_attach_form_validate($entity_1, $form, $form_state);

  $entity_2 = entity_create('test_entity', $form_state['values']['entity_2']);
  field_attach_extract_form_values($entity_2, $form['entity_2'], $form_state);
  field_attach_form_validate($entity_2, $form['entity_2'], $form_state);
}

/**
 * Submit handler for field_test_entity_nested_form().
 */
function field_test_entity_nested_form_submit($form, &$form_state) {
  $entity_1 = entity_create('test_entity', $form_state['values']);
  field_attach_extract_form_values($entity_1, $form, $form_state);
  field_test_entity_save($entity_1);

  $entity_2 = entity_create('test_entity', $form_state['values']['entity_2']);
  field_attach_extract_form_values($entity_2, $form['entity_2'], $form_state);
  field_test_entity_save($entity_2);

  drupal_set_message(t('test_entities @id_1 and @id_2 have been updated.', array('@id_1' => $entity_1->ftid, '@id_2' => $entity_2->ftid)));
}
