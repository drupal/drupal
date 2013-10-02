<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityStorageControllerBase.
 */

namespace Drupal\Core\Entity;

/**
 * A base entity storage controller class.
 */
abstract class EntityStorageControllerBase implements EntityStorageControllerInterface, EntityControllerInterface {

  /**
   * Static cache of entities.
   *
   * @var array
   */
  protected $entityCache = array();

  /**
   * Whether this entity type should use the static cache.
   *
   * Set by entity info.
   *
   * @var boolean
   */
  protected $cache;

  /**
   * Entity type for this controller instance.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Array of information about the entity.
   *
   * @var array
   *
   * @see entity_get_info()
   */
  protected $entityInfo;

  /**
   * Additional arguments to pass to hook_TYPE_load().
   *
   * Set before calling Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   *
   * @var array
   */
  protected $hookLoadArguments = array();

  /**
   * Name of the entity's ID field in the entity database table.
   *
   * @var string
   */
  protected $idKey;

  /**
   * Name of entity's UUID database table field, if it supports UUIDs.
   *
   * Has the value FALSE if this entity does not use UUIDs.
   *
   * @var string
   */
  protected $uuidKey;

  /**
   * Constructs an EntityStorageControllerBase instance.
   *
   * @param string $entity_type
   *   The entity type for which the instance is created.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   */
  public function __construct($entity_type, $entity_info) {
    $this->entityType = $entity_type;
    $this->entityInfo = $entity_info;
    // Check if the entity type supports static caching of loaded entities.
    $this->cache = !empty($this->entityInfo['static_cache']);
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id) {
    $this->resetCache(array($id));
    return $this->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    if ($this->cache && isset($ids)) {
      foreach ($ids as $id) {
        unset($this->entityCache[$id]);
      }
    }
    else {
      $this->entityCache = array();
    }
  }

  /**
   * Gets entities from the static cache.
   *
   * @param $ids
   *   If not empty, return entities that match these IDs.
   *
   * @return
   *   Array of entities from the entity cache.
   */
  protected function cacheGet($ids) {
    $entities = array();
    // Load any available entities from the internal cache.
    if ($this->cache && !empty($this->entityCache)) {
      $entities += array_intersect_key($this->entityCache, array_flip($ids));
    }
    return $entities;
  }

  /**
   * Stores entities in the static entity cache.
   *
   * @param $entities
   *   Entities to store in the cache.
   */
  protected function cacheSet($entities) {
    if ($this->cache) {
      $this->entityCache += $entities;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeFieldMethod($method, EntityInterface $entity) {
    // Only act on content entities.
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $translation = $entity->getTranslation($langcode);
      foreach ($translation as $field) {
        $field->$method();
      }
    }
  }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param string $hook
   *   One of 'presave', 'insert', 'update', 'predelete', 'delete', or
   *  'revision_delete'.
   * @param \Drupal\Core\Entity\EntityInterface  $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    // Invoke the hook.
    module_invoke_all($this->entityType . '_' . $hook, $entity);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $entity, $this->entityType);
  }

  /**
   * Checks translation statuses and invoke the related hooks if needed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  protected function invokeTranslationHooks(EntityInterface $entity) {
    // Only act on content entities.
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }
    $translations = $entity->getTranslationLanguages(FALSE);
    $original_translations = $entity->original->getTranslationLanguages(FALSE);
    $all_translations = array_keys($translations + $original_translations);

    // Notify modules of translation insertion/deletion.
    foreach ($all_translations as $langcode) {
      if (isset($translations[$langcode]) && !isset($original_translations[$langcode])) {
        $this->invokeHook('translation_insert', $entity->getTranslation($langcode));
      }
      elseif (!isset($translations[$langcode]) && isset($original_translations[$langcode])) {
        $this->invokeHook('translation_delete', $entity->getTranslation($langcode));
      }
    }
  }

}
