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
 *
 * @deprecated Use \Drupal\Core\Entity\EntityManager::getDefinitions() directly.
 */
function entity_get_info($entity_type = NULL) {
  if (empty($entity_type)) {
    return drupal_container()->get('plugin.manager.entity')->getDefinitions();
  }
  else {
    return drupal_container()->get('plugin.manager.entity')->getDefinition($entity_type);
  }
}

/**
 * Resets the cached information about entity types.
 */
function entity_info_cache_clear() {
  drupal_static_reset('entity_get_view_modes');
  drupal_static_reset('entity_get_bundles');
  // Clear all languages.
  drupal_container()->get('plugin.manager.entity')->clearCachedDefinitions();
}

/**
 * Returns the entity bundle info.
 *
 * @param string|null $entity_type
 *   The entity type whose bundle info should be returned, or NULL for all
 *   bundles info. Defaults to NULL.
 *
 * @return array
 *   The bundle info for a specific entity type, or all entity types.
 */
function entity_get_bundles($entity_type = NULL) {
  $bundles = &drupal_static(__FUNCTION__);
  if (!$bundles) {
    $langcode = language(LANGUAGE_TYPE_INTERFACE)->langcode;
    if ($cache = cache()->get("entity_bundle_info:$langcode")) {
      $bundles = $cache->data;
    }
    else {
      $bundles = module_invoke_all('entity_bundle_info');
      // If no bundles are provided, use the entity type name and label.
      foreach (entity_get_info() as $type => $entity_info) {
        if (!isset($bundles[$type])) {
          $bundles[$type][$type]['label'] = $entity_info['label'];
        }
      }
      drupal_alter('entity_bundle_info', $bundles);
      cache()->set("entity_bundle_info:$langcode", $bundles, CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
    }
  }

  if (empty($entity_type)) {
    return $bundles;
  }
  elseif (isset($bundles[$entity_type])) {
    return $bundles[$entity_type];
  }

  return array();
}

/**
 * Returns the entity view mode info.
 *
 * @param string|null $entity_type
 *   The entity type whose view mode info should be returned, or NULL for all
 *   view mode info. Defaults to NULL.
 *
 * @return array
 *   The view mode info for a specific entity type, or all entity types.
 */
function entity_get_view_modes($entity_type = NULL) {
  $view_modes = &drupal_static(__FUNCTION__);
  if (!$view_modes) {
    $langcode = language(LANGUAGE_TYPE_INTERFACE)->langcode;
    if ($cache = cache()->get("entity_view_mode_info:$langcode")) {
      $view_modes = $cache->data;
    }
    else {
      $view_modes = module_invoke_all('entity_view_mode_info');
      foreach ($view_modes as $type => $entity_info) {
        foreach ($entity_info as $view_mode => $view_mode_info) {
          $view_modes[$type][$view_mode] += array(
            'custom_settings' => FALSE,
          );
        }
      }
      drupal_alter('entity_view_mode_info', $view_modes);
      cache()->set("entity_view_mode_info:$langcode", $view_modes, CacheBackendInterface::CACHE_PERMANENT, array('entity_info' => TRUE));
    }
  }

  if (empty($entity_type)) {
    return $view_modes;
  }
  elseif (isset($view_modes[$entity_type])) {
    return $view_modes[$entity_type];
  }

  return array();
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
  return drupal_container()->get('plugin.manager.entity')
    ->getStorageController($entity_type)
    ->loadRevision($revision_id);
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
  drupal_container()->get('plugin.manager.entity')
    ->getStorageController($entity_type)
    ->deleteRevision($revision_id);
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

  $controller = drupal_container()->get('plugin.manager.entity')->getStorageController($entity_type);
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
  $controller = drupal_container()->get('plugin.manager.entity')->getStorageController($entity_type);
  if ($reset) {
    $controller->resetCache();
  }
  return $controller->load($ids);
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
  return drupal_container()->get('plugin.manager.entity')
    ->getStorageController($entity_type)
    ->loadByProperties($values);
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
  $controller = drupal_container()->get('plugin.manager.entity')->getStorageController($entity_type);
  $controller->resetCache(array($id));
  $result = $controller->load(array($id));
  return reset($result);
}

/**
 * Deletes multiple entities permanently.
 *
 * @param string $entity_type
 *   The type of the entity.
 * @param array $ids
 *   An array of entity IDs of the entities to delete.
 */
function entity_delete_multiple($entity_type, array $ids) {
  $controller = drupal_container()->get('plugin.manager.entity')->getStorageController($entity_type);
  $entities = $controller->load($ids);
  $controller->delete($entities);
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
  return drupal_container()->get('plugin.manager.entity')
    ->getStorageController($entity_type)
    ->create($values);
}

/**
 * Gets the entity controller class for an entity type.
 *
 * @return Drupal\Core\Entity\EntityStorageControllerInterface
 *
 * @deprecated Use \Drupal\Core\Entity\EntityManager::getStorageController().
 */
function entity_get_controller($entity_type) {
  return drupal_container()->get('plugin.manager.entity')
    ->getStorageController($entity_type);
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
 * Returns the entity access controller for the given entity type.
 *
 * @param string $entity_type
 *   The type of the entity.
 *
 * @return \Drupal\Core\Entity\EntityAccessControllerInterface
 *   An entity access controller instance.
 *
 * @deprecated Use \Drupal\Core\Entity\EntityManager::getRenderController().
 */
function entity_access_controller($entity_type) {
  return drupal_container()->get('plugin.manager.entity')
    ->getAccessController($entity_type);
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
 *
 * @deprecated Use \Drupal\Core\Entity\EntityManager::getFormController().
 */
function entity_form_controller($entity_type, $operation = 'default') {
  return drupal_container()->get('plugin.manager.entity')
    ->getFormController($entity_type, $operation);
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
  $controller = drupal_container()->get('plugin.manager.entity')->getFormController($entity->entityType(), $operation);
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
 * $entity->PROPERTY for all entries in $form_state['values'] that are not
 * field data, and calling field_attach_extract_form_values() to copy field
 * data. Apart from that this helper invokes any additional builder functions
 * that have been specified in $form['#entity_builders'].
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
  // this form. Copying field values must be done using
  // field_attach_extract_form_values().
  $values_excluding_fields = $info['fieldable'] ? array_diff_key($form_state['values'], field_info_instances($entity_type, $entity->bundle())) : $form_state['values'];
  foreach ($values_excluding_fields as $key => $value) {
    $entity->set($key, $value);
  }

  // Invoke all specified builders for copying form values to entity properties.
  if (isset($form['#entity_builders'])) {
    foreach ($form['#entity_builders'] as $function) {
      call_user_func_array($function, array($entity_type, $entity, &$form, &$form_state));
    }
  }

  // Copy field values to the entity.
  if ($info['fieldable']) {
    field_attach_extract_form_values($entity, $form, $form_state);
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
 * @deprecated Use \Drupal\Core\Entity\EntityManager::getFormController().
 */
function entity_list_controller($entity_type) {
  return drupal_container()->get('plugin.manager.entity')
    ->getListController($entity_type);
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
 * @deprecated Use \Drupal\Core\Entity\EntityManager::getFormController().
 */
function entity_render_controller($entity_type) {
  return drupal_container()->get('plugin.manager.entity')
    ->getRenderController($entity_type);
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
  return drupal_container()->get('plugin.manager.entity')
    ->getRenderController($entity->entityType())
    ->view($entity, $view_mode, $langcode);
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
  return drupal_container()->get('plugin.manager.entity')
    ->getRenderController(reset($entities)->entityType())
    ->viewMultiple($entities, $view_mode, $langcode);
}

/**
 * Returns the entity_display object associated to a bundle and view mode.
 *
 * Use this function when assigning suggested display options for a component
 * in a given view mode. Note that they will only be actually used at render
 * time if the view mode itself is configured to use dedicated display settings
 * for the bundle; if not, the 'default' display is used instead.
 *
 * The function reads the entity_display object from the current configuration,
 * or returns a ready-to-use empty one if configuration entry exists yet for
 * this bundle and view mode. This streamlines manipulation of display objects
 * by always returning a consistent object that reflects the current state of
 * the configuration.
 *
 * Example usage:
 * - Set the 'body' field to be displayed and the 'field_image' field to be
 *   hidden on article nodes in the 'default' display.
 * @code
 * entity_get_display('node', 'article', 'default')
 *   ->setComponent('body', array(
 *     'type' => 'text_summary_or_trimmed',
 *     'settings' => array('trim_length' => '200')
 *     'weight' => 1,
 *   ))
 *   ->removeComponent('field_image')
 *   ->save();
 * @endcode
 *
 * @param string $entity_type
 *   The entity type.
 * @param string $bundle
 *   The bundle.
 * @param string $view_mode
 *   The view mode, or 'default' to retrieve the 'default' display object for
 *   this bundle.
 *
 * @return \Drupal\entity\Plugin\Core\Entity\EntityDisplay
 *   The display object associated to the view mode.
 */
function entity_get_display($entity_type, $bundle, $view_mode) {
  // Try loading the display from configuration.
  $display = entity_load('entity_display', $entity_type . '.' . $bundle . '.' . $view_mode);

  // If not found, create a fresh display object. We do not preemptively create
  // new entity_display configuration entries for each existing entity type and
  // bundle whenever a new view mode becomes available. Instead, configuration
  // entries are only created when a display object is explicitly configured
  // and saved.
  if (!$display) {
    $display = entity_create('entity_display', array(
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'viewMode' => $view_mode,
    ));
  }

  return $display;
}

/**
 * Returns the entity_display object used to render an entity.
 *
 * Depending on the configuration of the view mode for the bundle, this can be
 * either the display object associated to the view mode, or the 'default'
 * display.
 *
 * This function should only be used internally when rendering an entity. When
 * assigning suggested display options for a component in a given view mode,
 * entity_get_display() should be used instead, in order to avoid inadvertently
 * modifying the output of other view modes that might happen to use the
 * 'default' display too. Those options will then be effectively applied only
 * if the view mode is configured to use them.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being rendered.
 * @param string $view_mode
 *  The view mode being rendered.
 *
 * @return \Drupal\entity\Plugin\Core\Entity\EntityDisplay
 *   The display object that should be used to render the entity.
 *
 * @see entity_get_render_display().
 */
function entity_get_render_display(EntityInterface $entity, $view_mode) {
  $entity_type = $entity->entityType();
  $bundle = $entity->bundle();

  // Determine the display to use for rendering this entity. Depending on the
  // configuration of the view mode for this bundle, this will be either the
  // display associated to the view mode, or the 'default' display.
  $view_mode_settings = field_view_mode_settings($entity_type, $bundle);
  $render_view_mode = !empty($view_mode_settings[$view_mode]['custom_settings']) ? $view_mode : 'default';

  $display = entity_get_display($entity_type, $bundle, $render_view_mode);
  $display->originalViewMode = $view_mode;

  return $display;
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
 *
 * @return \Drupal\Core\Entity\Query\QueryInterface
 *   The query object that can query the given entity type.
 */
function entity_query($entity_type, $conjunction = 'AND') {
  return drupal_container()->get('entity.query')->get($entity_type, $conjunction);
}

/**
 * Generic access callback for entity pages.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which access is being checked.
 * @param string $operation
 *   (optional) The operation being performed on the entity. Defaults to 'view'.
 *
 * @return bool
 *   TRUE if the access is granted. FALSE if access is denied.
 */
function entity_page_access(EntityInterface $entity, $operation = 'view') {
  return $entity->access($operation);
}

/**
 * Generic access callback for create entity pages.
 *
 * Some entity types might have create access per bundle or something else.
 * In that case you have to create a custom access callback.
 *
 * @param string $entity_type
 *   The entity type.
 *
 * @return bool
 *   TRUE if the access is granted. FALSE if access is denied.
 */
function entity_page_create_access($entity_type) {
  $entity = drupal_container()->get('plugin.manager.entity')
    ->getStorageController($entity_type)
    ->create(array());
  return $entity->access('create');
}
