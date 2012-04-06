<?php

/**
 * @file
 * Provides an interface and a base class for entities.
 */

/**
 * Defines a common interface for all entity objects.
 */
interface EntityInterface {

  /**
   * Constructs a new entity object.
   *
   * @param $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type);

  /**
   * Returns the entity identifier (the entity's machine name or numeric ID).
   *
   * @return
   *   The identifier of the entity, or NULL if the entity does not yet have
   *   an identifier.
   */
  public function id();

  /**
   * Returns whether the entity is new.
   *
   * Usually an entity is new if no ID exists for it yet. However, entities may
   * be enforced to be new with existing IDs too.
   *
   * @return
   *   TRUE if the entity is new, or FALSE if the entity has already been saved.
   *
   * @see EntityInterface::enforceIsNew()
   */
  public function isNew();

  /**
   * Enforces an entity to be new.
   *
   * Allows migrations to create entities with pre-defined IDs by forcing the
   * entity to be new before saving.
   *
   * @param bool $value
   *   (optional) Whether the entity should be forced to be new. Defaults to
   *   TRUE.
   *
   * @see EntityInterface::isNew()
   */
  public function enforceIsNew($value = TRUE);

  /**
   * Returns the type of the entity.
   *
   * @return
   *   The type of the entity.
   */
  public function entityType();

  /**
   * Returns the bundle of the entity.
   *
   * @return
   *   The bundle of the entity. Defaults to the entity type if the entity type
   *   does not make use of different bundles.
   */
  public function bundle();

  /**
   * Returns the label of the entity.
   *
   * @return
   *   The label of the entity, or NULL if there is no label defined.
   */
  public function label();

  /**
   * Returns the URI elements of the entity.
   *
   * @return
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the entity, and matching the signature of url(). NULL if the entity
   *   has no URI of its own.
   */
  public function uri();

  /**
   * Saves an entity permanently.
   *
   * @return
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function save();

  /**
   * Deletes an entity permanently.
   *
   * @throws EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function delete();

  /**
   * Creates a duplicate of the entity.
   *
   * @return EntityInterface
   *   A clone of the current entity with all identifiers unset, so saving
   *   it inserts a new entity into the storage system.
   */
  public function createDuplicate();

  /**
   * Returns the info of the type of the entity.
   *
   * @see entity_get_info()
   */
  public function entityInfo();
}

/**
 * Defines a base entity class.
 *
 * Default implementation of EntityInterface.
 *
 * This class can be used as-is by simple entity types. Entity types requiring
 * special handling can extend the class.
 */
class Entity implements EntityInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Information about the entity's type.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * The entity ID key.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The entity bundle key.
   *
   * @var string
   */
  protected $bundleKey;

  /**
   * Boolean indicating whether the entity should be forced to be new.
   *
   * @var bool
   */
  protected $enforceIsNew;

  /**
   * Constructs a new entity object.
   */
  public function __construct(array $values = array(), $entity_type) {
    $this->entityType = $entity_type;
    $this->setUp();
    // Set initial values.
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Sets up the object instance on construction or unserialization.
   */
  protected function setUp() {
    $this->entityInfo = entity_get_info($this->entityType);
    $this->idKey = $this->entityInfo['entity keys']['id'];
    $this->bundleKey = isset($this->entityInfo['entity keys']['bundle']) ? $this->entityInfo['entity keys']['bundle'] : NULL;
  }

  /**
   * Implements EntityInterface::id().
   */
  public function id() {
    return isset($this->{$this->idKey}) ? $this->{$this->idKey} : NULL;
  }

  /**
   * Implements EntityInterface::isNew().
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || empty($this->{$this->idKey});
  }

  /**
   * Implements EntityInterface::enforceIsNew().
   */
  public function enforceIsNew($value = TRUE) {
    $this->enforceIsNew = $value;
  }

  /**
   * Implements EntityInterface::entityType().
   */
  public function entityType() {
    return $this->entityType;
  }

  /**
   * Implements EntityInterface::bundle().
   */
  public function bundle() {
    return isset($this->bundleKey) ? $this->{$this->bundleKey} : $this->entityType;
  }

  /**
   * Implements EntityInterface::label().
   *
   * @see entity_label()
   */
  public function label() {
    $label = FALSE;
    if (isset($this->entityInfo['label callback']) && function_exists($this->entityInfo['label callback'])) {
      $label = $this->entityInfo['label callback']($this->entityType, $this);
    }
    elseif (!empty($this->entityInfo['entity keys']['label']) && isset($this->{$this->entityInfo['entity keys']['label']})) {
      $label = $this->{$this->entityInfo['entity keys']['label']};
    }
    return $label;
  }

  /**
   * Implements EntityInterface::uri().
   *
   * @see entity_uri()
   */
  public function uri() {
    $bundle = $this->bundle();
    // A bundle-specific callback takes precedence over the generic one for the
    // entity type.
    if (isset($this->entityInfo['bundles'][$bundle]['uri callback'])) {
      $uri_callback = $this->entityInfo['bundles'][$bundle]['uri callback'];
    }
    elseif (isset($this->entityInfo['uri callback'])) {
      $uri_callback = $this->entityInfo['uri callback'];
    }
    else {
      return NULL;
    }

    // Invoke the callback to get the URI. If there is no callback, return NULL.
    if (isset($uri_callback) && function_exists($uri_callback)) {
      $uri = $uri_callback($this);
      // Pass the entity data to url() so that alter functions do not need to
      // look up this entity again.
      $uri['options']['entity_type'] = $this->entityType;
      $uri['options']['entity'] = $this;
      return $uri;
    }
  }

  /**
   * Implements EntityInterface::save().
   */
  public function save() {
    return entity_get_controller($this->entityType)->save($this);
  }

  /**
   * Implements EntityInterface::delete().
   */
  public function delete() {
    if (!$this->isNew()) {
      entity_get_controller($this->entityType)->delete(array($this->id()));
    }
  }

  /**
   * Implements EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $duplicate->{$this->idKey} = NULL;
    return $duplicate;
  }

  /**
   * Implements EntityInterface::entityInfo().
   */
  public function entityInfo() {
    return $this->entityInfo;
  }

  /**
   * Serializes only what is necessary.
   *
   * See @link http://www.php.net/manual/language.oop5.magic.php#language.oop5.magic.sleep PHP Magic Methods @endlink.
   */
  public function __sleep() {
    $vars = get_object_vars($this);
    unset($vars['entityInfo'], $vars['idKey'], $vars['bundleKey']);
    // Also key the returned array with the variable names so the method may
    // be easily overridden and customized.
    return drupal_map_assoc(array_keys($vars));
  }

  /**
   * Invokes setUp() on unserialization.
   *
   * See @link http://www.php.net/manual/language.oop5.magic.php#language.oop5.magic.sleep PHP Magic Methods @endlink
   */
  public function __wakeup() {
    $this->setUp();
  }
}
