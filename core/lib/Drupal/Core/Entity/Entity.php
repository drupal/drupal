<?php

/**
 * @file
 * Definition of Drupal\Core\Entity\Entity.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Language\Language;

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
   * Indicates whether this is the default revision.
   *
   * @var bool
   */
  protected $isDefaultRevision = TRUE;

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
   * Implements EntityInterface::uuid().
   */
  public function uuid() {
    return isset($this->uuid) ? $this->uuid : NULL;
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
    $label = NULL;
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
    return !empty($this->langcode) ? language_load($this->langcode) : new Language(array('langcode' => LANGUAGE_NOT_SPECIFIED));
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
      // Prevent getFieldLangcode() from throwing an exception in case a
      // $langcode has been passed and it is invalid for the field.
      $langcode = $this->getFieldLangcode($field, $langcode, FALSE);
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
      // Throws an exception if the $langcode is invalid.
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
   * Determines the language code for accessing a field value.
   *
   * The effective language code to be used for a field varies:
   * - If the entity is language-specific and the requested field is
   *   translatable, the entity's language code should be used to access the
   *   field value when no language is explicitly provided.
   * - If the entity is not language-specific, LANGUAGE_NOT_SPECIFIED should be
   *   used to access all field values.
   * - If a field's values are non-translatable (shared among all language
   *   versions of an entity), LANGUAGE_NOT_SPECIFIED should be used to access
   *   them.
   *
   * There cannot be valid field values if a field is not translatable and the
   * requested langcode is not LANGUAGE_NOT_SPECIFIED. Therefore, this function
   * throws an exception in that case (or returns NULL when $strict is FALSE).
   *
   * @param string $field
   *   Field the language code is being determined for.
   * @param string|null $langcode
   *   (optional) The language code attempting to be applied to the field.
   *   Defaults to the entity language.
   * @param bool $strict
   *   (optional) When $strict is TRUE, an exception is thrown if the field is
   *   not translatable and the langcode is not LANGUAGE_NOT_SPECIFIED. When
   *   $strict is FALSE, NULL is returned and no exception is thrown. For
   *   example, EntityInterface::set() passes TRUE, since it must not set field
   *   values for invalid langcodes. EntityInterface::get() passes FALSE to
   *   determine whether any field values exist for a specific langcode.
   *   Defaults to TRUE.
   *
   * @return string|null
   *   The langcode if appropriate, LANGUAGE_NOT_SPECIFIED for non-translatable
   *   fields, or NULL when an invalid langcode was used in non-strict mode.
   *
   * @throws \InvalidArgumentException
   *   Thrown in case a $langcode other than LANGUAGE_NOT_SPECIFIED is passed
   *   for a non-translatable field and $strict is TRUE.
   */
  protected function getFieldLangcode($field, $langcode = NULL, $strict = TRUE) {
    // Only apply the given langcode if the entity is language-specific.
    // Otherwise translatable fields are handled as non-translatable fields.
    if (field_is_translatable($this->entityType, $field) && ($default_language = $this->language()) && !language_is_locked($this->langcode)) {
      // For translatable fields the values in default language are stored using
      // the language code of the default language.
      return isset($langcode) ? $langcode : $default_language->langcode;
    }
    else {
      // The field is not translatable, but the caller requested a specific
      // langcode that does not exist.
      if (isset($langcode) && $langcode !== LANGUAGE_NOT_SPECIFIED) {
        if ($strict) {
          throw new \InvalidArgumentException(format_string('Unable to resolve @langcode for non-translatable field @field_name. Use langcode LANGUAGE_NOT_SPECIFIED instead.', array(
            '@field_name' => $field['field_name'],
            '@langcode' => $langcode,
          )));
        }
        else {
          return NULL;
        }
      }
      // The field is not translatable and no $langcode was specified.
      return LANGUAGE_NOT_SPECIFIED;
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
    $entity_info = $this->entityInfo();
    $this->{$entity_info['entity keys']['id']} = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity keys']['uuid'])) {
      $uuid = new Uuid();
      $duplicate->{$entity_info['entity keys']['uuid']} = $uuid->generate();
    }
    return $duplicate;
  }

  /**
   * Implements EntityInterface::entityInfo().
   */
  public function entityInfo() {
    return entity_get_info($this->entityType);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::getRevisionId().
   */
  public function getRevisionId() {
    return NULL;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::isDefaultRevision().
   */
  public function isDefaultRevision($new_value = NULL) {
    $return = $this->isDefaultRevision;
    if (isset($new_value)) {
      $this->isDefaultRevision = (bool) $new_value;
    }
    return $return;
  }

}
