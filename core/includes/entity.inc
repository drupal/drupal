<?php

/**
 * @file
 * Entity API for handling entities like nodes or users.
 */

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Gets the entity definition for an entity type.
 *
 * @param string|null $entity_type
 *   (optional) The entity type (e.g. 'node'). Leave NULL to retrieve
 *   information for all entity types.
 *
 * @return array
 *   An array containing the entity type's definition, as retrieved with
 *   \Drupal\Core\Entity\EntityManager. If $entity_type is NULL, an associative
 *   array of all entity type definitions keyed by entity type is returned.
 *
 * @see \Drupal\Core\Entity\EntityManager
 * @see hook_entity_info_alter()
 */
function entity_get_info($entity_type = NULL) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['entity_info'] = &drupal_static(__FUNCTION__);
  }
  $entity_info = &$drupal_static_fast['entity_info'];

  if (empty($entity_info)) {
    $entity_info = drupal_container()->get('plugin.manager.entity')->getDefinitions();
  }

  if (empty($entity_type)) {
    return $entity_info;
  }
  elseif (isset($entity_info[$entity_type])) {
    return $entity_info[$entity_type];
  }
}

/**
 * Resets the cached information about entity types.
 */
function entity_info_cache_clear() {
  drupal_static_reset('entity_get_info');
  // Clear all languages.
  cache()->invalidateTags(array('entity_info' => TRUE));
}

/**
 * Loads an entity from the database.
 *
 * @param string $entity_type
 *   The entity type to load, e.g. node or user.
 * @param int $id
 *   The id of the entity to load.
 * @param bool $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return Drupal\Core\Entity\EntityInterface
 *   The entity object, or FALSE if there is no entity with the given id.
 *
 * @see \Drupal\Core\Entity\EntityManager
 * @see entity_load_multiple()
 * @see Drupal\Core\Entity\EntityStorageControllerInterface
 * @see Drupal\Core\Entity\DatabaseStorageController
 * @see Drupal\Core\Entity\Query\QueryInterface
 */
function entity_load($entity_type, $id, $reset = FALSE) {
  $entities = entity_load_multiple($entity_type, array($id), $reset);
  return isset($entities[$id]) ? $entities[$id] : FALSE;
}

/**
 * Loads an entity from the database.
 *
 * @param string $entity_type
 *   The entity type to load, e.g. node or user.
 * @param int $revision_id
 *   The id of the entity to load.
 *
 * @return Drupal\Core\Entity\EntityInterface
 *   The entity object, or FALSE if there is no entity with the given revision
 *   id.
 *
 * @see \Drupal\Core\Entity\EntityManager
 * @see Drupal\Core\Entity\EntityStorageControllerInterface
 * @see Drupal\Core\Entity\DatabaseStorageController
 */
function entity_revision_load($entity_type, $revision_id) {
  return entity_get_controller($entity_type)->loadRevision($revision_id);
}

/**
 * Deletes an entity revision.
 *
 * @param string $entity_type
 *   The entity type to load, e.g. node or user.
 * @param $revision_id
 *   The revision ID to delete.
 */
function entity_revision_delete($entity_type, $revision_id) {
  entity_get_controller($entity_type)->deleteRevision($revision_id);
}

/**
 * Loads an entity by UUID.
 *
 * Note that some entity types may not support UUIDs.
 *
 * @param string $entity_type
 *   The entity type to load; e.g., 'node' or 'user'.
 * @param string $uuid
 *   The UUID of the entity to load.
 * @param bool $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return EntityInterface|FALSE
 *   The entity object, or FALSE if there is no entity with the given UUID.
 *
 * @throws Drupal\Core\Entity\EntityStorageException
 *   Thrown in case the requested entity type does not support UUIDs.
 *
 * @see \Drupal\Core\Entity\EntityManager
 */
function entity_load_by_uuid($entity_type, $uuid, $reset = FALSE) {
  $entity_info = entity_get_info($entity_type);
  if (empty($entity_info['entity_keys']['uuid'])) {
    throw new EntityStorageException("Entity type $entity_type does not support UUIDs.");
  }
  $uuid_key = $entity_info['entity_keys']['uuid'];

  $controller = entity_get_controller($entity_type);
  if ($reset) {
    $controller->resetCache();
  }
  $entities = $controller->loadByProperties(array($uuid_key => $uuid));
  return reset($entities);
}

