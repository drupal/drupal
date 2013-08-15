<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityBCDecorator.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use IteratorAggregate;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides backwards compatible (BC) access to entity fields.
 *
 * Allows using entities converted to the new Entity Field API with the previous
 * way of accessing fields or properties. For example, via the backwards
 * compatible (BC) decorator you can do:
 * @code
 *   $node->title = $value;
 *   $node->body[LANGUAGE_NONE][0]['value'] = $value;
 * @endcode
 * Without the BC decorator the same assignment would have to look like this:
 * @code
 *   $node->title->value = $value;
 *   $node->body->value = $value;
 * @endcode
 * Without the BC decorator the language always default to the entity language,
 * whereas a specific translation can be access via the getTranslation() method.
 *
 * The BC decorator should be only used during conversion to the new entity
 * field API, such that existing code can be converted iteratively. Any new code
 * should directly use the new entity field API and avoid using the
 * EntityBCDecorator, if possible.
 *
 * @todo: Remove once everything is converted to use the new entity field API.
 */
class EntityBCDecorator implements IteratorAggregate, EntityInterface {

  /**
   * The EntityInterface object being decorated.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $decorated;

  /**
   * Local cache for field definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * Constructs a Drupal\Core\Entity\EntityCompatibilityDecorator object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $decorated
   *   The decorated entity.
   * @param array &$definitions
   *   An array of field definitions.
   */
  function __construct(EntityNG $decorated, array &$definitions) {
    $this->decorated = $decorated;
    $this->definitions = &$definitions;
  }

  /**
   * Overrides Entity::getNGEntity().
   */
  public function getNGEntity() {
    return $this->decorated;
  }

  /**
   * Overrides Entity::getBCEntity().
   */
  public function getBCEntity() {
    return $this;
  }

  /**
   * Implements the magic method for getting object properties.
   *
   * Directly accesses the plain field values, as done in Drupal 7.
   */
  public function &__get($name) {
    // Directly return the original property.
    if ($name == 'original') {
      return $this->decorated->values[$name];
    }

    // We access the protected 'values' and 'fields' properties of the decorated
    // entity via the magic getter - which returns them by reference for us. We
    // do so, as providing references to these arrays would make $entity->values
    // and $entity->fields reference themselves, which is problematic during
    // __clone() (this is something we cannot work-a-round easily as an unset()
    // on the variable is problematic in conjunction with the magic
    // getter/setter).

    if (!empty($this->decorated->fields[$name])) {
      // Any field value set via the new Entity Field API will be stored inside
      // the field objects managed by the entity, thus we need to ensure
      // $this->decorated->values reflects the latest values first.
      foreach ($this->decorated->fields[$name] as $langcode => $field) {
        // Only set if it's not empty, otherwise there can be ghost values.
        if (!$field->isEmpty()) {
          $this->decorated->values[$name][$langcode] = $field->getValue(TRUE);
        }
      }
      // The returned values might be changed by reference, so we need to remove
      // the field object to avoid the field object and the value getting out of
      // sync. That way, the next field object instantiated by EntityNG will
      // receive the possibly updated value.
      unset($this->decorated->fields[$name]);
    }
    // When accessing values for entity properties that have been converted to
    // an entity field, provide direct access to the plain value. This makes it
    // possible to use the BC-decorator with properties; e.g., $node->title.
    if (isset($this->definitions[$name]) && empty($this->definitions[$name]['configurable'])) {
      if (!isset($this->decorated->values[$name][Language::LANGCODE_DEFAULT])) {
        $this->decorated->values[$name][Language::LANGCODE_DEFAULT][0]['value'] = NULL;
      }
      if (is_array($this->decorated->values[$name][Language::LANGCODE_DEFAULT])) {
        // We need to ensure the key doesn't matter. Mostly it's 'value' but
        // e.g. EntityReferenceItem uses target_id - so just take the first one.
        if (isset($this->decorated->values[$name][Language::LANGCODE_DEFAULT][0]) && is_array($this->decorated->values[$name][Language::LANGCODE_DEFAULT][0])) {
          return $this->decorated->values[$name][Language::LANGCODE_DEFAULT][0][current(array_keys($this->decorated->values[$name][Language::LANGCODE_DEFAULT][0]))];
        }
      }
      return $this->decorated->values[$name][Language::LANGCODE_DEFAULT];
    }
    else {
      // Allow accessing field values in an entity default language other than
      // Language::LANGCODE_DEFAULT by mapping the values to
      // Language::LANGCODE_DEFAULT. This is necessary as EntityNG always keys
      // default language values with Language::LANGCODE_DEFAULT while field API
      // expects them to be keyed by langcode.
      $langcode = $this->decorated->getUntranslated()->language()->id;
      if ($langcode != Language::LANGCODE_DEFAULT && isset($this->decorated->values[$name]) && is_array($this->decorated->values[$name])) {
        if (isset($this->decorated->values[$name][Language::LANGCODE_DEFAULT]) && !isset($this->decorated->values[$name][$langcode])) {
          $this->decorated->values[$name][$langcode] = &$this->decorated->values[$name][Language::LANGCODE_DEFAULT];
        }
      }
      if (!isset($this->decorated->values[$name])) {
        $this->decorated->values[$name] = NULL;
      }
      return $this->decorated->values[$name];
    }
  }

