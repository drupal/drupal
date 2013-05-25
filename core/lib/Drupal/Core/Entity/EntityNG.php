<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityNG.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Component\Uuid\Uuid;
use ArrayIterator;
use InvalidArgumentException;

/**
 * Implements Entity Field API specific enhancements to the Entity class.
 *
 * Entity(..)NG classes are variants of the Entity(...) classes that implement
 * the next generation (NG) entity field API. They exist during conversion to
 * the new API only and changes will be merged into the respective original
 * classes once the conversion is complete.
 *
 * @todo: Once all entity types have been converted, merge improvements into the
 * Entity class and overhaul the EntityInterface.
 */
class EntityNG extends Entity {

  /**
   * Local cache holding the value of the bundle field.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The plain data values of the contained fields.
   *
   * This always holds the original, unchanged values of the entity. The values
   * are keyed by language code, whereas Language::LANGCODE_DEFAULT is used for
   * values in default language.
   *
   * @todo: Add methods for getting original fields and for determining
   * changes.
   * @todo: Provide a better way for defining default values.
   *
   * @var array
   */
  protected $values = array(
    'langcode' => array(Language::LANGCODE_DEFAULT => array(0 => array('value' => Language::LANGCODE_NOT_SPECIFIED))),
  );

  /**
   * The array of fields, each being an instance of FieldInterface.
   *
   * @var array
   */
  protected $fields = array();

  /**
   * An instance of the backward compatibility decorator.
   *
   * @var EntityBCDecorator
   */
  protected $bcEntity;