/**
 * Loads multiple entities from the database.
 *
 * This function should be used whenever you need to load more than one entity
 * from the database. The entities are loaded into memory and will not require
 * database access if loaded again during the same page request.
 *
 * The actual loading is done through a class that has to implement the
 * Drupal\Core\Entity\EntityStorageControllerInterface interface. By default,
 * Drupal\Core\Entity\DatabaseStorageController is used. Entity types can
 * specify that a different class should be used by setting the
 * 'controller_class' key in the entity plugin annotation. These classes can
 * either implement the Drupal\Core\Entity\EntityStorageControllerInterface
 * interface, or, most commonly, extend the
 * Drupal\Core\Entity\DatabaseStorageController class.
 * See Drupal\node\Plugin\Core\Entity\Node and Drupal\node\NodeStorageController
 * for an example.
 *
 * @param string $entity_type
 *   The entity type to load, e.g. node or user.
 * @param array $ids
 *   (optional) An array of entity IDs. If omitted, all entities are loaded.
 * @param bool $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return array
 *   An array of entity objects indexed by their ids.
 *
 * @see \Drupal\Core\Entity\EntityManager
 * @see Drupal\Core\Entity\EntityStorageControllerInterface
 * @see Drupal\Core\Entity\DatabaseStorageController
 * @see Drupal\Core\Entity\Query\QueryInterface
 */
function entity_load_multiple($entity_type, array $ids = NULL, $reset = FALSE) {
  if ($reset) {
    entity_get_controller($entity_type)->resetCache();
  }
  return entity_get_controller($entity_type)->load($ids);
}

/**
 * Load entities by their property values.
 *
 * @param string $entity_type
 *   The entity type to load, e.g. node or user.
 * @param array $values
 *   An associative array where the keys are the property names and the
 *   values are the values those properties must have.
 *
 * @return array
 *   An array of entity objects indexed by their ids.
 */
function entity_load_multiple_by_properties($entity_type, array $values) {
  return entity_get_controller($entity_type)->loadByProperties($values);
}

/**
 * Loads the unchanged, i.e. not modified, entity from the database.
 *
 * Unlike entity_load() this function ensures the entity is directly loaded from
 * the database, thus bypassing any static cache. In particular, this function
 * is useful to determine changes by comparing the entity being saved to the
 * stored entity.
 *
 * @param $entity_type
 *   The entity type to load, e.g. node or user.
 * @param $id
 *   The ID of the entity to load.
 *
 * @return
 *   The unchanged entity, or FALSE if the entity cannot be loaded.
 */
function entity_load_unchanged($entity_type, $id) {
  entity_get_controller($entity_type)->resetCache(array($id));
  $result = entity_get_controller($entity_type)->load(array($id));
  return reset($result);
}

/**
 * Deletes multiple entities permanently.
 *
 * @param $entity_type
 *   The type of the entity.
 * @param $ids
 *   An array of entity IDs of the entities to delete.
 */
function entity_delete_multiple($entity_type, $ids) {
  entity_get_controller($entity_type)->delete($ids);
}

/**
 * Constructs a new entity object, without permanently saving it.
 *
 * @param $entity_type
 *   The type of the entity.
 * @param $values
 *   An array of values to set, keyed by property name. If the entity type has
 *   bundles the bundle key has to be specified.
 *
 * @return Drupal\Core\Entity\EntityInterface
 *   A new entity object.
 */
function entity_create($entity_type, array $values) {
  return entity_get_controller($entity_type)->create($values);
}

/**
 * Gets the entity controller class for an entity type.
 *
 * @return Drupal\Core\Entity\EntityStorageControllerInterface
 */
function entity_get_controller($entity_type) {
  $controllers = &drupal_static(__FUNCTION__, array());
  if (!isset($controllers[$entity_type])) {
    $type_info = entity_get_info($entity_type);
    $class = $type_info['controller_class'];
    $controllers[$entity_type] = new $class($entity_type);
  }
  return $controllers[$entity_type];
}

/**
 * Returns the label of an entity.
 *
 * This is a wrapper for Drupal\Core\Entity\EntityInterface::label(). This function
 * should only be used as a callback, e.g. for menu title callbacks.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which to generate the label.
 * @param $langcode
 *   (optional) The language code of the language that should be used for
 *   getting the label. If set to NULL, the entity's default language is
 *   used.
 *
 * @return
 *   The label of the entity, or NULL if there is no label defined.
 *
 * @see Drupal\Core\Entity\EntityInterface::label()
 */
