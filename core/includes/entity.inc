<?php

/**
 * @file
 * Entity API for handling entities like nodes or users.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;

/**
 * Resets the cached information about entity types.
 */
function entity_info_cache_clear() {
  drupal_static_reset('entity_get_view_modes');
  // Clear all languages.
  \Drupal::entityManager()->clearCachedDefinitions();
  \Drupal::entityManager()->clearCachedFieldDefinitions();
}

/**
 * Clears the entity render cache for all entity types.
 */
function entity_render_cache_clear() {
  $entity_manager = Drupal::entityManager();
  foreach ($entity_manager->getDefinitions() as $entity_type => $info) {
    if ($entity_manager->hasController($entity_type, 'view_builder')) {
      $entity_manager->getViewBuilder($entity_type)->resetCache();
    }
  }
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
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getBundleInfo()
 * @see \Drupal\Core\Entity\EntityManagerInterface::getAllBundleInfo()
 */
function entity_get_bundles($entity_type = NULL) {
  if (isset($entity_type)) {
    return \Drupal::entityManager()->getBundleInfo($entity_type);
  }
  else {
    return \Drupal::entityManager()->getAllBundleInfo();
  }
}

/**
 * Notifies modules about an operation that was performed on a entity bundle.
 *
 * @param string $hook
 *   One of 'create', 'rename' or 'delete'.
 * @param string $entity_type
 *   The entity type to which the bundle is bound.
 * @param string $bundle
 *   The name of the bundle on which the operation was performed.
 * @param string|null $bundle_new
 *   The new name of the bundle in case of a 'rename' operation. Defaults to
 *   NULL.
 */
function entity_invoke_bundle_hook($hook, $entity_type, $bundle, $bundle_new = NULL) {
  entity_info_cache_clear();

  // Notify the entity storage controller.
  $method = 'onBundle' . ucfirst($hook);
  $storage_controller = \Drupal::entityManager()->getStorageController($entity_type);
  if (method_exists($storage_controller, $method)) {
    $storage_controller->$method($bundle, $bundle_new);
  }
  // Invoke hook_entity_bundle_*() hooks.
  \Drupal::moduleHandler()->invokeAll('entity_bundle_' . $hook, array($entity_type, $bundle, $bundle_new));
}

/**
 * Returns the entity form mode info.
 *
 * @param string|null $entity_type
 *   The entity type whose form mode info should be returned, or NULL for all
 *   form mode info. Defaults to NULL.
 *
 * @return array
 *   The form mode info for a specific entity type, or all entity types.
 */
function entity_get_form_modes($entity_type = NULL) {
  $form_modes = &drupal_static(__FUNCTION__);
  if (!$form_modes) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->id;
    if ($cache = \Drupal::cache()->get("entity_form_mode_info:$langcode")) {
      $form_modes = $cache->data;
    }
    else {
      $form_modes = array();
      foreach (entity_load_multiple('form_mode') as $form_mode) {
        list($form_mode_entity_type, $form_mode_name) = explode('.', $form_mode->id(), 2);
        $form_modes[$form_mode_entity_type][$form_mode_name] = (array) $form_mode;
      }
      \Drupal::moduleHandler()->alter('entity_form_mode_info', $form_modes);
      \Drupal::cache()->set("entity_form_mode_info:$langcode", $form_modes, Cache::PERMANENT, array('entity_types' => TRUE));
    }
  }

  if (empty($entity_type)) {
    return $form_modes;
  }
  elseif (isset($form_modes[$entity_type])) {
    return $form_modes[$entity_type];
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
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->id;
    if ($cache = \Drupal::cache()->get("entity_view_mode_info:$langcode")) {
      $view_modes = $cache->data;
    }
    else {
      $view_modes = array();
      foreach (entity_load_multiple('view_mode') as $view_mode) {
        list($view_mode_entity_type, $view_mode_name) = explode('.', $view_mode->id(), 2);
        $view_modes[$view_mode_entity_type][$view_mode_name] = (array) $view_mode;
      }
      \Drupal::moduleHandler()->alter('entity_view_mode_info', $view_modes);
      \Drupal::cache()->set("entity_view_mode_info:$langcode", $view_modes, Cache::PERMANENT, array('entity_types' => TRUE));
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
 * @param mixed $id
 *   The id of the entity to load.
 * @param bool $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   The entity object, or NULL if there is no entity with the given id.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface
 * @see \Drupal\Core\Entity\EntityStorageControllerInterface
 * @see \Drupal\Core\Entity\DatabaseStorageController
 * @see \Drupal\Core\Entity\Query\QueryInterface
 */
function entity_load($entity_type, $id, $reset = FALSE) {
  $controller = \Drupal::entityManager()->getStorageController($entity_type);
  if ($reset) {
    $controller->resetCache(array($id));
  }
  return $controller->load($id);
}

/**
 * Loads an entity from the database.
 *
 * @param string $entity_type
 *   The entity type to load, e.g. node or user.
 * @param int $revision_id
 *   The id of the entity to load.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   The entity object, or FALSE if there is no entity with the given revision
 *   id.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface
 * @see \Drupal\Core\Entity\EntityStorageControllerInterface
 * @see \Drupal\Core\Entity\DatabaseStorageController
 */
function entity_revision_load($entity_type, $revision_id) {
  return \Drupal::entityManager()
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
  \Drupal::entityManager()
    ->getStorageController($entity_type)
    ->deleteRevision($revision_id);
}

/**
 * Loads an entity by UUID.
 *
 * Note that some entity types may not support UUIDs.
 *
 * @param string $entity_type_id
 *   The entity type to load; e.g., 'node' or 'user'.
 * @param string $uuid
 *   The UUID of the entity to load.
 * @param bool $reset
 *   Whether to reset the internal cache for the requested entity type.
 *
 * @return EntityInterface|FALSE
 *   The entity object, or FALSE if there is no entity with the given UUID.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 *   Thrown in case the requested entity type does not support UUIDs.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface
 */
function entity_load_by_uuid($entity_type_id, $uuid, $reset = FALSE) {
  $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);

  if (!$uuid_key = $entity_type->getKey('uuid')) {
    throw new EntityStorageException("Entity type $entity_type_id does not support UUIDs.");
  }

  $controller = \Drupal::entityManager()->getStorageController($entity_type_id);
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
 * "controllers['storage']" key in the entity plugin annotation. These classes
 * can either implement the Drupal\Core\Entity\EntityStorageControllerInterface
 * interface, or, most commonly, extend the
 * Drupal\Core\Entity\DatabaseStorageController class.
 * See Drupal\node\Entity\Node and Drupal\node\NodeStorageController
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
 * @see \Drupal\Core\Entity\EntityManagerInterface
 * @see \Drupal\Core\Entity\EntityStorageControllerInterface
 * @see \Drupal\Core\Entity\DatabaseStorageController
 * @see \Drupal\Core\Entity\Query\QueryInterface
 */
function entity_load_multiple($entity_type, array $ids = NULL, $reset = FALSE) {
  $controller = \Drupal::entityManager()->getStorageController($entity_type);
  if ($reset) {
    $controller->resetCache($ids);
  }
  return $controller->loadMultiple($ids);
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
  return \Drupal::entityManager()
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
  return \Drupal::entityManager()
    ->getStorageController($entity_type)
    ->loadUnchanged($id);
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
  $controller = \Drupal::entityManager()->getStorageController($entity_type);
  $entities = $controller->loadMultiple($ids);
  $controller->delete($entities);
}

/**
 * Constructs a new entity object, without permanently saving it.
 *
 * @param string $entity_type
 *   The type of the entity.
 * @param array $values
 *   (optional) An array of values to set, keyed by property name. If the
 *   entity type has bundles, the bundle key has to be specified.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   A new entity object.
 */
function entity_create($entity_type, array $values = array()) {
  return \Drupal::entityManager()
    ->getStorageController($entity_type)
    ->create($values);
}

/**
 * Gets the entity controller class for an entity type.
 *
 * @return \Drupal\Core\Entity\EntityStorageControllerInterface
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getStorageController().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::entityManager()->getStorageController().
 */
function entity_get_controller($entity_type) {
  return \Drupal::entityManager()
    ->getStorageController($entity_type);
}

/**
 * Returns the label of an entity.
 *
 * This is a wrapper for Drupal\Core\Entity\EntityInterface::label(). This function
 * should only be used as a callback, e.g. for menu title callbacks.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity for which to generate the label.
 * @param $langcode
 *   (optional) The language code of the language that should be used for
 *   getting the label. If set to NULL, the entity's default language is
 *   used.
 *
 * @return
 *   The label of the entity, or NULL if there is no label defined.
 *
 * @see \Drupal\Core\Entity\EntityInterface::label()
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
 * @see \Drupal\Core\Entity\EntityManagerInterface::getAccessController().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::entityManager()->getAccessController().
 */
function entity_access_controller($entity_type) {
  return \Drupal::entityManager()
    ->getAccessController($entity_type);
}

/**
 * Returns an entity list controller for a given entity type.
 *
 * @param string $entity_type
 *   The type of the entity.
 *
 * @return \Drupal\Core\Entity\EntityListControllerInterface
 *   An entity list controller.
 *
 * @see \Drupal\Core\Entity\EntityManagerInterface::getListController().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::entityManager()->getListController().
 */
function entity_list_controller($entity_type) {
  return \Drupal::entityManager()
    ->getListController($entity_type);
}

/**
 * Returns the render array for an entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity to be rendered.
 * @param string $view_mode
 *   The view mode that should be used to display the entity.
 * @param string $langcode
 *   (optional) For which language the entity should be rendered, defaults to
 *   the current content language.
 * @param bool $reset
 *   (optional) Whether to reset the render cache for the requested entity.
 *   Defaults to FALSE.
 *
 * @return array
 *   A render array for the entity.
 */
function entity_view(EntityInterface $entity, $view_mode, $langcode = NULL, $reset = FALSE) {
  $render_controller = \Drupal::entityManager()->getViewBuilder($entity->getEntityTypeId());
  if ($reset) {
    $render_controller->resetCache(array($entity->id()));
  }
  return $render_controller->view($entity, $view_mode, $langcode);
}

/**
 * Returns the render array for the provided entities.
 *
 * @param \Drupal\Core\Entity\EntityInterface[] $entities
 *   The entities to be rendered, must be of the same type.
 * @param string $view_mode
 *   The view mode that should be used to display the entity.
 * @param string $langcode
 *   (optional) For which language the entity should be rendered, defaults to
 *   the current content language.
 * @param bool $reset
 *   (optional) Whether to reset the render cache for the requested entities.
 *   Defaults to FALSE.
 *
 * @return array
 *   A render array for the entities, indexed by the same keys as the
 *   entities array passed in $entities.
 */
function entity_view_multiple(array $entities, $view_mode, $langcode = NULL, $reset = FALSE) {
  $render_controller = \Drupal::entityManager()->getViewBuilder(reset($entities)->getEntityTypeId());
  if ($reset) {
    $render_controller->resetCache(array_keys($entities));
  }
  return $render_controller->viewMultiple($entities, $view_mode, $langcode);
}

/**
 * Returns the entity view display associated to a bundle and view mode.
 *
 * Use this function when assigning suggested display options for a component
 * in a given view mode. Note that they will only be actually used at render
 * time if the view mode itself is configured to use dedicated display settings
 * for the bundle; if not, the 'default' display is used instead.
 *
 * The function reads the entity view display from the current configuration, or
 * returns a ready-to-use empty one if configuration entry exists yet for this
 * bundle and view mode. This streamlines manipulation of display objects by
 * always returning a consistent object that reflects the current state of the
 * configuration.
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
 * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
 *   The entity view display associated to the view mode.
 */
function entity_get_display($entity_type, $bundle, $view_mode) {
  // Try loading the display from configuration.
  $display = entity_load('entity_view_display', $entity_type . '.' . $bundle . '.' . $view_mode);

  // If not found, create a fresh display object. We do not preemptively create
  // new entity_view_display configuration entries for each existing entity type
  // and bundle whenever a new view mode becomes available. Instead,
  // configuration entries are only created when a display object is explicitly
  // configured and saved.
  if (!$display) {
    $display = entity_create('entity_view_display', array(
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => $view_mode,
      'status' => TRUE,
    ));
  }

  return $display;
}

/**
 * Returns the entity form display associated to a bundle and form mode.
 *
 * The function reads the entity form display object from the current
 * configuration, or returns a ready-to-use empty one if no configuration entry
 * exists yet for this bundle and form mode. This streamlines manipulation of
 * entity form displays by always returning a consistent object that reflects
 * the current state of the configuration.
 *
 * Example usage:
 * - Set the 'body' field to be displayed with the 'text_textarea_with_summary'
 *   widget and the 'field_image' field to be hidden on article nodes in the
 *  'default' form mode.
 * @code
 * entity_get_form_display('node', 'article', 'default')
 *   ->setComponent('body', array(
 *     'type' => 'text_textarea_with_summary',
 *     'weight' => 1,
 *   ))
 *   ->setComponent('field_image', array(
 *     'type' => 'hidden',
 *   ))
 *   ->save();
 * @endcode
 *
 * @param string $entity_type
 *   The entity type.
 * @param string $bundle
 *   The bundle.
 * @param string $form_mode
 *   The form mode.
 *
 * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
 *   The entity form display associated to the given form mode.
 */
function entity_get_form_display($entity_type, $bundle, $form_mode) {
  // Try loading the entity from configuration.
  $entity_form_display = entity_load('entity_form_display', $entity_type . '.' . $bundle . '.' . $form_mode);

  // If not found, create a fresh entity object. We do not preemptively create
  // new entity form display configuration entries for each existing entity type
  // and bundle whenever a new form mode becomes available. Instead,
  // configuration entries are only created when an entity form display is
  // explicitly configured and saved.
  if (!$entity_form_display) {
    $entity_form_display = entity_create('entity_form_display', array(
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => $form_mode,
      'status' => TRUE,
    ));
  }

  return $entity_form_display;
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
 * @param string $entity_type
 *   The entity type.
 * @param string $bundle
 *   (optional) The bundle of the entity. Required if the entity supports
 *   bundles, defaults to NULL otherwise.
 *
 * @return bool
 *   TRUE if the access is granted. FALSE if access is denied.
 */
function entity_page_create_access($entity_type, $bundle = NULL) {
  return \Drupal::entityManager()->getAccessController($entity_type)->createAccess($bundle);
}