  /**
   * Local cache for field definitions.
   *
   * @see EntityNG::getPropertyDefinitions()
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE) {
    $this->entityType = $entity_type;
    $this->bundle = $bundle ? $bundle : $this->entityType;
    foreach ($values as $key => $value) {
      // If the key matches an existing property set the value to the property
      // to ensure non converted properties have the correct value.
      if (property_exists($this, $key) && isset($value[Language::LANGCODE_DEFAULT])) {
        $this->$key = $value[Language::LANGCODE_DEFAULT];
      }
      $this->values[$key] = $value;
    }
    $this->init();
  }

  /**
   * Gets the typed data type of the entity.
   *
   * @return string
   */
  public function getType() {
    return $this->entityType;
  }

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    // We unset all defined properties, so magic getters apply.
    unset($this->langcode);
  }

  /**
   * Magic __wakeup() implementation.
   */
  public function __wakeup() {
    $this->init();
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->id->value;
  }

  /**
   * Implements \Drupal\Core\Entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->bundle;
  }

  /**
   * Overrides Entity::uuid().
   */
  public function uuid() {
    return $this->get('uuid')->value;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::get().
   */
  public function get($property_name) {
    // Values in default language are always stored using the
    // Language::LANGCODE_DEFAULT constant.
    if (!isset($this->fields[$property_name][Language::LANGCODE_DEFAULT])) {
      return $this->getTranslatedField($property_name, Language::LANGCODE_DEFAULT);
    }
    return $this->fields[$property_name][Language::LANGCODE_DEFAULT];
  }

  /**
   * Gets a translated field.
   *
   * @return \Drupal\Core\Entity\Field\FieldInterface
   */
  protected function getTranslatedField($property_name, $langcode) {
    // Populate $this->fields to speed-up further look-ups and to keep track of
    // fields objects, possibly holding changes to field values.
    if (!isset($this->fields[$property_name][$langcode])) {
      $definition = $this->getPropertyDefinition($property_name);
      if (!$definition) {
        throw new InvalidArgumentException('Field ' . check_plain($property_name) . ' is unknown.');
      }
      // Non-translatable fields are always stored with
      // Language::LANGCODE_DEFAULT as key.
      if ($langcode != Language::LANGCODE_DEFAULT && empty($definition['translatable'])) {
        $this->fields[$property_name][$langcode] = $this->getTranslatedField($property_name, Language::LANGCODE_DEFAULT);
      }
      else {
        $value = NULL;
        if (isset($this->values[$property_name][$langcode])) {
          $value = $this->values[$property_name][$langcode];
        }
        // @todo: Make entities implement the TypedDataInterface.
        $this->fields[$property_name][$langcode] = typed_data()->getPropertyInstance($this, $property_name, $value);
      }
    }
    return $this->fields[$property_name][$langcode];
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::set().
   */
  public function set($property_name, $value, $notify = TRUE) {
    $this->get($property_name)->setValue($value, FALSE);
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getProperties().
   */
  public function getProperties($include_computed = FALSE) {
    $properties = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if ($include_computed || empty($definition['computed'])) {
        $properties[$name] = $this->get($name);
      }
    }
    return $properties;
  }

  /**
   * Implements \IteratorAggregate::getIterator().
   */
  public function getIterator() {
    return new ArrayIterator($this->getProperties());
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinition().
   */
  public function getPropertyDefinition($name) {
    if (!isset($this->fieldDefinitions)) {
      $this->getPropertyDefinitions();
    }
    if (isset($this->fieldDefinitions[$name])) {
      return $this->fieldDefinitions[$name];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyDefinitions().
   */
  public function getPropertyDefinitions() {
    if (!isset($this->fieldDefinitions)) {
      $this->fieldDefinitions = \Drupal::entityManager()->getStorageController($this->entityType)->getFieldDefinitions(array(
        'EntityType' => $this->entityType,
        'Bundle' => $this->bundle,
      ));
    }
    return $this->fieldDefinitions;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::getPropertyValues().
   */
  public function getPropertyValues() {
    $values = array();
    foreach ($this->getProperties() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values;
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::setPropertyValues().
   */
  public function setPropertyValues($values) {
    foreach ($values as $name => $value) {
      $this->get($name)->setValue($value);
    }
  }

  /**
   * Implements \Drupal\Core\TypedData\ComplexDataInterface::isEmpty().
   */
  public function isEmpty() {
    if (!$this->isNew()) {
      return FALSE;
    }
    foreach ($this->getProperties() as $property) {
      if ($property->getValue() !== NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::language().
   */
  public function language() {
    // Get the language code if the property exists.
    if ($this->getPropertyDefinition('langcode')) {
      $language = $this->get('langcode')->language;
    }
    if (empty($language)) {
      // Make sure we return a proper language object.
      $language = new Language(array('langcode' => Language::LANGCODE_NOT_SPECIFIED));
    }
    return $language;
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::getTranslation().
   *
   * @return \Drupal\Core\Entity\Field\Type\EntityTranslation
   */
  public function getTranslation($langcode, $strict = TRUE) {
    // If the default language is Language::LANGCODE_NOT_SPECIFIED, the entity is not
    // translatable, so we use Language::LANGCODE_DEFAULT.
    if ($langcode == Language::LANGCODE_DEFAULT || in_array($this->language()->langcode, array(Language::LANGCODE_NOT_SPECIFIED, $langcode))) {
      // No translation needed, return the entity.
      return $this;
    }
    // Check whether the language code is valid, thus is of an available
    // language.
    $languages = language_list(Language::STATE_ALL);
    if (!isset($languages[$langcode])) {
      throw new InvalidArgumentException("Unable to get translation for the invalid language '$langcode'.");
    }
    $fields = array();
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      // Load only translatable properties in strict mode.
      if (!empty($definition['translatable']) || !$strict) {
        $fields[$name] = $this->getTranslatedField($name, $langcode);
      }
    }
    // @todo: Add a way to get the definition of a translation to the
    // TranslatableInterface and leverage TypeDataManager::getPropertyInstance
    // also.
    $translation_definition = array(
      'type' => 'entity_translation',
      'constraints' => array(
        'entity type' => $this->entityType(),
        'bundle' => $this->bundle(),
      ),
    );
    $translation = typed_data()->create($translation_definition, $fields);
    $translation->setStrictMode($strict);
    $translation->setContext('@' . $langcode, $this);
    return $translation;
  }

  /**
   * Implements \Drupal\Core\TypedData\TranslatableInterface::getTranslationLanguages().
   */
  public function getTranslationLanguages($include_default = TRUE) {
    $translations = array();
    // Build an array with the translation langcodes set as keys. Empty
    // translations should not be included and must be skipped.
    foreach ($this->getProperties() as $name => $property) {
      foreach ($this->fields[$name] as $langcode => $field) {
        if (!$field->isEmpty()) {
          $translations[$langcode] = TRUE;
        }
        if (isset($this->values[$name])) {
          foreach ($this->values[$name] as $langcode => $values) {
            // If a value is there but the field object is empty, it has been
            // unset, so we need to skip the field also.
            if ($values && !(isset($this->fields[$name][$langcode]) && $this->fields[$name][$langcode]->isEmpty())) {
              $translations[$langcode] = TRUE;
            }
          }
        }
      }
    }
    // We include the default language code instead of the
    // Language::LANGCODE_DEFAULT constant.
    unset($translations[Language::LANGCODE_DEFAULT]);

    if ($include_default) {
      $translations[$this->language()->langcode] = TRUE;
    }
    // Now load language objects based upon translation langcodes.
    return array_intersect_key(language_list(Language::STATE_ALL), $translations);
  }

  /**
   * Overrides Entity::translations().
   *
   * @todo: Remove once Entity::translations() gets removed.
   */
  public function translations() {
    return $this->getTranslationLanguages(FALSE);
  }

  /**
   * Overrides Entity::getBCEntity().
   */
  public function getBCEntity() {
    if (!isset($this->bcEntity)) {
      // Initialize field definitions so that we can pass them by reference.
      $this->getPropertyDefinitions();
      $this->bcEntity = new EntityBCDecorator($this, $this->fieldDefinitions);
    }
    return $this->bcEntity;
  }

  /**
   * Updates the original values with the interim changes.
   */
  public function updateOriginalValues() {
    if (!$this->fields) {
      return;
    }
    foreach ($this->getPropertyDefinitions() as $name => $definition) {
      if (empty($definition['computed']) && !empty($this->fields[$name])) {
        foreach ($this->fields[$name] as $langcode => $field) {
          $field->filterEmptyValues();
          $this->values[$name][$langcode] = $field->getValue();
        }
      }
    }
  }

  /**
   * Implements the magic method for setting object properties.
   *
   * Uses default language always.
   * For compatibility mode to work this must return a reference.
   */
  public function &__get($name) {
    // If this is an entity field, handle it accordingly. We first check whether
    // a field object has been already created. If not, we create one.
    if (isset($this->fields[$name][Language::LANGCODE_DEFAULT])) {
      return $this->fields[$name][Language::LANGCODE_DEFAULT];
    }
    // Inline getPropertyDefinition() to speed up things.
    if (!isset($this->fieldDefinitions)) {
      $this->getPropertyDefinitions();
    }
    if (isset($this->fieldDefinitions[$name])) {
      $return = $this->getTranslatedField($name, Language::LANGCODE_DEFAULT);
      return $return;
    }
    // Allow the EntityBCDecorator to directly access the values and fields.
    // @todo: Remove once the EntityBCDecorator gets removed.
    if ($name == 'values' || $name == 'fields') {
      return $this->$name;
    }
    // Else directly read/write plain values. That way, non-field entity
    // properties can always be accessed directly.
    if (!isset($this->values[$name])) {
      $this->values[$name] = NULL;
    }
    return $this->values[$name];
  }

  /**
   * Implements the magic method for setting object properties.
   *
   * Uses default language always.
   */
  public function __set($name, $value) {
    // Support setting values via property objects.
    if ($value instanceof TypedDataInterface && !$value instanceof EntityInterface) {
      $value = $value->getValue();
    }
    // If this is an entity field, handle it accordingly. We first check whether
    // a field object has been already created. If not, we create one.
    if (isset($this->fields[$name][Language::LANGCODE_DEFAULT])) {
      $this->fields[$name][Language::LANGCODE_DEFAULT]->setValue($value);
    }
    elseif ($this->getPropertyDefinition($name)) {
      $this->getTranslatedField($name, Language::LANGCODE_DEFAULT)->setValue($value);
    }
    // Else directly read/write plain values. That way, fields not yet converted
    // to the entity field API can always be directly accessed.
    else {
      $this->values[$name] = $value;
    }
  }

  /**
   * Implements the magic method for isset().
   */
  public function __isset($name) {
    if ($this->getPropertyDefinition($name)) {
      return $this->get($name)->getValue() !== NULL;
    }
    else {
      return isset($this->values[$name]);
    }
  }

  /**
   * Implements the magic method for unset.
   */
  public function __unset($name) {
    if ($this->getPropertyDefinition($name)) {
      $this->get($name)->setValue(NULL);
    }
    else {
      unset($this->values[$name]);
    }
  }

  /**
   * Overrides Entity::createDuplicate().
   */
  public function createDuplicate() {
    $duplicate = clone $this;
    $entity_info = $this->entityInfo();
    $duplicate->{$entity_info['entity_keys']['id']}->value = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity_keys']['uuid'])) {
      $uuid = new Uuid();
      $duplicate->{$entity_info['entity_keys']['uuid']}->value = $uuid->generate();
    }

    // Check whether the entity type supports revisions and initialize it if so.
    if (!empty($entity_info['entity_keys']['revision'])) {
      $duplicate->{$entity_info['entity_keys']['revision']}->value = NULL;
    }

    return $duplicate;
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    $this->bcEntity = NULL;

    foreach ($this->fields as $name => $properties) {
      foreach ($properties as $langcode => $property) {
        $this->fields[$name][$langcode] = clone $property;
        $this->fields[$name][$langcode]->setContext($name, $this);
      }
    }
  }

  /**
   * Overrides Entity::label() to access the label field with the new API.
   */
  public function label($langcode = NULL) {
    $label = NULL;
    $entity_info = $this->entityInfo();
    if (isset($entity_info['label_callback']) && function_exists($entity_info['label_callback'])) {
      $label = $entity_info['label_callback']($this->entityType, $this, $langcode);
    }
    elseif (!empty($entity_info['entity_keys']['label']) && isset($this->{$entity_info['entity_keys']['label']})) {
      $label = $this->{$entity_info['entity_keys']['label']}->value;
    }
    return $label;
  }
}