function entity_page_label(EntityInterface $entity, $langcode = NULL) {
  return $entity->label($langcode);
}

/**
 * Returns an entity form controller for the given operation.
 *
 * Since there might be different scenarios in which an entity is edited,
 * multiple form controllers suitable to the different operations may be defined.
 * If no controller is found for the default operation, the base class will be
 * used. If a non-existing non-default operation is specified an exception will
 * be thrown.
 *
 * @see \Drupal\Core\Entity\EntityManager
 *
 * @param $entity_type
 *   The type of the entity.
 * @param $operation
 *   (optional) The name of an operation, such as creation, editing or deletion,
 *   identifying the controlled form. Defaults to 'default' which is the usual
 *   create/edit form.
 *
 * @return Drupal\Core\Entity\EntityFormControllerInterface
 *   An entity form controller instance.
 */
function entity_form_controller($entity_type, $operation = 'default') {
  $info = entity_get_info($entity_type);

  // Check whether there is a form controller class for the specified operation.
  if (!empty($info['form_controller_class'][$operation])) {
    $class = $info['form_controller_class'][$operation];
  }
  // If no controller is specified default to the base implementation.
  elseif (empty($info['form_controller_class']) && $operation == 'default') {
    $class = 'Drupal\Core\Entity\EntityFormController';
  }
  // If a non-existing operation has been specified stop.
  else {
    throw new InvalidArgumentException("Missing form controller for '$entity_type', operation '$operation'");
  }

  return new $class($operation);
}

/**
 * Returns the form id for the given entity and operation.
 *
 * @param EntityInterface $entity
 *   The entity to be created or edited.
 * @param $operation
 *   (optional) The operation for the form to be processed.
 *
 * @return
 *   A string representing the entity form id.
 */
function entity_form_id(EntityInterface $entity, $operation = 'default') {
  $entity_type = $entity->entityType();
  $bundle = $entity->bundle();
  $form_id = $entity_type;
  if ($bundle != $entity_type) {
    $form_id = $bundle . '_' . $form_id;
  }
  if ($operation != 'default') {
    $form_id = $form_id . '_' . $operation;
  }
  return $form_id . '_form';
}

/**
 * Returns the default form state for the given entity and operation.
 *
 * @param EntityInterface $entity
 *   The entity to be created or edited.
 * @param $operation
 *   (optional) The operation identifying the form to be processed.
 *
 * @return
 *   A $form_state array already filled the entity form controller.
 */
function entity_form_state_defaults(EntityInterface $entity, $operation = 'default', $langcode = NULL) {
  $form_state = array();
  $controller = entity_form_controller($entity->entityType(), $operation);
  $form_state['build_info']['callback'] = array($controller, 'build');
  $form_state['build_info']['base_form_id'] = $entity->entityType() . '_form';
  $form_state['build_info']['args'] = array($entity);
  $form_state['langcode'] = $langcode;
  return $form_state;
}

/**
 * Retrieves, populates, and processes an entity form.
 *
 * @param EntityInterface $entity
 *   The entity to be created or edited.
 * @param $operation
 *   (optional) The operation identifying the form to be submitted.
 * @param $form_state
 *   (optional) A keyed array containing the current state of the form.
 *
 * @return
 *   A $form_state array already filled with the entity form controller.
 */
function entity_form_submit(EntityInterface $entity, $operation = 'default', &$form_state = array()) {
  $form_state += entity_form_state_defaults($entity, $operation);
  $form_id = entity_form_id($entity, $operation);
  drupal_form_submit($form_id, $form_state);
}

/**
 * Returns the built and processed entity form for the given entity.
 *
 * @param EntityInterface $entity
 *   The entity to be created or edited.
 * @param $operation
 *   (optional) The operation identifying the form variation to be returned.
 *
 * @return
 *   The processed form for the given entity and operation.
 */
function entity_get_form(EntityInterface $entity, $operation = 'default', $langcode = NULL) {
  $form_state = entity_form_state_defaults($entity, $operation, $langcode);
  $form_id = entity_form_id($entity, $operation);
  return drupal_build_form($form_id, $form_state);
}

