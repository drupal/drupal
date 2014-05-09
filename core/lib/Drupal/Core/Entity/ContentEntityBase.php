<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityBase.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
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
   * Local cache for field definitions.
   *
   * @see ContentEntityBase::getFieldDefinitions()
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * Local cache for the available language objects.
   *
   * @var array
   */
  protected $languages;

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
   * Local cache for the default language code.
   *
   * @var string
   */
  protected $defaultLangcode;

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
   * Holds entity keys like the ID, bundle and revision ID.
   *
   * @var array
   */
  protected $entityKeys = array();

  /**
   * The instantiated entity data definition.
   *
   * @var \Drupal\Core\Entity\TypedData\EntityDataDefinition
   */
  protected $dataDefinition;

  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = array()) {
    $this->entityTypeId = $entity_type;
    $this->entityKeys['bundle'] = $bundle ? $bundle : $this->entityTypeId;
    $this->languages = $this->languageManager()->getLanguages(Language::STATE_ALL);

    foreach ($values as $key => $value) {
      // If the key matches an existing property set the value to the property
      // to set properties like isDefaultRevision.
      // @todo: Should this be converted somehow?
      if (property_exists($this, $key) && isset($value[Language::LANGCODE_DEFAULT])) {
        $this->$key = $value[Language::LANGCODE_DEFAULT];
      }
    }

    $this->values = $values;
    foreach ($this->getEntityType()->getKeys() as $key => $field_name) {
      if (isset($this->values[$field_name])) {
        if (is_array($this->values[$field_name]) && isset($this->values[$field_name][Language::LANGCODE_DEFAULT])) {
          $this->entityKeys[$key] = $this->values[$field_name][Language::LANGCODE_DEFAULT];
        }
      }
    }

    // Initialize translations. Ensure we have at least an entry for the default
    // language.
    $data = array('status' => static::TRANSLATION_EXISTING);
    $this->translations[Language::LANGCODE_DEFAULT] = $data;
    $this->setDefaultLangcode();
    if ($translations) {
      foreach ($translations as $langcode) {
        if ($langcode != $this->defaultLangcode && $langcode != Language::LANGCODE_DEFAULT) {
          $this->translations[$langcode] = $data;
        }
      }
    }
  }

  /**
   * Returns the typed data manager.
   *
   * @return \Drupal\Core\TypedData\TypedDataManager
   */
  protected function typedDataManager() {
    return \Drupal::typedDataManager();
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    $this->newRevision = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($value = TRUE) {

    if (!$this->getEntityType()->hasKey('revision')) {
      throw new \LogicException(String::format('Entity type @entity_type does support revisions.'));
    }

    if ($value && !$this->newRevision) {
      // When saving a new revision, set any existing revision ID to NULL so as
      // to ensure that a new revision will actually be created.
      $this->set($this->getEntityType()->getKey('revision'), NULL);
    }
    $this->newRevision = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isNewRevision() {
    return $this->newRevision || ($this->getEntityType()->hasKey('revision') && !$this->getRevisionId());
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
    return $this->getEntityKey('revision');
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    $bundles = $this->entityManager()->getBundleInfo($this->entityTypeId);
    return !empty($bundles[$this->bundle()]['translatable']);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition() {
    if (!$this->dataDefinition) {
      $this->dataDefinition = EntityDataDefinition::create($this->getEntityTypeId());
      $this->dataDefinition->setBundles(array($this->bundle()));
    }
    return $this->dataDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    // @todo: This does not make much sense, so remove once TypedDataInterface
    // is removed. See https://drupal.org/node/2002138.
    return $this->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // @todo: This does not make much sense, so remove once TypedDataInterface
    // is removed. See https://drupal.org/node/2002138.
    foreach ($value as $field_name => $field_value) {
      $this->set($field_name, $field_value, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    return (string) $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->typedDataManager()->getValidator()->validate($this);
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
   * Clear entity translation object cache to remove stale references.
   */
  protected function clearTranslationCache() {
    foreach ($this->translations as &$translation) {
      unset($translation['entity']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Get the values of instantiated field objects, only serialize the values.
    foreach ($this->fields as $name => $fields) {
      foreach ($fields as $langcode => $field) {
        $this->values[$name][$langcode] = $field->getValue();
      }
    }
    $this->fields = array();
    $this->fieldDefinitions = NULL;
    $this->dataDefinition = NULL;
    $this->clearTranslationCache();

    return parent::__sleep();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getEntityKey('id');
  }

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return $this->getEntityKey('bundle');
  }

  /**
   * {inheritdoc}
   */
  public function uuid() {
    return $this->getEntityKey('uuid');
  }

  /**
   * {@inheritdoc}
   */
  public function hasField($field_name) {
    return (bool) $this->getFieldDefinition($field_name);
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
  protected function getTranslatedField($name, $langcode) {
    if ($this->translations[$this->activeLangcode]['status'] == static::TRANSLATION_REMOVED) {
      $message = 'The entity object refers to a removed translation (@langcode) and cannot be manipulated.';
      throw new \InvalidArgumentException(String::format($message, array('@langcode' => $this->activeLangcode)));
    }
    // Populate $this->fields to speed-up further look-ups and to keep track of
    // fields objects, possibly holding changes to field values.
    if (!isset($this->fields[$name][$langcode])) {
      $definition = $this->getFieldDefinition($name);
      if (!$definition) {
        throw new \InvalidArgumentException('Field ' . String::checkPlain($name) . ' is unknown.');
      }
      // Non-translatable fields are always stored with
      // Language::LANGCODE_DEFAULT as key.

      $default = $langcode == Language::LANGCODE_DEFAULT;
      if (!$default && !$definition->isTranslatable()) {
        if (!isset($this->fields[$name][Language::LANGCODE_DEFAULT])) {
          $this->fields[$name][Language::LANGCODE_DEFAULT] = $this->getTranslatedField($name, Language::LANGCODE_DEFAULT);
        }
        $this->fields[$name][$langcode] = &$this->fields[$name][Language::LANGCODE_DEFAULT];
      }
      else {
        $value = NULL;
        if (isset($this->values[$name][$langcode])) {
          $value = $this->values[$name][$langcode];
        }
        $field = \Drupal::typedDataManager()->getPropertyInstance($this, $name, $value);
        if ($default) {
          // $this->defaultLangcode might not be set if we are initializing the
          // default language code cache, in which case there is no valid
          // langcode to assign.
          $field_langcode = isset($this->defaultLangcode) ? $this->defaultLangcode : Language::LANGCODE_NOT_SPECIFIED;
        }
        else {
          $field_langcode = $langcode;
        }
        $field->setLangcode($field_langcode);
        $this->fields[$name][$langcode] = $field;
      }
    }
    return $this->fields[$name][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function set($name, $value, $notify = TRUE) {
    // If default language or an entity key changes we need to react to that.
    $notify = $name == 'langcode' || in_array($name, $this->getEntityType()->getKeys());
    $this->get($name)->setValue($value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    $properties = array();
    foreach ($this->getFieldDefinitions() as $name => $definition) {
      if ($include_computed || !$definition->isComputed()) {
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
  public function getFieldDefinition($name) {
    if (!isset($this->fieldDefinitions)) {
      $this->getFieldDefinitions();
    }
    if (isset($this->fieldDefinitions[$name])) {
      return $this->fieldDefinitions[$name];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    if (!isset($this->fieldDefinitions)) {
      $this->fieldDefinitions = \Drupal::entityManager()->getFieldDefinitions($this->entityTypeId, $this->bundle());
    }
    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = array();
    foreach ($this->getProperties() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values;
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
  public function access($operation, AccountInterface $account = NULL) {
    if ($operation == 'create') {
      return $this->entityManager()
        ->getAccessController($this->entityTypeId)
        ->createAccess($this->bundle(), $account);
    }
    return $this->entityManager()
      ->getAccessController($this->entityTypeId)
      ->access($this, $operation, $this->activeLangcode, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    $language = NULL;
    if ($this->activeLangcode != Language::LANGCODE_DEFAULT) {
      if (!isset($this->languages[$this->activeLangcode])) {
        $this->languages += $this->languageManager()->getLanguages(Language::STATE_ALL);
      }
      $language = $this->languages[$this->activeLangcode];
    }
    else {
      $language = $this->languages[$this->defaultLangcode];
    }
    return $language;
  }

  /**
   * Populates the local cache for the default language code.
   */
  protected function setDefaultLangcode() {
    // Get the language code if the property exists.
    if ($this->hasField('langcode') && ($item = $this->get('langcode')) && isset($item->language)) {
      $this->defaultLangcode = $item->language->id;
    }
    if (empty($this->defaultLangcode)) {
      // Make sure we return a proper language object.
      $this->defaultLangcode = Language::LANGCODE_NOT_SPECIFIED;
    }
    // This needs to be initialized manually as it is skipped when instantiating
    // the language field object to avoid infinite recursion.
    if (!empty($this->fields['langcode'])) {
      $this->fields['langcode'][Language::LANGCODE_DEFAULT]->setLangcode($this->defaultLangcode);
    }
  }

  /**
   * Updates language for already instantiated fields.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object.
   */
  protected function updateFieldLangcodes($langcode) {
    foreach ($this->fields as $name => $items) {
      if (!empty($items[Language::LANGCODE_DEFAULT])) {
        $items[Language::LANGCODE_DEFAULT]->setLangcode($langcode);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    if ($name == 'langcode') {
      $this->setDefaultLangcode();
      if (isset($this->translations[$this->defaultLangcode])) {
        $message = String::format('A translation already exists for the specified language (@langcode).', array('@langcode' => $this->defaultLangcode));
        throw new \InvalidArgumentException($message);
      }
      $this->updateFieldLangcodes($this->defaultLangcode);
    }
    // Check if the changed name is the value of an entity key and if the value
    // of that is currently cached, if so, reset it. Exclude the bundle from
    // that check, as it ready only and must not change, unsetting it could
    // lead to recursions.
    if ($key = array_search($name, $this->getEntityType()->getKeys())) {
      if (isset($this->entityKeys[$key]) && $key != 'bundle') {
        unset($this->entityKeys[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function getTranslation($langcode) {
    // Ensure we always use the default language code when dealing with the
    // original entity language.
    if ($langcode != Language::LANGCODE_DEFAULT && $langcode == $this->defaultLangcode) {
      $langcode = Language::LANGCODE_DEFAULT;
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
        if (isset($this->languages[$langcode])) {
          // If the entity or the requested language  is not a configured
          // language, we fall back to the entity itself, since in this case it
          // cannot have translations.
          $translation = empty($this->languages[$this->defaultLangcode]->locked) && empty($this->languages[$langcode]->locked) ? $this->addTranslation($langcode) : $this;
        }
      }
    }

    if (empty($translation)) {
      $message = 'Invalid translation language (@langcode) specified.';
      throw new \InvalidArgumentException(String::format($message, array('@langcode' => $langcode)));
    }

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getUntranslated() {
    return $this->getTranslation(Language::LANGCODE_DEFAULT);
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
    $translation->enforceIsNew = &$this->enforceIsNew;
    $translation->translationInitialize = FALSE;
    // The label is the only entity key that can change based on the language,
    // so unset that in case it is currently set.
    unset($translation->entityKeys['label']);

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation($langcode) {
    if ($langcode == $this->defaultLangcode) {
      $langcode = Language::LANGCODE_DEFAULT;
    }
    return !empty($this->translations[$langcode]['status']);
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslation($langcode, array $values = array()) {
    if (!isset($this->languages[$langcode]) || $this->hasTranslation($langcode)) {
      $message = 'Invalid translation language (@langcode) specified.';
      throw new \InvalidArgumentException(String::format($message, array('@langcode' => $langcode)));
    }

    // Instantiate a new empty entity so default values will be populated in the
    // specified language.
    $entity_type = $this->getEntityType();
    $default_values = array($entity_type->getKey('bundle') => $this->bundle(), 'langcode' => $langcode);
    $entity = $this->entityManager()
      ->getStorage($this->getEntityTypeId())
      ->create($default_values);

    foreach ($entity as $name => $field) {
      if (!isset($values[$name]) && !$field->isEmpty()) {
        $values[$name] = $field->value;
      }
    }

    $this->translations[$langcode]['status'] = static::TRANSLATION_CREATED;
    $translation = $this->getTranslation($langcode);
    $definitions = $translation->getFieldDefinitions();

    foreach ($values as $name => $value) {
      if (isset($definitions[$name]) && $definitions[$name]->isTranslatable()) {
        $translation->$name = $value;
      }
    }

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function removeTranslation($langcode) {
    if (isset($this->translations[$langcode]) && $langcode != Language::LANGCODE_DEFAULT && $langcode != $this->defaultLangcode) {
      foreach ($this->getFieldDefinitions() as $name => $definition) {
        if ($definition->isTranslatable()) {
          unset($this->values[$name][$langcode]);
          unset($this->fields[$name][$langcode]);
        }
      }
      $this->translations[$langcode]['status'] = static::TRANSLATION_REMOVED;
    }
    else {
      $message = 'The specified translation (@langcode) cannot be removed.';
      throw new \InvalidArgumentException(String::format($message, array('@langcode' => $langcode)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initTranslation($langcode) {
    if ($langcode != Language::LANGCODE_DEFAULT && $langcode != $this->defaultLangcode) {
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
      $translations[$this->defaultLangcode] = TRUE;
    }

    // Now load language objects based upon translation langcodes.
    return array_intersect_key($this->languages, $translations);
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
    foreach ($this->getFieldDefinitions() as $name => $definition) {
      if (!$definition->isComputed() && !empty($this->fields[$name])) {
        foreach ($this->fields[$name] as $langcode => $item) {
          $item->filterEmptyItems();
          $this->values[$name][$langcode] = $item->getValue();
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
    // Inline getFieldDefinition() to speed up things.
    if (!isset($this->fieldDefinitions)) {
      $this->getFieldDefinitions();
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
      throw new \InvalidArgumentException(String::format($message, array('@langcode' => $this->activeLangcode)));
    }

    $duplicate = clone $this;
    $entity_type = $this->getEntityType();
    $duplicate->{$entity_type->getKey('id')}->value = NULL;
    $duplicate->enforceIsNew();

    // Check if the entity type supports UUIDs and generate a new one if so.
    if ($entity_type->hasKey('uuid')) {
      $duplicate->{$entity_type->getKey('uuid')}->value = $this->uuidGenerator()->generate();
    }

    // Check whether the entity type supports revisions and initialize it if so.
    if ($entity_type->isRevisionable()) {
      $duplicate->{$entity_type->getKey('revision')}->value = NULL;
    }

    $duplicate->entityKeys = array();

    return $duplicate;
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    // Avoid deep-cloning when we are initializing a translation object, since
    // it will represent the same entity, only with a different active language.
    if (!$this->translationInitialize) {
      $definitions = $this->getFieldDefinitions();
      foreach ($this->fields as $name => $values) {
        $this->fields[$name] = array();
        // Untranslatable fields may have multiple references for the same field
        // object keyed by language. To avoid creating different field objects
        // we retain just the original value, as references will be recreated
        // later as needed.
        if (!$definitions[$name]->isTranslatable() && count($values) > 1) {
          $values = array_intersect_key($values, array(Language::LANGCODE_DEFAULT => TRUE));
        }
        foreach ($values as $langcode => $items) {
          $this->fields[$name][$langcode] = clone $items;
          $this->fields[$name][$langcode]->setContext($name, $this);
        }
      }

      // Ensure the translations array is actually cloned by overwriting the
      // original reference with one pointing to a copy of the array.
      $this->clearTranslationCache();
      $translations = $this->translations;
      $this->translations = &$translations;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = NULL;
    $entity_type = $this->getEntityType();
    if (($label_callback = $entity_type->getLabelCallback()) && is_callable($label_callback)) {
      $label = call_user_func($label_callback, $this);
    }
    elseif (($label_key = $entity_type->getKey('label'))) {
      $label = $this->getEntityKey('label');
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

  /**
   * Returns the value of the given entity key, if defined.
   *
   * @param string $key
   *   Name of the entity key, for example id, revision or bundle.
   *
   * @return mixed
   *   The value of the entity key, NULL if not defined.
   */
  protected function getEntityKey($key) {
    if (!isset($this->entityKeys[$key]) || !array_key_exists($key, $this->entityKeys)) {
      if ($this->getEntityType()->hasKey($key)) {
        $field_name = $this->getEntityType()->getKey($key);
        $property = $this->getFieldDefinition($field_name)->getMainPropertyName();
        $this->entityKeys[$key] = $this->get($field_name)->$property;
      }
      else {
        $this->entityKeys[$key] = NULL;
      }

    }
    return $this->entityKeys[$key];
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    return array();
  }

}
