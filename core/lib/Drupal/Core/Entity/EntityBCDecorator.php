<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityBCDecorator.
 */

namespace Drupal\Core\Entity;

use IteratorAggregate;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ContextAwareInterface;

/**
 * Implements a decorator providing backwards compatible entity field access.
 *
 * Allows using entities converted to the new Entity Field API with the Drupal 7
 * way of accessing fields or properties.
 *
 * Note: We access the protected 'values' and 'fields' properties of the entity
 * via the magic getter - which returns them by reference for us. We do so, as
 * providing references to this arrays makes $entity->values and $entity->fields
 * to references itself as well, which is problematic during __clone() (this is
 * something that would not be easy to fix as an unset() on the variable is
 * problematic with the magic getter/setter then).
 */
class EntityBCDecorator implements IteratorAggregate, EntityInterface {

  /**
   * The EntityInterface object being decorated.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $decorated;

  /**
   * Constructs a Drupal\Core\Entity\EntityCompatibilityDecorator object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $decorated
   *   The decorated entity.
   */
  function __construct(EntityNG $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * Overrides Entity::getOriginalEntity().
   */
  public function getOriginalEntity() {
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
    // Make sure $this->decorated->values reflects the latest values.
    if (!empty($this->decorated->fields[$name])) {
      foreach ($this->decorated->fields[$name] as $langcode => $field) {
        $this->decorated->values[$name][$langcode] = $field->getValue();
      }
      // Values might be changed by reference, so remove the field object to
      // avoid them becoming out of sync.
      unset($this->decorated->fields[$name]);
    }
    // Allow accessing field values in entity default languages other than
    // LANGUAGE_DEFAULT by mapping the values to LANGUAGE_DEFAULT.
    $langcode = $this->decorated->language()->langcode;
    if ($langcode != LANGUAGE_DEFAULT && isset($this->decorated->values[$name][LANGUAGE_DEFAULT]) && !isset($this->decorated->values[$name][$langcode])) {
      $this->decorated->values[$name][$langcode] = &$this->decorated->values[$name][LANGUAGE_DEFAULT];
    }

    if (!isset($this->decorated->values[$name])) {
      $this->decorated->values[$name] = NULL;
    }
    return $this->decorated->values[$name];
  }

  /**
   * Implements the magic method for setting object properties.
   *
   * Directly writes to the plain field values, as done by Drupal 7.
   */
  public function __set($name, $value) {
    if (is_array($value) && $definition = $this->decorated->getPropertyDefinition($name)) {
      // If field API sets a value with a langcode in entity language, move it
      // to LANGUAGE_DEFAULT.
      foreach ($value as $langcode => $data) {
        if ($langcode != LANGUAGE_DEFAULT && $langcode == $this->decorated->language()->langcode) {
          $value[LANGUAGE_DEFAULT] = $data;
          unset($value[$langcode]);
        }
      }
    }
    $this->decorated->values[$name] = $value;
    unset($this->decorated->fields[$name]);
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
    $value = &$this->__get($name);
    $value = array();
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function access($operation = 'view', \Drupal\user\Plugin\Core\Entity\User $account = NULL) {
    return $this->decorated->access($account);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function get($property_name) {
    return $this->decorated->get($property_name);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function set($property_name, $value) {
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
  public function uri() {
    return $this->decorated->uri();
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
  public function getTranslation($langcode, $strict = TRUE) {
    return $this->decorated->getTranslation($langcode, $strict);
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
  public function setContext($name = NULL, ContextAwareInterface $parent = NULL) {
    $this->decorated->setContext($name, $parent);
  }

  /**
   * Forwards the call to the decorated entity.
   */
  public function getExportProperties() {
    $this->decorated->getExportProperties();
  }
}
