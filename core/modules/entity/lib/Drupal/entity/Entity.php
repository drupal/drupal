<?php

/**
 * @file
 * Definition of Drupal\entity\Entity.
 */

namespace Drupal\entity;

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
   * The language code of the entity's default language.
   *
   * @var string
   */
  public $langcode = LANGUAGE_NOT_SPECIFIED;

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
   * Indicates whether this is the current revision.
   *
   * @var bool
   */
  public $isCurrentRevision = TRUE;

  /**
   * Constructs a new entity object.
   */
  public function __construct(array $values = array(), $entity_type) {
    $this->entityType = $entity_type;
    // Set initial values.
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Implements EntityInterface::id().
   */
  public function id() {
    return isset($this->id) ? $this->id : NULL;
  }

  /**
   * Implements EntityInterface::isNew().
   */
  public function isNew() {
    return !empty($this->enforceIsNew) || !$this->id();
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
    return $this->entityType;
  }

  /**
   * Implements EntityInterface::label().
   */
  public function label($langcode = NULL) {
    $label = FALSE;
    $entity_info = $this->entityInfo();
    if (isset($entity_info['label callback']) && function_exists($entity_info['label callback'])) {
      $label = $entity_info['label callback']($this->entityType, $this, $langcode);
    }
    elseif (!empty($entity_info['entity keys']['label']) && isset($this->{$entity_info['entity keys']['label']})) {
      $label = $this->{$entity_info['entity keys']['label']};
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
    $entity_info = $this->entityInfo();
    if (isset($entity_info['bundles'][$bundle]['uri callback'])) {
      $uri_callback = $entity_info['bundles'][$bundle]['uri callback'];
    }
    elseif (isset($entity_info['uri callback'])) {
      $uri_callback = $entity_info['uri callback'];
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
   * Implements EntityInterface::language().
   */
  public function language() {
    // @todo: Check for language.module instead, once Field API language
    // handling depends upon it too.
    return module_exists('locale') ? language_load($this->langcode) : FALSE;
  }

  /**
   * Implements EntityInterface::translations().
   */
  public function translations() {
    $languages = array();
    $entity_info = $this->entityInfo();
    if ($entity_info['fieldable'] && ($default_language = $this->language())) {
      // Go through translatable properties and determine all languages for
      // which translated values are available.
      foreach (field_info_instances($this->entityType, $this->bundle()) as $field_name => $instance) {
        $field = field_info_field($field_name);
        if (field_is_translatable($this->entityType, $field) && isset($this->$field_name)) {
          foreach ($this->$field_name as $langcode => $value)  {
            $languages[$langcode] = TRUE;
          }
        }
      }
      // Remove the default language from the translations.
      unset($languages[$default_language->langcode]);
      $languages = array_intersect_key(language_list(), $languages);
    }
    return $languages;
  }

  /**
   * Implements EntityInterface::get().
   */
  public function get($property_name, $langcode = NULL) {
    // Handle fields.
    $entity_info = $this->entityInfo();
    if ($entity_info['fieldable'] && field_info_instance($this->entityType, $property_name, $this->bundle())) {
      $field = field_info_field($property_name);
      $langcode = $this->getFieldLangcode($field, $langcode);
      return isset($this->{$property_name}[$langcode]) ? $this->{$property_name}[$langcode] : NULL;
    }
    else {
      // Handle properties being not fields.
      // @todo: Add support for translatable properties being not fields.
      return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
    }
  }

  /**
   * Implements EntityInterface::set().
   */
  public function set($property_name, $value, $langcode = NULL) {
    // Handle fields.
    $entity_info = $this->entityInfo();
    if ($entity_info['fieldable'] && field_info_instance($this->entityType, $property_name, $this->bundle())) {
      $field = field_info_field($property_name);
      $langcode = $this->getFieldLangcode($field, $langcode);
      $this->{$property_name}[$langcode] = $value;
    }
    else {
      // Handle properties being not fields.
      // @todo: Add support for translatable properties being not fields.
      $this->{$property_name} = $value;
    }
  }

  /**
   * Determines the language code to use for accessing a field value in a certain language.
   */
  protected function getFieldLangcode($field, $langcode = NULL) {
    // Only apply the given langcode if the entity is language-specific.
    // Otherwise translatable fields are handled as non-translatable fields.
    if (field_is_translatable($this->entityType, $field) && ($default_language = $this->language()) && !language_is_locked($this->langcode)) {
      // For translatable fields the values in default language are stored using
      // the language code of the default language.
      return isset($langcode) ? $langcode : $default_language->langcode;
    }
    else {
      // If there is a langcode defined for this field, just return it. Otherwise
      // return LANGUAGE_NOT_SPECIFIED.
      return (isset($this->langcode) ? $this->langcode : LANGUAGE_NOT_SPECIFIED);
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
    $duplicate->id = NULL;
    return $duplicate;
  }

  /**
   * Implements EntityInterface::entityInfo().
   */
  public function entityInfo() {
    return entity_get_info($this->entityType);
  }

  /**
   * Implements Drupal\entity\EntityInterface::getRevisionId().
   */
  public function getRevisionId() {
    return NULL;
  }

  /**
   * Implements Drupal\entity\EntityInterface::isCurrentRevision().
   */
  public function isCurrentRevision() {
    return $this->isCurrentRevision;
  }

}
