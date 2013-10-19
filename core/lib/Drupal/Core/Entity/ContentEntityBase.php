<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Implements Entity Field API specific enhancements to the Entity class.
 */
abstract class ContentEntityBase extends Entity implements \IteratorAggregate, ContentEntityInterface {

  /**
   * Status code indentifying a removed translation.
   */
  const TRANSLATION_REMOVED = 0;

  /**
   * Status code indentifying an existing translation.
   */
  const TRANSLATION_EXISTING = 1;

  /**
   * Status code indentifying a newly created translation.
   */
  const TRANSLATION_CREATED = 2;

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
  protected $values = array();

  /**
   * The array of fields, each being an instance of FieldItemListInterface.
   *
   * @var array
   */
  protected $fields = array();

  /**
   * Local cache for the entity language.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * Local cache for the available language objects.
   *
   * @var array
   */
  protected $languages;

  /**
   * Local cache for field definitions.
   *
   * @see ContentEntityBase::getPropertyDefinitions()
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * Local cache for URI placeholder substitution values.
   *
   * @var array
   */
  protected $uriPlaceholderReplacements;

  /**
   * Language code identifying the entity active language.
   *
   * This is the language field accessors will use to determine which field
   * values manipulate.
   *
   * @var string
   */
  protected $activeLangcode = Language::LANGCODE_DEFAULT;

  /**
   * An array of entity translation metadata.
   *
   * An associative array keyed by translation language code. Every value is an
   * array containg the translation status and the translation object, if it has
   * already been instantiated.
   *
   * @var array
   */
  protected $translations = array();

  /**
   * A flag indicating whether a translation object is being initialized.
   *
   * @var bool
   */
  protected $translationInitialize = FALSE;

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
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    $this->entityType = $entity_type;
    $this->bundle = $bundle ? $bundle : $this->entityType;
    $this->languages = language_list(Language::STATE_ALL);

    foreach ($values as $key => $value) {
      // If the key matches an existing property set the value to the property
      // to ensure non converted properties have the correct value.
      if (property_exists($this, $key) && isset($value[Language::LANGCODE_DEFAULT])) {
        $this->$key = $value[Language::LANGCODE_DEFAULT];
      }
      $this->values[$key] = $value;
    }

    // Initialize translations. Ensure we have at least an entry for the entity
    // original language.
    $data = array('status' => static::TRANSLATION_EXISTING);
    $this->translations[Language::LANGCODE_DEFAULT] = $data;
    if ($translations) {
      $default_langcode = $this->language()->id;
      foreach ($translations as $langcode) {
        if ($langcode != $default_langcode && $langcode != Language::LANGCODE_DEFAULT) {
          $this->translations[$langcode] = $data;
        }
      }
    }

