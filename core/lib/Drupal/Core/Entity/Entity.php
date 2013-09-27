<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Entity.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Plugin\DataType\EntityReferenceItem;
use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base entity class.
 *
 * Default implementation of EntityInterface.
 *
 * This class can be used as-is by simple entity types. Entity types requiring
 * special handling can extend the class.
 */
class Entity implements \IteratorAggregate, EntityInterface {

  /**
   * The language code of the entity's default language.
   *
   * @var string
   */
  public $langcode = Language::LANGCODE_NOT_SPECIFIED;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Boolean indicating whether the entity should be forced to be new.
   *
   * @var bool
   */
  protected $enforceIsNew;

  /**
   * Boolean indicating whether a new revision should be created on save.
   *
   * @var bool
   */
  protected $newRevision = FALSE;

  /**
   * Indicates whether this is the default revision.
   *
   * @var bool
   */
  protected $isDefaultRevision = TRUE;

  /**
   * Constructs an Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. If the entity type
   *   has bundles, the bundle key has to be specified.
   * @param string $entity_type
   *   The type of the entity to create.
   */
  public function __construct(array $values, $entity_type) {
    $this->entityType = $entity_type;
    // Set initial values.
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::uuid().
   */
  public function uuid() {
    return isset($this->uuid) ? $this->uuid : NULL;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isNew().
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || !$this->id();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isNewRevision().
   */
  public function isNewRevision() {
    $info = $this->entityInfo();
    return $this->newRevision || (!empty($info['entity_keys']['revision']) && !$this->getRevisionId());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::enforceIsNew().
   */
  public function enforceIsNew($value = TRUE) {
    $this->enforceIsNew = $value;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::setNewRevision().
   */
  public function setNewRevision($value = TRUE) {
    $this->newRevision = $value;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::entityType().
   */
  public function entityType() {
    return $this->entityType;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->entityType;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::label().
   */
  public function label($langcode = NULL) {
    $label = NULL;
    $entity_info = $this->entityInfo();
    if (isset($entity_info['label_callback']) && function_exists($entity_info['label_callback'])) {
      $label = $entity_info['label_callback']($this->entityType, $this, $langcode);
    }
    elseif (!empty($entity_info['entity_keys']['label']) && isset($this->{$entity_info['entity_keys']['label']})) {
      $label = $this->{$entity_info['entity_keys']['label']};
    }
    return $label;
  }

  /**
   * Returns the URI elements of the entity.
   *
   * URI templates might be set in the links array in an annotation, for
   * example:
   * @code
   * links = {
   *   "canonical" = "/node/{node}",
   *   "edit-form" = "/node/{node}/edit",
   *   "version-history" = "/node/{node}/revisions"
   * }
   * @endcode
   * or specified in a callback function set like:
   * @code
   * uri_callback = "contact_category_uri",
   * @endcode
   * If the path is not set in the links array, the uri_callback function is
   * used for setting the path. If this does not exist and the link relationship
   * type is canonical, the path is set using the default template:
   * entity/entityType/id.
   *
   * @param string $rel
   *   The link relationship type, for example: canonical or edit-form.
   *
   * @return array
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the entity, and matching the signature of url().
   */
  public function uri($rel = 'canonical') {
    $entity_info = $this->entityInfo();

    // The links array might contain URI templates set in annotations.
    $link_templates = isset($entity_info['links']) ? $entity_info['links'] : array();

    if (isset($link_templates[$rel])) {
      // If there is a template for the given relationship type, do the
      // placeholder replacement and use that as the path.
      $template = $link_templates[$rel];
      $replacements = $this->uriPlaceholderReplacements();
      $uri['path'] = str_replace(array_keys($replacements), array_values($replacements), $template);

      // @todo Remove this once http://drupal.org/node/1888424 is in and we can
      //   move the BC handling of / vs. no-/ to the generator.
      $uri['path'] = trim($uri['path'], '/');

      // Pass the entity data to url() so that alter functions do not need to
      // look up this entity again.
      $uri['options']['entity_type'] = $this->entityType;
      $uri['options']['entity'] = $this;
      return $uri;
    }

    $bundle = $this->bundle();
    // A bundle-specific callback takes precedence over the generic one for
    // the entity type.
    $bundles = entity_get_bundles($this->entityType);
    if (isset($bundles[$bundle]['uri_callback'])) {
      $uri_callback = $bundles[$bundle]['uri_callback'];
    }
    elseif (isset($entity_info['uri_callback'])) {
      $uri_callback = $entity_info['uri_callback'];
    }

    // Invoke the callback to get the URI. If there is no callback, use the
    // default URI format.
    if (isset($uri_callback) && function_exists($uri_callback)) {
      $uri = $uri_callback($this);
    }
    // Only use these defaults for a canonical link (that is, a link to self).
    // Other relationship types are not supported by this logic.
    elseif ($rel == 'canonical') {
      $uri = array(
        'path' => 'entity/' . $this->entityType . '/' . $this->id(),
      );
    }
    else {
      return array();
    }

    // Pass the entity data to url() so that alter functions do not need to
    // look up this entity again.
    $uri['options']['entity_type'] = $this->entityType;
    $uri['options']['entity'] = $this;
    return $uri;
  }

  /**
   * Returns an array of placeholders for this entity.
   *
   * Individual entity classes may override this method to add additional
   * placeholders if desired. If so, they should be sure to replicate the
   * property caching logic.
   *
   * @return array
   *   An array of URI placeholders.
   */
  protected function uriPlaceholderReplacements() {
    if (empty($this->uriPlaceholderReplacements)) {
      $this->uriPlaceholderReplacements = array(
        '{entityType}' => $this->entityType(),
        '{bundle}' => $this->bundle(),
        '{id}' => $this->id(),
        '{uuid}' => $this->uuid(),
        '{' . $this->entityType() . '}' => $this->id(),
      );
    }
    return $this->uriPlaceholderReplacements;
  }

  /**
   * {@inheritdoc}
   *
   * Returns a list of URI relationships supported by this entity.
   *
   * @return array
   *   An array of link relationships supported by this entity.
   */
  public function uriRelationships() {
    $entity_info = $this->entityInfo();
    return isset($entity_info['links']) ? array_keys($entity_info['links']) : array();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::get().
   */
  public function get($property_name, $langcode = NULL) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    $this->{$property_name} = $value;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getIterator().
   */
  public function getIterator() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    return new \ArrayIterator(array());
  }

  /**
   * Implements \Drupal\Core\TypedData\AccessibleInterface::access().
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'create') {
      return \Drupal::entityManager()
        ->getAccessController($this->entityType)
        ->createAccess($this->bundle(), $account);
    }
    return \Drupal::entityManager()
      ->getAccessController($this->entityType)
      ->access($this, $operation, Language::LANGCODE_DEFAULT, $account);
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::language().
   */
  public function language() {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    $language = language_load($this->langcode);
    if (!$language) {
      // Make sure we return a proper language object.
      $language = new Language(array('id' => Language::LANGCODE_NOT_SPECIFIED));
    }
    return $language;
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::getTranslation().
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getTranslation($langcode) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    return $this;
  }

  /**
   * Returns the languages the entity is translated to.
   *
   * @todo: Remove once all entity types implement the entity field API.
   *   This is deprecated by
   *   \Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages().
   */
  public function translations() {
    return $this->getTranslationLanguages(FALSE);
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages().
   */
  public function getTranslationLanguages($include_default = TRUE) {
    // @todo: Replace by EntityNG implementation once all entity types have been
    // converted to use the entity field API.
    $default_language = $this->language();
    $languages = array($default_language->id => $default_language);
    $entity_info = $this->entityInfo();

    if ($entity_info['fieldable']) {
      // Go through translatable properties and determine all languages for
      // which translated values are available.
      foreach (field_info_instances($this->entityType, $this->bundle()) as $field_name => $instance) {
        if (field_is_translatable($this->entityType, $instance->getField()) && isset($this->$field_name)) {
          foreach (array_filter($this->$field_name) as $langcode => $value)  {
            $languages[$langcode] = TRUE;
          }
        }
      }
      $languages = array_intersect_key(language_list(Language::STATE_ALL), $languages);
    }

    if (empty($include_default)) {
      unset($languages[$default_language->id]);
    }

    return $languages;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::save().
   */
  public function save() {
    return \Drupal::entityManager()->getStorageController($this->entityType)->save($this);
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::delete().
   */
  public function delete() {
    if (!$this->isNew()) {
      \Drupal::entityManager()->getStorageController($this->entityType)->delete(array($this->id() => $this));
    }
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_info = $this->entityInfo();
    $duplicate->{$entity_info['entity_keys']['id']} = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity_keys']['uuid'])) {
      // @todo Inject the UUID service into the Entity class once possible.
      $duplicate->{$entity_info['entity_keys']['uuid']} = \Drupal::service('uuid')->generate();
    }
    return $duplicate;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::entityInfo().
   */
  public function entityInfo() {
    return \Drupal::entityManager()->getDefinition($this->entityType());
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::getRevisionId().
   */
  public function getRevisionId() {
    return NULL;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isDefaultRevision().
   */
  public function isDefaultRevision($new_value = NULL) {
    $return = $this->isDefaultRevision;
    if (isset($new_value)) {
      $this->isDefaultRevision = (bool) $new_value;
    }
    return $return;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::getExportProperties().
   */
  public function getExportProperties() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    // @todo: This does not make much sense, so remove once TypedDataInterface
    // is removed. See https://drupal.org/node/2002138.
    if ($this->bundle() != $this->entityType()) {
      $type = 'entity:' . $this->entityType() . ':' . $this->bundle();
    }
    else {
      $type = 'entity:' . $this->entityType();
    }
    return array('type' => $type);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    // @todo: This does not make much sense, so remove once TypedDataInterface
    // is removed. See https://drupal.org/node/2002138.
    return $this->getPropertyValues();
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value, $notify = TRUE) {
    // @todo: This does not make much sense, so remove once TypedDataInterface
    // is removed. See https://drupal.org/node/2002138.
    $this->setPropertyValues($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // @todo: Add the typed data manager as proper dependency.
    return \Drupal::typedData()->getValidator()->validate($this);
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    foreach ($this->getProperties() as $property) {
      $property->applyDefaultValue(FALSE);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::onChange().
   */
  public function onChange($property_name) {
    // Nothing to do.
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getName().
   */
  public function getName() {
    return NULL;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getRoot().
   */
  public function getRoot() {
    return $this;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getPropertyPath().
   */
  public function getPropertyPath() {
    return '';
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getParent().
   */
  public function getParent() {
    return NULL;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setContext().
   */
  public function setContext($name = NULL, TypedDataInterface $parent = NULL) {
    // As entities are always the root of the tree of typed data, we do not need
    // to set any parent or name.
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::isTranslatable().
   */
  public function isTranslatable() {
    // @todo Inject the entity manager and retrieve bundle info from it.
    $bundles = entity_get_bundles($this->entityType);
    return !empty($bundles[$this->bundle()]['translatable']);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    $this->changed();
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageControllerInterface $storage_controller) {
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    foreach ($entities as $entity) {
      $entity->changed();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record) {
  }

  /**
   * {@inheritdoc}
   */
  public function getUntranslated() {
    return $this->getTranslation(Language::LANGCODE_DEFAULT);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation($langcode) {
    $translations = $this->getTranslationLanguages();
    return isset($translations[$langcode]);
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslation($langcode, array $values = array()) {
    // @todo Config entities do not support entity translation hence we need to
    //   move the TranslatableInterface implementation to EntityNG. See
    //   http://drupal.org/node/2004244
  }

  /**
   * {@inheritdoc}
   */
  public function removeTranslation($langcode) {
    // @todo Config entities do not support entity translation hence we need to
    //   move the TranslatableInterface implementation to EntityNG. See
    //   http://drupal.org/node/2004244
  }

  /**
   * {@inheritdoc}
   */
  public function initTranslation($langcode) {
    // @todo Config entities do not support entity translation hence we need to
    //   move the TranslatableInterface implementation to EntityNG. See
    //   http://drupal.org/node/2004244
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    $referenced_entities = array();

    // @todo Remove when all entities are converted to EntityNG.
    if (!$this->getPropertyDefinitions()) {
      return $referenced_entities;
    }

    // Gather a list of referenced entities.
    foreach ($this->getProperties() as $name => $definition) {
      $field_items = $this->get($name);
      foreach ($field_items as $offset => $field_item) {
        if ($field_item instanceof EntityReferenceItem && $entity = $field_item->entity) {
          $referenced_entities[] = $entity;
        }
      }
    }

    return $referenced_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function changed() {
    $referenced_entity_ids = array(
      $this->entityType() => array($this->id() => TRUE),
    );

    foreach ($this->referencedEntities() as $referenced_entity) {
      $referenced_entity_ids[$referenced_entity->entityType()][$referenced_entity->id()] = TRUE;
    }

    foreach ($referenced_entity_ids as $entity_type => $entity_ids) {
      if (\Drupal::entityManager()->hasController($entity_type, 'render')) {
        \Drupal::entityManager()->getRenderController($entity_type)->resetCache(array_keys($entity_ids));
      }
    }
  }

}