  /**
   * Implements the magic method for setting object properties.
   *
   * Directly writes to the plain field values, as done by Drupal 7.
   */
  public function __set($name, $value) {
    $defined = isset($this->definitions[$name]);
    // When updating values for entity properties that have been converted to
    // an entity field, directly write to the plain value. This makes it
    // possible to use the BC-decorator with properties; e.g., $node->title.
    if ($defined && empty($this->definitions[$name]['configurable'])) {
      $this->decorated->values[$name][Language::LANGCODE_DEFAULT] = $value;
    }
    else {
      if ($defined && is_array($value)) {
        // If field API sets a value with a langcode in entity language, move it
        // to Language::LANGCODE_DEFAULT.
        // This is necessary as EntityNG always keys default language values
        // with Language::LANGCODE_DEFAULT while field API expects them to be
        // keyed by langcode.
        foreach ($value as $langcode => $data) {
          if ($langcode != Language::LANGCODE_DEFAULT && $langcode == $this->decorated->language()->id) {
            $value[Language::LANGCODE_DEFAULT] = $data;
            unset($value[$langcode]);
          }
        }
      }
      $this->decorated->values[$name] = $value;
    }
    // Remove the field object to avoid the field object and the value getting
    // out of sync. That way, the next field object instantiated by EntityNG
    // will hold the updated value.
    unset($this->decorated->fields[$name]);
    $this->decorated->onChange($name);
  }

  /**
   * Implements the magic method for isset().
   */
  public function __isset($name) {
    $value = $this->__get($name);
    return isset($value);
  }

  /**
   * Implements the magic method for unset().
   */
  public function __unset($name) {
    // Set the value to NULL.
    $value = &$this->__get($name);
    $value = NULL;
  }

  /**
   * Implements the magic method for clone().
   */
  function __clone() {
    $this->decorated = clone $this->decorated;
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function uriRelationships() {
    return $this->decorated->uriRelationships();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    return $this->decorated->access($operation, $account);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function get($property_name) {
    // Ensure this works with not yet defined fields.
    if (!isset($this->definitions[$property_name])) {
      return $this->__get($property_name);
    }
    return $this->decorated->get($property_name);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function set($property_name, $value, $notify = TRUE) {
    // Ensure this works with not yet defined fields.
    if (!isset($this->definitions[$property_name])) {
      return $this->__set($property_name, $value);
    }
    return $this->decorated->set($property_name, $value);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getProperties($include_computed = FALSE) {
    return $this->decorated->getProperties($include_computed);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getPropertyValues() {
    return $this->decorated->getPropertyValues();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function setPropertyValues($values) {
    return $this->decorated->setPropertyValues($values);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getPropertyDefinition($name) {
    return $this->decorated->getPropertyDefinition($name);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getPropertyDefinitions() {
    return $this->decorated->getPropertyDefinitions();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function isEmpty() {
    return $this->decorated->isEmpty();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getIterator() {
    return $this->decorated->getIterator();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function id() {
    return $this->decorated->id();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function uuid() {
    return $this->decorated->uuid();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function isNew() {
    return $this->decorated->isNew();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function isNewRevision() {
    return $this->decorated->isNewRevision();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function setNewRevision($value = TRUE) {
    return $this->decorated->setNewRevision($value);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function enforceIsNew($value = TRUE) {
    return $this->decorated->enforceIsNew($value);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function entityType() {
    return $this->decorated->entityType();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function bundle() {
    return $this->decorated->bundle();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function label($langcode = NULL) {
    return $this->decorated->label($langcode);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function uri($rel = 'canonical') {
    return $this->decorated->uri($rel);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function save() {
    return $this->decorated->save();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function delete() {
    return $this->decorated->delete();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function createDuplicate() {
    return $this->decorated->createDuplicate();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function entityInfo() {
    return $this->decorated->entityInfo();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getRevisionId() {
    return $this->decorated->getRevisionId();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function isDefaultRevision($new_value = NULL) {
    return $this->decorated->isDefaultRevision($new_value);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function language() {
    return $this->decorated->language();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getTranslationLanguages($include_default = TRUE) {
    return $this->decorated->getTranslationLanguages($include_default);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getTranslation($langcode) {
    return $this->decorated->getTranslation($langcode);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getDefinition() {
    return $this->decorated->getDefinition();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getValue() {
    return $this->decorated->getValue();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function setValue($value, $notify = TRUE) {
    return $this->decorated->setValue($value, $notify);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getString() {
    return $this->decorated->getString();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getConstraints() {
    return $this->decorated->getConstraints();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function validate() {
    return $this->decorated->validate();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getName() {
    return $this->decorated->getName();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getRoot() {
    return $this->decorated->getRoot();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getPropertyPath() {
    return $this->decorated->getPropertyPath();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getParent() {
    return $this->decorated->getParent();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function setContext($name = NULL, TypedDataInterface $parent = NULL) {
    $this->decorated->setContext($name, $parent);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getExportProperties() {
    $this->decorated->getExportProperties();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function onChange($property_name) {
    $this->decorated->onChange($property_name);
  }


  /**
   * Forwards the call to the decorated entity.
   */
  public function applyDefaultValue($notify = TRUE) {
    return $this->decorated->applyDefaultValue($notify);
  }

  /*
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $this->decorated->preSave($storage_controller);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record) {
    $this->decorated->preSave($storage_controller, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    $this->decorated->postSave($storage_controller, $update);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
  }

  public function postCreate(EntityStorageControllerInterface $storage_controller) {
    $this->decorated->postCreate($storage_controller);
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
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageControllerInterface $storage_controller, array $entities) {
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function isTranslatable() {
    return $this->decorated->isTranslatable();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getUntranslated() {
    return $this->decorated->getUntranslated();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function hasTranslation($langcode) {
    return $this->decorated->hasTranslation($langcode);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function addTranslation($langcode, array $values = array()) {
    return $this->decorated->addTranslation($langcode, $values);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function removeTranslation($langcode) {
    $this->decorated->removeTranslation($langcode);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function initTranslation($langcode) {
    $this->decorated->initTranslation($langcode);
  }

}