    $this->init();
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($value = TRUE) {
    $this->newRevision = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    $info = $this->entityInfo();
    return $this->newRevision || (!empty($info['entity_keys']['revision']) && !$this->getRevisionId());
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision($new_value = NULL) {
    $return = $this->isDefaultRevision;
    if (isset($new_value)) {
      $this->isDefaultRevision = (bool) $new_value;
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    // @todo Inject the entity manager and retrieve bundle info from it.
    $bundles = entity_get_bundles($this->entityType);
    return !empty($bundles[$this->bundle()]['translatable']);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record) {
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getConstraints() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoot() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($name = NULL, TypedDataInterface $parent = NULL) {
    // As entities are always the root of the tree of typed data, we do not need
    // to set any parent or name.
  }

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    // We unset all defined properties, so magic getters apply.
    unset($this->langcode);
  }

  /**
   * Clear entity translation object cache to remove stale references.
   */
  protected function clearTranslationCache() {
    foreach ($this->translations as &$translation) {
      unset($translation['entity']);
    }
  }

  /**
   * Magic __wakeup() implementation.
   */
  public function __wakeup() {
    $this->init();
    // @todo This should be done before serializing the entity, but we would
    //   need to provide the full list of data to be serialized. See the
    //   dedicated issue at https://drupal.org/node/2027795.
    $this->clearTranslationCache();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id->value;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function hasField($field_name) {
    return (bool) $this->getPropertyDefinition($field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    if (!isset($this->fields[$property_name][$this->activeLangcode])) {
      return $this->getTranslatedField($property_name, $this->activeLangcode);
    }
    return $this->fields[$property_name][$this->activeLangcode];
  }

  /**
   * Gets a translated field.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   */
  protected function getTranslatedField($property_name, $langcode) {
    if ($this->translations[$this->activeLangcode]['status'] == static::TRANSLATION_REMOVED) {
      $message = 'The entity object refers to a removed translation (@langcode) and cannot be manipulated.';
      throw new \InvalidArgumentException(format_string($message, array('@langcode' => $this->activeLangcode)));
    }
    // Populate $this->fields to speed-up further look-ups and to keep track of
    // fields objects, possibly holding changes to field values.
    if (!isset($this->fields[$property_name][$langcode])) {
      $definition = $this->getPropertyDefinition($property_name);
      if (!$definition) {
        throw new \InvalidArgumentException('Field ' . check_plain($property_name) . ' is unknown.');
      }
      // Non-translatable fields are always stored with
      // Language::LANGCODE_DEFAULT as key.
      if ($langcode != Language::LANGCODE_DEFAULT && empty($definition['translatable'])) {
        if (!isset($this->fields[$property_name][Language::LANGCODE_DEFAULT])) {
          $this->fields[$property_name][Language::LANGCODE_DEFAULT] = $this->getTranslatedField($property_name, Language::LANGCODE_DEFAULT);
        }
        $this->fields[$property_name][$langcode] = &$this->fields[$property_name][Language::LANGCODE_DEFAULT];
      }
      else {
        $value = NULL;
        if (isset($this->values[$property_name][$langcode])) {
          $value = $this->values[$property_name][$langcode];
        }
        $field = \Drupal::typedData()->getPropertyInstance($this, $property_name, $value);
        $field->setLangcode($langcode);
        $this->fields[$property_name][$langcode] = $field;
      }
    }
    return $this->fields[$property_name][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    $this->get($property_name)->setValue($value, FALSE);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->getProperties());
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->fieldDefinitions)) {
      $bundle = $this->bundle != $this->entityType ? $this->bundle : NULL;
      $this->fieldDefinitions = \Drupal::entityManager()->getFieldDefinitions($this->entityType, $bundle);
    }
    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyValues() {
    $values = array();
    foreach ($this->getProperties() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyValues($values) {
    foreach ($values as $name => $value) {
      $this->get($name)->setValue($value);
    }
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'create') {
      return \Drupal::entityManager()
        ->getAccessController($this->entityType)
        ->createAccess($this->bundle(), $account);
    }
    return \Drupal::entityManager()
      ->getAccessController($this->entityType)
      ->access($this, $operation, $this->activeLangcode, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    if ($this->activeLangcode != Language::LANGCODE_DEFAULT) {
      if (!isset($this->languages[$this->activeLangcode])) {
        $this->languages += language_list(Language::STATE_ALL);
      }
      return $this->languages[$this->activeLangcode];
    }
    else {
      return $this->language ?: $this->getDefaultLanguage();
    }
  }

  /**
   * Returns the entity original language.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object.
   */
  protected function getDefaultLanguage() {
    // Keep a local cache of the language object and clear it if the langcode
    // gets changed, see ContentEntityBase::onChange().
    if (!isset($this->language)) {
      // Get the language code if the property exists.
      if ($this->getPropertyDefinition('langcode') && ($item = $this->get('langcode')) && isset($item->language)) {
        $this->language = $item->language;
      }
      if (empty($this->language)) {
        // Make sure we return a proper language object.
        $this->language = new Language(array('id' => Language::LANGCODE_NOT_SPECIFIED, 'locked' => TRUE));
      }
    }
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name) {
    if ($property_name == 'langcode') {
      // Avoid using unset as this unnecessarily triggers magic methods later
      // on.
      $this->language = NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getTranslation($langcode) {
    // Ensure we always use the default language code when dealing with the
    // original entity language.
    if ($langcode != Language::LANGCODE_DEFAULT) {
      $default_language = $this->language ?: $this->getDefaultLanguage();
      if ($langcode == $default_language->id) {
        $langcode = Language::LANGCODE_DEFAULT;
      }
    }

    // Populate entity translation object cache so it will be available for all
    // translation objects.
    if ($langcode == $this->activeLangcode) {
      $this->translations[$langcode]['entity'] = $this;
    }

    // If we already have a translation object for the specified language we can
    // just return it.
    if (isset($this->translations[$langcode]['entity'])) {
      $translation = $this->translations[$langcode]['entity'];
    }
    else {
      if (isset($this->translations[$langcode])) {
        $translation = $this->initializeTranslation($langcode);
        $this->translations[$langcode]['entity'] = $translation;
      }
      else {
        // If we were given a valid language and there is no translation for it,
        // we return a new one.
        $languages = language_list(Language::STATE_ALL);
        if (isset($languages[$langcode])) {
          // If the entity or the requested language  is not a configured
          // language, we fall back to the entity itself, since in this case it
          // cannot have translations.
          $translation = empty($this->getDefaultLanguage()->locked) && empty($languages[$langcode]->locked) ? $this->addTranslation($langcode) : $this;
        }
      }
    }

    if (empty($translation)) {
      $message = 'Invalid translation language (@langcode) specified.';
      throw new \InvalidArgumentException(format_string($message, array('@langcode' => $langcode)));
    }

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getUntranslated() {
    $langcode = Language::LANGCODE_DEFAULT;
    return isset($this->translations[$langcode]['entity']) ? $this->translations[$langcode]['entity'] : $this->getTranslation($langcode);
  }

  /**
   * Instantiates a translation object for an existing translation.
   *
   * The translated entity will be a clone of the current entity with the
   * specified $langcode. All translations share the same field data structures
   * to ensure that all of them deal with fresh data.
   *
   * @param string $langcode
   *   The language code for the requested translation.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The translation object. The content properties of the translation object
   *   are stored as references to the main entity.
   */
  protected function initializeTranslation($langcode) {
    // If the requested translation is valid, clone it with the current language
    // as the active language. The $translationInitialize flag triggers a
    // shallow (non-recursive) clone.
    $this->translationInitialize = TRUE;
    $translation = clone $this;
    $this->translationInitialize = FALSE;

    $translation->activeLangcode = $langcode;

    // Ensure that changes to fields, values and translations are propagated
    // to all the translation objects.
    // @todo Consider converting these to ArrayObject.
    $translation->values = &$this->values;
    $translation->fields = &$this->fields;
    $translation->translations = &$this->translations;
    $translation->translationInitialize = FALSE;

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation($langcode) {
    $default_language = $this->language ?: $this->getDefaultLanguage();
    if ($langcode == $default_language->id) {
      $langcode = Language::LANGCODE_DEFAULT;
    }
    return !empty($this->translations[$langcode]['status']);
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslation($langcode, array $values = array()) {
    $languages = language_list(Language::STATE_ALL);
    if (!isset($languages[$langcode]) || $this->hasTranslation($langcode)) {
      $message = 'Invalid translation language (@langcode) specified.';
      throw new \InvalidArgumentException(format_string($message, array('@langcode' => $langcode)));
    }

    // Instantiate a new empty entity so default values will be populated in the
    // specified language.
    $info = $this->entityInfo();
    $default_values = array($info['entity_keys']['bundle'] => $this->bundle, 'langcode' => $langcode);
    $entity = \Drupal::entityManager()
      ->getStorageController($this->entityType())
      ->create($default_values);

    foreach ($entity as $name => $field) {
      if (!isset($values[$name]) && !$field->isEmpty()) {
        $values[$name] = $field->value;
      }
    }

    $this->translations[$langcode]['status'] = static::TRANSLATION_CREATED;
    $translation = $this->getTranslation($langcode);
    $definitions = $translation->getPropertyDefinitions();

    foreach ($values as $name => $value) {
      if (isset($definitions[$name]) && !empty($definitions[$name]['translatable'])) {
        $translation->$name = $value;
      }
    }

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function removeTranslation($langcode) {
    if (isset($this->translations[$langcode]) && $langcode != Language::LANGCODE_DEFAULT && $langcode != $this->getDefaultLanguage()->id) {
      foreach ($this->getPropertyDefinitions() as $name => $definition) {
        if (!empty($definition['translatable'])) {
          unset($this->values[$name][$langcode]);
          unset($this->fields[$name][$langcode]);
        }
      }
      $this->translations[$langcode]['status'] = static::TRANSLATION_REMOVED;
    }
    else {
      $message = 'The specified translation (@langcode) cannot be removed.';
      throw new \InvalidArgumentException(format_string($message, array('@langcode' => $langcode)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initTranslation($langcode) {
    if ($langcode != Language::LANGCODE_DEFAULT && $langcode != $this->getDefaultLanguage()->id) {
      $this->translations[$langcode]['status'] = static::TRANSLATION_EXISTING;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationLanguages($include_default = TRUE) {
    $translations = array_filter($this->translations, function($translation) { return $translation['status']; });
    unset($translations[Language::LANGCODE_DEFAULT]);

    if ($include_default) {
      $langcode = $this->getDefaultLanguage()->id;
      $translations[$langcode] = TRUE;
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
   * Implements the magic method for getting object properties.
   *
   * @todo: A lot of code still uses non-fields (e.g. $entity->content in render
   *   controllers) by reference. Clean that up.
   */
  public function &__get($name) {
    // If this is an entity field, handle it accordingly. We first check whether
    // a field object has been already created. If not, we create one.
    if (isset($this->fields[$name][$this->activeLangcode])) {
      return $this->fields[$name][$this->activeLangcode];
    }
    // Inline getPropertyDefinition() to speed up things.
    if (!isset($this->fieldDefinitions)) {
      $this->getPropertyDefinitions();
    }
    if (isset($this->fieldDefinitions[$name])) {
      $return = $this->getTranslatedField($name, $this->activeLangcode);
      return $return;
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
    if (isset($this->fields[$name][$this->activeLangcode])) {
      $this->fields[$name][$this->activeLangcode]->setValue($value);
    }
    elseif ($this->hasField($name)) {
      $this->getTranslatedField($name, $this->activeLangcode)->setValue($value);
    }
    // The translations array is unset when cloning the entity object, we just
    // need to restore it.
    elseif ($name == 'translations') {
      $this->translations = $value;
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
    if ($this->hasField($name)) {
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
    if ($this->hasField($name)) {
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
    if ($this->translations[$this->activeLangcode]['status'] == static::TRANSLATION_REMOVED) {
      $message = 'The entity object refers to a removed translation (@langcode) and cannot be manipulated.';
      throw new \InvalidArgumentException(format_string($message, array('@langcode' => $this->activeLangcode)));
    }

    $duplicate = clone $this;
    $entity_info = $this->entityInfo();
    $duplicate->{$entity_info['entity_keys']['id']}->value = NULL;

    // Check if the entity type supports UUIDs and generate a new one if so.
    if (!empty($entity_info['entity_keys']['uuid'])) {
      // @todo Inject the UUID service into the Entity class once possible.
      $duplicate->{$entity_info['entity_keys']['uuid']}->value = \Drupal::service('uuid')->generate();
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
    // Avoid deep-cloning when we are initializing a translation object, since
    // it will represent the same entity, only with a different active language.
    if (!$this->translationInitialize) {
      foreach ($this->fields as $name => $properties) {
        foreach ($properties as $langcode => $property) {
          $this->fields[$name][$langcode] = clone $property;
          $this->fields[$name][$langcode]->setContext($name, $this);
        }
      }

      // Ensure the translations array is actually cloned by removing the
      // original reference and re-creating its values.
      $this->clearTranslationCache();
      $translations = $this->translations;
      unset($this->translations);
      // This will trigger the magic setter as the translations array is
      // undefined now.
      $this->translations = $translations;
    }
  }

  /**
   * Overrides Entity::label() to access the label field with the new API.
   */
  public function label($langcode = NULL) {
    $label = NULL;
    $entity_info = $this->entityInfo();
    if (!isset($langcode)) {
      $langcode = $this->activeLangcode;
    }
    if (isset($entity_info['label_callback']) && function_exists($entity_info['label_callback'])) {
      $label = $entity_info['label_callback']($this->entityType, $this, $langcode);
    }
    elseif (!empty($entity_info['entity_keys']['label']) && isset($this->{$entity_info['entity_keys']['label']})) {
      $label = $this->{$entity_info['entity_keys']['label']}->value;
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    $referenced_entities = array();

    // Gather a list of referenced entities.
    foreach ($this->getProperties() as $field_items) {
      foreach ($field_items as $field_item) {
        // Loop over all properties of a field item.
        foreach ($field_item->getProperties(TRUE) as $property) {
          if ($property instanceof EntityReference && $entity = $property->getTarget()) {
            $referenced_entities[] = $entity;
          }
        }
      }
    }

    return $referenced_entities;
  }

}