/**
 * Copies submitted values to entity properties for simple entity forms.
 *
 * During the submission handling of an entity form's "Save", "Preview", and
 * possibly other buttons, the form state's entity needs to be updated with the
 * submitted form values. Each entity form implements its own builder function
 * for doing this, appropriate for the particular entity and form, whereas
 * modules may specify additional builder functions in $form['#entity_builders']
 * for copying the form values of added form elements to entity properties.
 * Many of the main entity builder functions can call this helper function to
 * re-use its logic of copying $form_state['values'][PROPERTY] values to
 * $entity->PROPERTY for all entries in $form_state['values'] that are not field
 * data, and calling field_attach_submit() to copy field data. Apart from that
 * this helper invokes any additional builder functions that have been specified
 * in $form['#entity_builders'].
 *
 * For some entity forms (e.g., forms with complex non-field data and forms that
 * simultaneously edit multiple entities), this behavior may be inappropriate,
 * so the builder function for such forms needs to implement the required
 * functionality instead of calling this function.
 */
function entity_form_submit_build_entity($entity_type, $entity, $form, &$form_state) {
  $info = entity_get_info($entity_type);

  // Copy top-level form values that are not for fields to entity properties,
  // without changing existing entity properties that are not being edited by
  // this form. Copying field values must be done using field_attach_submit().
  $values_excluding_fields = $info['fieldable'] ? array_diff_key($form_state['values'], field_info_instances($entity_type, $entity->bundle())) : $form_state['values'];
  foreach ($values_excluding_fields as $key => $value) {
    $entity->$key = $value;
  }

  // Invoke all specified builders for copying form values to entity properties.
  if (isset($form['#entity_builders'])) {
    foreach ($form['#entity_builders'] as $function) {
      $function($entity_type, $entity, $form, $form_state);
    }
  }

  // Copy field values to the entity.
  if ($info['fieldable']) {
    field_attach_submit($entity_type, $entity, $form, $form_state);
  }
}

/**
 * Returns an entity list controller for a given entity type.
 *
 * @param string $entity_type
 *   The type of the entity.
 *
 * @return Drupal\Core\Entity\EntityListControllerInterface
 *   An entity list controller.
 *
 * @see \Drupal\Core\Entity\EntityManager
 */
function entity_list_controller($entity_type) {
  $storage = entity_get_controller($entity_type);
  $entity_info = entity_get_info($entity_type);
  $class = $entity_info['list_controller_class'];
  return new $class($entity_type, $storage);
}

/**
 * Returns an entity render controller for a given entity type.
 *
 * @param string $entity_type
 *   The type of the entity.
 *
 * @return Drupal\Core\Entity\EntityRenderControllerInterface
 *   An entity render controller.
 *
 * @see \Drupal\Core\Entity\EntityManager
 */
function entity_render_controller($entity_type) {
  $info = entity_get_info($entity_type);
  $class = $info['render_controller_class'];
  return new $class($entity_type);
}

/**
 * Returns the render array for an entity.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity to be rendered.
 * @param string $view_mode
 *   The view mode that should be used to display the entity.
 * @param string $langcode
 *   (optional) For which language the entity should be rendered, defaults to
 *   the current content language.
 *
 * @return array
 *   A render array for the entity.
 */
function entity_view(EntityInterface $entity, $view_mode, $langcode = NULL) {
  return entity_render_controller($entity->entityType())->view($entity, $view_mode, $langcode);
}

/**
 * Returns the render array for the provided entities.
 *
 * @param array $entities
 *   The entities to be rendered, must be of the same type.
 * @param string $view_mode
 *   The view mode that should be used to display the entity.
 * @param string $langcode
 *   (optional) For which language the entity should be rendered, defaults to
 *   the current content language.
 *
 * @return array
 *   A render array for the entities, indexed by the same keys as the
 *   entities array passed in $entities.
 */
function entity_view_multiple(array $entities, $view_mode, $langcode = NULL) {
  return entity_render_controller(reset($entities)->entityType())->viewMultiple($entities, $view_mode, $langcode);
}

/**
 * Returns the entity query object for this entity type.
 *
 * @param $entity_type
 *   The entity type, e.g. node, for which the query object should be
 *   returned.
 * @param $conjunction
 *   AND if all conditions in the query need to apply, OR if any of them is
 *   enough. Optional, defaults to AND.
 */
function entity_query($entity_type, $conjunction = 'AND') {
  return drupal_container()->get('entity.query')->get($entity_type, $conjunction);
}
