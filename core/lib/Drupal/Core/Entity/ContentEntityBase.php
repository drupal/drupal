<?php

namespace Drupal\Core\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TranslationStatusInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Implements Entity Field API specific enhancements to the Entity class.
 *
 * @ingroup entity_api
 */
abstract class ContentEntityBase extends EntityBase implements \IteratorAggregate, ContentEntityInterface, TranslationStatusInterface {

  use EntityChangesDetectionTrait {
    getFieldsToSkipFromTranslationChangesCheck as traitGetFieldsToSkipFromTranslationChangesCheck;
  }
  use SynchronizableEntityTrait;

  /**
   * The plain data values of the contained fields.
   *
   * This always holds the original, unchanged values of the entity. The values
   * are keyed by language code, whereas LanguageInterface::LANGCODE_DEFAULT
   * is used for values in default language.
   *
   * @todo Add methods for getting original fields and for determining
   * changes.
   * @todo Provide a better way for defining default values.
   *
   * @var array
   */
  protected $values = [];

  /**
   * The array of fields, each being an instance of FieldItemListInterface.
   *
   * @var array
   */
  protected $fields = [];

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
   * @var \Drupal\Core\Language\LanguageInterface[]
   */
  protected $languages;

  /**
   * The language entity key.
   *
   * @var string
   */
  protected $langcodeKey;

  /**
   * The default langcode entity key.
   *
   * @var string
   */
  protected $defaultLangcodeKey;

  /**
   * Language code identifying the entity active language.
   *
   * This is the language field accessors will use to determine which field
   * values to manipulate.
   *
   * @var string
   */
  protected $activeLangcode = LanguageInterface::LANGCODE_DEFAULT;

  /**
   * Override the result of isDefaultTranslation().
   *
   * Under certain circumstances, such as when changing default translation, the
   * default value needs to be overridden.
   *
   * @var bool|null
   *
   * @internal
   */
  protected ?bool $enforceDefaultTranslation = NULL;

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
   * array containing the translation status and the translation object, if it
   * has already been instantiated.
   *
   * @var array
   */
  protected $translations = [];

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
   * Holds untranslatable entity keys such as the ID, bundle, and revision ID.
   *
   * @var array
   */
  protected $entityKeys = [];

  /**
   * Holds translatable entity keys such as the label.
   *
   * @var array
   */
  protected $translatableEntityKeys = [];

  /**
   * Whether entity validation was performed.
   *
   * @var bool
   */
  protected $validated = FALSE;

  /**
   * Whether entity validation is required before saving the entity.
   *
   * @var bool
   */
  protected $validationRequired = FALSE;

  /**
   * The loaded revision ID before the new revision was set.
   *
   * @var int
   */
  protected $loadedRevisionId;

  /**
   * The revision translation affected entity key.
   *
   * @var string
   */
  protected $revisionTranslationAffectedKey;

  /**
   * Whether the revision translation affected flag has been enforced.
   *
   * An array, keyed by the translation language code.
   *
   * @var bool[]
   */
  protected $enforceRevisionTranslationAffected = [];

  /**
   * Local cache for fields to skip from the checking for translation changes.
   *
   * @var array
   */
  protected static $fieldsToSkipFromTranslationChangesCheck = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    $this->entityTypeId = $entity_type;
    $this->entityKeys['bundle'] = $bundle ? $bundle : $this->entityTypeId;
    $this->langcodeKey = $this->getEntityType()->getKey('langcode');
    $this->defaultLangcodeKey = $this->getEntityType()->getKey('default_langcode');
    $this->revisionTranslationAffectedKey = $this->getEntityType()->getKey('revision_translation_affected');

    foreach ($values as $key => $value) {
      // If the key matches an existing property set the value to the property
      // to set properties like isDefaultRevision.
      // @todo Should this be converted somehow?
      if (property_exists($this, $key) && isset($value[LanguageInterface::LANGCODE_DEFAULT])) {
        $this->$key = $value[LanguageInterface::LANGCODE_DEFAULT];
      }
    }

    $this->values = $values;
    foreach ($this->getEntityType()->getKeys() as $key => $field_name) {
      if (isset($this->values[$field_name])) {
        if (is_array($this->values[$field_name])) {
          // We store untranslatable fields into an entity key without using a
          // langcode key.
          if (!$this->getFieldDefinition($field_name)->isTranslatable()) {
            if (isset($this->values[$field_name][LanguageInterface::LANGCODE_DEFAULT])) {
              if (is_array($this->values[$field_name][LanguageInterface::LANGCODE_DEFAULT])) {
                if (isset($this->values[$field_name][LanguageInterface::LANGCODE_DEFAULT][0]['value'])) {
                  $this->entityKeys[$key] = $this->values[$field_name][LanguageInterface::LANGCODE_DEFAULT][0]['value'];
                }
              }
              else {
                $this->entityKeys[$key] = $this->values[$field_name][LanguageInterface::LANGCODE_DEFAULT];
              }
            }
          }
          else {
            // We save translatable fields such as the publishing status of a node
            // into an entity key array keyed by langcode as a performance
            // optimization, so we don't have to go through TypedData when we
            // need these values.
            foreach ($this->values[$field_name] as $langcode => $field_value) {
              if (is_array($this->values[$field_name][$langcode])) {
                if (isset($this->values[$field_name][$langcode][0]['value'])) {
                  $this->translatableEntityKeys[$key][$langcode] = $this->values[$field_name][$langcode][0]['value'];
                }
              }
              else {
                $this->translatableEntityKeys[$key][$langcode] = $this->values[$field_name][$langcode];
              }
            }
          }
        }
      }
    }

    // Initialize translations. Ensure we have at least an entry for the default
    // language.
    // We determine if the entity is new by checking in the entity values for
    // the presence of the id entity key, as the usage of ::isNew() is not
    // possible in the constructor.
    $data = isset($values[$this->getEntityType()->getKey('id')]) ? ['status' => static::TRANSLATION_EXISTING] : ['status' => static::TRANSLATION_CREATED];
    $this->translations[LanguageInterface::LANGCODE_DEFAULT] = $data;
    $this->setDefaultLangcode();
    if ($translations) {
      foreach ($translations as $langcode) {
        if ($langcode != $this->defaultLangcode && $langcode != LanguageInterface::LANGCODE_DEFAULT) {
          $this->translations[$langcode] = $data;
        }
      }
    }
    if ($this->getEntityType()->isRevisionable()) {
      // Store the loaded revision ID the entity has been loaded with to
      // keep it safe from changes.
      $this->updateLoadedRevisionId();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguages() {
    if (empty($this->languages)) {
      $this->languages = $this->languageManager()->getLanguages(LanguageInterface::STATE_ALL);
      // If the entity references a language that is not or no longer available,
      // we return a mock language object to avoid disrupting the consuming
      // code.
      if (!isset($this->languages[$this->defaultLangcode])) {
        $this->languages[$this->defaultLangcode] = new Language(['id' => $this->defaultLangcode]);
      }
    }
    return $this->languages;
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
      throw new \LogicException("Entity type {$this->getEntityTypeId()} does not support revisions.");
    }

    if ($value && !$this->newRevision) {
      // When saving a new revision, set any existing revision ID to NULL so as
      // to ensure that a new revision will actually be created.
      $this->set($this->getEntityType()->getKey('revision'), NULL);
    }
    elseif (!$value && $this->newRevision) {
      // If ::setNewRevision(FALSE) is called after ::setNewRevision(TRUE) we
      // have to restore the loaded revision ID.
      $this->set($this->getEntityType()->getKey('revision'), $this->getLoadedRevisionId());
    }

    $this->newRevision = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoadedRevisionId() {
    return $this->loadedRevisionId;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLoadedRevisionId() {
    $this->loadedRevisionId = $this->getRevisionId() ?: $this->loadedRevisionId;
    return $this;
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
    // New entities should always ensure at least one default revision exists,
    // creating an entity without a default revision is an invalid state.
    return $this->isNew() || $return;
  }

  /**
   * {@inheritdoc}
   */
  public function wasDefaultRevision() {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $this->getEntityType();
    if (!$entity_type->isRevisionable()) {
      return TRUE;
    }

    $revision_default_key = $entity_type->getRevisionMetadataKey('revision_default');
    $value = $this->isNew() || $this->get($revision_default_key)->value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision() {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());

    return $this->getLoadedRevisionId() == $storage->getLatestRevisionId($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestTranslationAffectedRevision() {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());

    return $this->getLoadedRevisionId() == $storage->getLatestTranslationAffectedRevisionId($this->id(), $this->language()->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionTranslationAffected() {
    return $this->hasField($this->revisionTranslationAffectedKey) ? $this->get($this->revisionTranslationAffectedKey)->value : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionTranslationAffected($affected) {
    if ($this->hasField($this->revisionTranslationAffectedKey)) {
      $this->set($this->revisionTranslationAffectedKey, $affected);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionTranslationAffectedEnforced() {
    return !empty($this->enforceRevisionTranslationAffected[$this->activeLangcode]);
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionTranslationAffectedEnforced($enforced) {
    $this->enforceRevisionTranslationAffected[$this->activeLangcode] = $enforced;
    return $this;
  }

  /**
   * Set or clear an override of the isDefaultTranslation() result.
   *
   * @param bool|null $enforce_default_translation
   *   If boolean value is passed, the value will override the result of
   *   isDefaultTranslation() method. If NULL is passed, the default logic will
   *   be used.
   *
   * @return $this
   */
  public function setDefaultTranslationEnforced(?bool $enforce_default_translation): static {
    $this->enforceDefaultTranslation = $enforce_default_translation;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultTranslation() {
    if ($this->enforceDefaultTranslation !== NULL) {
      return $this->enforceDefaultTranslation;
    }
    return $this->activeLangcode === LanguageInterface::LANGCODE_DEFAULT;
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
    // Check the bundle is translatable, the entity has a language defined, and
    // the site has more than one language.
    $bundles = $this->entityTypeBundleInfo()->getBundleInfo($this->entityTypeId);
    return !empty($bundles[$this->bundle()]['translatable']) && !$this->getUntranslated()->language()->isLocked() && $this->languageManager()->isMultilingual();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // An entity requiring validation should not be saved if it has not been
    // actually validated.
    if ($this->validationRequired && !$this->validated) {
      throw new \LogicException('Entity validation is required, but was skipped.');
    }
    else {
      $this->validated = FALSE;
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update the status of all saved translations.
    $removed = [];
    foreach ($this->translations as $langcode => &$data) {
      if ($data['status'] == static::TRANSLATION_REMOVED) {
        $removed[$langcode] = TRUE;
      }
      else {
        $data['status'] = static::TRANSLATION_EXISTING;
      }
    }
    $this->translations = array_diff_key($this->translations, $removed);

    // Reset the new revision flag.
    $this->newRevision = FALSE;

    // Reset the enforcement of the revision translation affected flag.
    $this->enforceRevisionTranslationAffected = [];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $this->validated = TRUE;
    $violations = $this->getTypedData()->validate();
    return new EntityConstraintViolationList($this, iterator_to_array($violations));
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationRequired() {
    return (bool) $this->validationRequired;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationRequired($required) {
    $this->validationRequired = $required;
    return $this;
  }

  /**
   * Clears entity translation object cache to remove stale references.
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
    $this->fields = [];
    $this->fieldDefinitions = NULL;
    $this->languages = NULL;
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
   * {@inheritdoc}
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
  public function get($field_name) {
    if (!isset($this->fields[$field_name][$this->activeLangcode])) {
      return $this->getTranslatedField($field_name, $this->activeLangcode);
    }
    return $this->fields[$field_name][$this->activeLangcode];
  }

  /**
   * Gets a translated field.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   */
  protected function getTranslatedField($name, $langcode) {
    if ($this->translations[$this->activeLangcode]['status'] == static::TRANSLATION_REMOVED) {
      throw new \InvalidArgumentException("The entity object refers to a removed translation ({$this->activeLangcode}) and cannot be manipulated.");
    }
    // Populate $this->fields to speed-up further look-ups and to keep track of
    // fields objects, possibly holding changes to field values.
    if (!isset($this->fields[$name][$langcode])) {
      $definition = $this->getFieldDefinition($name);
      if (!$definition) {
        throw new \InvalidArgumentException("Field $name is unknown.");
      }
      // Non-translatable fields are always stored with
      // LanguageInterface::LANGCODE_DEFAULT as key.

      $default = $langcode == LanguageInterface::LANGCODE_DEFAULT;
      if (!$default && !$definition->isTranslatable()) {
        if (!isset($this->fields[$name][LanguageInterface::LANGCODE_DEFAULT])) {
          $this->fields[$name][LanguageInterface::LANGCODE_DEFAULT] = $this->getTranslatedField($name, LanguageInterface::LANGCODE_DEFAULT);
        }
        $this->fields[$name][$langcode] = &$this->fields[$name][LanguageInterface::LANGCODE_DEFAULT];
      }
      else {
        $value = NULL;
        if (isset($this->values[$name][$langcode])) {
          $value = $this->values[$name][$langcode];
        }
        $field = \Drupal::service('plugin.manager.field.field_type')->createFieldItemList($this->getTranslation($langcode), $name, $value);
        if ($default) {
          // $this->defaultLangcode might not be set if we are initializing the
          // default language code cache, in which case there is no valid
          // langcode to assign.
          $field_langcode = $this->defaultLangcode ?? LanguageInterface::LANGCODE_NOT_SPECIFIED;
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
    // Assign the value on the child and overrule notify such that we get
    // notified to handle changes afterwards. We can ignore notify as there is
    // no parent to notify anyway.
    $this->get($name)->setValue($value, TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($include_computed = TRUE) {
    $fields = [];
    foreach ($this->getFieldDefinitions() as $name => $definition) {
      if ($include_computed || !$definition->isComputed()) {
        $fields[$name] = $this->get($name);
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableFields($include_computed = TRUE) {
    $fields = [];
    foreach ($this->getFieldDefinitions() as $name => $definition) {
      if (($include_computed || !$definition->isComputed()) && $definition->isTranslatable()) {
        $fields[$name] = $this->get($name);
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getIterator() {
    return new \ArrayIterator($this->getFields());
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
      $this->fieldDefinitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->entityTypeId, $this->bundle());
    }
    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];
    foreach ($this->getFields() as $name => $property) {
      $values[$name] = $property->getValue();
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      return $this->entityTypeManager()
        ->getAccessControlHandler($this->entityTypeId)
        ->createAccess($this->bundle(), $account, [], $return_as_object);
    }
    return $this->entityTypeManager()
      ->getAccessControlHandler($this->entityTypeId)
      ->access($this, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function language() {
    $language = NULL;
    if ($this->activeLangcode != LanguageInterface::LANGCODE_DEFAULT) {
      if (!isset($this->languages[$this->activeLangcode])) {
        $this->getLanguages();
      }
      $language = $this->languages[$this->activeLangcode];
    }
    else {
      // @todo Avoid this check by getting the language from the language
      //   manager directly in https://www.drupal.org/node/2303877.
      if (!isset($this->languages[$this->defaultLangcode])) {
        $this->getLanguages();
      }
      $language = $this->languages[$this->defaultLangcode];
    }
    return $language;
  }

  /**
   * Populates the local cache for the default language code.
   */
  protected function setDefaultLangcode() {
    // Get the language code if the property exists.
    // Try to read the value directly from the list of entity keys which got
    // initialized in __construct(). This avoids creating a field item object.
    if (isset($this->translatableEntityKeys['langcode'][$this->activeLangcode])) {
      $this->defaultLangcode = $this->translatableEntityKeys['langcode'][$this->activeLangcode];
    }
    elseif ($this->hasField($this->langcodeKey) && ($item = $this->get($this->langcodeKey)) && isset($item->language)) {
      $this->defaultLangcode = $item->language->getId();
      $this->translatableEntityKeys['langcode'][$this->activeLangcode] = $this->defaultLangcode;
    }

    if (empty($this->defaultLangcode)) {
      // Make sure we return a proper language object, if the entity has a
      // langcode field, default to the site's default language.
      if ($this->hasField($this->langcodeKey)) {
        $this->defaultLangcode = $this->languageManager()->getDefaultLanguage()->getId();
      }
      else {
        $this->defaultLangcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
      }
    }

    // This needs to be initialized manually as it is skipped when instantiating
    // the language field object to avoid infinite recursion.
    if (!empty($this->fields[$this->langcodeKey])) {
      $this->fields[$this->langcodeKey][LanguageInterface::LANGCODE_DEFAULT]->setLangcode($this->defaultLangcode);
    }
  }

  /**
   * Updates language for already instantiated fields.
   */
  protected function updateFieldLangcodes($langcode) {
    foreach ($this->fields as $items) {
      if (!empty($items[LanguageInterface::LANGCODE_DEFAULT])) {
        $items[LanguageInterface::LANGCODE_DEFAULT]->setLangcode($langcode);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    // Check if the changed name is the value of any entity keys and if any of
    // those values are currently cached, if so, reset it. Exclude the bundle
    // from that check, as it ready only and must not change, unsetting it could
    // lead to recursions.
    foreach (array_keys($this->getEntityType()->getKeys(), $name, TRUE) as $key) {
      if ($key != 'bundle') {
        if (isset($this->entityKeys[$key])) {
          unset($this->entityKeys[$key]);
        }
        elseif (isset($this->translatableEntityKeys[$key][$this->activeLangcode])) {
          unset($this->translatableEntityKeys[$key][$this->activeLangcode]);
        }
        // If the revision identifier field is being populated with the original
        // value, we need to make sure the "new revision" flag is reset
        // accordingly.
        if ($key === 'revision' && $this->getRevisionId() == $this->getLoadedRevisionId() && !$this->isNew()) {
          $this->newRevision = FALSE;
        }
      }
    }

    switch ($name) {
      case $this->langcodeKey:
        if ($this->isDefaultTranslation()) {
          // Update the default internal language cache.
          $this->setDefaultLangcode();
          if (isset($this->translations[$this->defaultLangcode])) {
            $message = new FormattableMarkup('A translation already exists for the specified language (@langcode).', ['@langcode' => $this->defaultLangcode]);
            throw new \InvalidArgumentException($message);
          }
          $this->updateFieldLangcodes($this->defaultLangcode);
        }
        else {
          // @todo Allow the translation language to be changed. See
          //   https://www.drupal.org/node/2443989.
          $items = $this->get($this->langcodeKey);
          if ($items->value != $this->activeLangcode) {
            $items->setValue($this->activeLangcode, FALSE);
            $message = new FormattableMarkup('The translation language cannot be changed (@langcode).', ['@langcode' => $this->activeLangcode]);
            throw new \LogicException($message);
          }
        }
        break;

      case $this->defaultLangcodeKey:
        // @todo Use a standard method to make the default_langcode field
        //   read-only. See https://www.drupal.org/node/2443991.
        if (isset($this->values[$this->defaultLangcodeKey]) && $this->get($this->defaultLangcodeKey)->value != $this->isDefaultTranslation()) {
          $this->get($this->defaultLangcodeKey)->setValue($this->isDefaultTranslation(), FALSE);
          $message = new FormattableMarkup('The default translation flag cannot be changed (@langcode).', ['@langcode' => $this->activeLangcode]);
          throw new \LogicException($message);
        }
        break;

      case $this->revisionTranslationAffectedKey:
        // If the revision translation affected flag is being set then enforce
        // its value.
        $this->setRevisionTranslationAffectedEnforced(TRUE);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslation($langcode) {
    // Ensure we always use the default language code when dealing with the
    // original entity language.
    if ($langcode != LanguageInterface::LANGCODE_DEFAULT && $langcode == $this->defaultLangcode) {
      $langcode = LanguageInterface::LANGCODE_DEFAULT;
    }

    // Populate entity translation object cache so it will be available for all
    // translation objects.
    if (!isset($this->translations[$this->activeLangcode]['entity'])) {
      $this->translations[$this->activeLangcode]['entity'] = $this;
    }

    // If we already have a translation object for the specified language we can
    // just return it.
    if (isset($this->translations[$langcode]['entity'])) {
      $translation = $this->translations[$langcode]['entity'];
    }
    // Otherwise if an existing translation language was specified we need to
    // instantiate the related translation.
    elseif (isset($this->translations[$langcode])) {
      $translation = $this->initializeTranslation($langcode);
      $this->translations[$langcode]['entity'] = $translation;
    }

    if (empty($translation)) {
      throw new \InvalidArgumentException("Invalid translation language ($langcode) specified.");
    }

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getUntranslated() {
    return $this->getTranslation(LanguageInterface::LANGCODE_DEFAULT);
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
    $translation->newRevision = &$this->newRevision;
    $translation->entityKeys = &$this->entityKeys;
    $translation->translatableEntityKeys = &$this->translatableEntityKeys;
    $translation->translationInitialize = FALSE;
    $translation->typedData = NULL;
    $translation->loadedRevisionId = &$this->loadedRevisionId;
    $translation->isDefaultRevision = &$this->isDefaultRevision;
    $translation->enforceRevisionTranslationAffected = &$this->enforceRevisionTranslationAffected;
    $translation->isSyncing = &$this->isSyncing;

    return $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation($langcode) {
    if ($langcode == $this->defaultLangcode) {
      $langcode = LanguageInterface::LANGCODE_DEFAULT;
    }
    return !empty($this->translations[$langcode]['status']);
  }

  /**
   * {@inheritdoc}
   */
  public function isNewTranslation() {
    return $this->translations[$this->activeLangcode]['status'] == static::TRANSLATION_CREATED;
  }

  /**
   * {@inheritdoc}
   */
  public function addTranslation($langcode, array $values = []) {
    // Make sure we do not attempt to create a translation if an invalid
    // language is specified or the entity cannot be translated.
    $this->getLanguages();
    if (!isset($this->languages[$langcode]) || $this->hasTranslation($langcode) || $this->languages[$langcode]->isLocked()) {
      throw new \InvalidArgumentException("Invalid translation language ($langcode) specified.");
    }
    if ($this->languages[$this->defaultLangcode]->isLocked()) {
      throw new \InvalidArgumentException("The entity cannot be translated since it is language neutral ({$this->defaultLangcode}).");
    }

    // Initialize the translation object.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());
    $this->translations[$langcode]['status'] = !isset($this->translations[$langcode]['status_existed']) ? static::TRANSLATION_CREATED : static::TRANSLATION_EXISTING;
    return $storage->createTranslation($this, $langcode, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function removeTranslation($langcode) {
    if (isset($this->translations[$langcode]) && $langcode != LanguageInterface::LANGCODE_DEFAULT && $langcode != $this->defaultLangcode) {
      foreach ($this->getFieldDefinitions() as $name => $definition) {
        if ($definition->isTranslatable()) {
          unset($this->values[$name][$langcode]);
          unset($this->fields[$name][$langcode]);
        }
      }
      // If removing a translation which has not been saved yet, then we have
      // to remove it completely so that ::getTranslationStatus returns the
      // proper status.
      if ($this->translations[$langcode]['status'] == static::TRANSLATION_CREATED) {
        unset($this->translations[$langcode]);
      }
      else {
        if ($this->translations[$langcode]['status'] == static::TRANSLATION_EXISTING) {
          $this->translations[$langcode]['status_existed'] = TRUE;
        }
        $this->translations[$langcode]['status'] = static::TRANSLATION_REMOVED;
      }
    }
    else {
      throw new \InvalidArgumentException("The specified translation ($langcode) cannot be removed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationStatus($langcode) {
    if ($langcode == $this->defaultLangcode) {
      $langcode = LanguageInterface::LANGCODE_DEFAULT;
    }
    return isset($this->translations[$langcode]) ? $this->translations[$langcode]['status'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationLanguages($include_default = TRUE) {
    $translations = array_filter($this->translations, function ($translation) {
      return $translation['status'];
    });
    unset($translations[LanguageInterface::LANGCODE_DEFAULT]);

    if ($include_default) {
      $translations[$this->defaultLangcode] = TRUE;
    }

    // Now load language objects based upon translation langcodes.
    return array_intersect_key($this->getLanguages(), $translations);
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
   * @todo A lot of code still uses non-fields (e.g. $entity->content in view
   *   builders) by reference. Clean that up.
   */
  public function &__get($name) {
    // If this is an entity field, handle it accordingly. We first check whether
    // a field object has been already created. If not, we create one.
    if (isset($this->fields[$name][$this->activeLangcode])) {
      return $this->fields[$name][$this->activeLangcode];
    }
    // Inline getFieldDefinition() to speed things up.
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
    // Inline getFieldDefinition() to speed things up.
    if (!isset($this->fieldDefinitions)) {
      $this->getFieldDefinitions();
    }
    // Handle Field API fields.
    if (isset($this->fieldDefinitions[$name])) {
      // Support setting values via property objects.
      if ($value instanceof TypedDataInterface) {
        $value = $value->getValue();
      }
      // If a FieldItemList object already exists, set its value.
      if (isset($this->fields[$name][$this->activeLangcode])) {
        $this->fields[$name][$this->activeLangcode]->setValue($value);
      }
      // If not, create one.
      else {
        $this->getTranslatedField($name, $this->activeLangcode)->setValue($value);
      }
    }
    // The translations array is unset when cloning the entity object, we just
    // need to restore it.
    elseif ($name == 'translations') {
      $this->translations = $value;
    }
    // Directly write non-field values.
    else {
      $this->values[$name] = $value;
    }
  }

  /**
   * Implements the magic method for isset().
   */
  public function __isset($name) {
    // "Official" Field API fields are always set. For non-field properties,
    // check the internal values.
    return $this->hasField($name) ? TRUE : isset($this->values[$name]);
  }

  /**
   * Implements the magic method for unset().
   */
  public function __unset($name) {
    // Unsetting a field means emptying it.
    if ($this->hasField($name)) {
      $this->get($name)->setValue([]);
    }
    // For non-field properties, unset the internal value.
    else {
      unset($this->values[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $entity_type_manager = \Drupal::entityTypeManager();
    $class_name = static::class;
    $storage = $entity_type_manager->getStorage($entity_type_repository->getEntityTypeFromClass($class_name));

    // Always explicitly specify the bundle if the entity has a bundle class.
    if ($storage instanceof BundleEntityStorageInterface && ($bundle = $storage->getBundleFromClass($class_name))) {
      $values[$storage->getEntityType()->getKey('bundle')] = $bundle;
    }

    return $storage->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    if ($this->translations[$this->activeLangcode]['status'] == static::TRANSLATION_REMOVED) {
      throw new \InvalidArgumentException("The entity object refers to a removed translation ({$this->activeLangcode}) and cannot be manipulated.");
    }

    $duplicate = clone $this;
    $entity_type = $this->getEntityType();
    if ($entity_type->hasKey('id')) {
      $duplicate->{$entity_type->getKey('id')}->value = NULL;
    }
    $duplicate->enforceIsNew();

    // Check if the entity type supports UUIDs and generate a new one if so.
    if ($entity_type->hasKey('uuid')) {
      $duplicate->{$entity_type->getKey('uuid')}->value = $this->uuidGenerator()->generate();
    }

    // Check whether the entity type supports revisions and initialize it if so.
    if ($entity_type->isRevisionable()) {
      $duplicate->{$entity_type->getKey('revision')}->value = NULL;
      $duplicate->loadedRevisionId = NULL;
    }

    return $duplicate;
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    // Avoid deep-cloning when we are initializing a translation object, since
    // it will represent the same entity, only with a different active language.
    if ($this->translationInitialize) {
      return;
    }

    // The translation is a different object, and needs its own TypedData
    // adapter object.
    $this->typedData = NULL;
    $definitions = $this->getFieldDefinitions();

    // The translation cache has to be cleared before cloning the fields
    // below so that the call to getTranslation() does not re-use the
    // translation objects of the old entity but instead creates new
    // translation objects from the newly cloned entity. Otherwise the newly
    // cloned field item lists would hold references to the old translation
    // objects in their $parent property after the call to setContext().
    $this->clearTranslationCache();

    // Because the new translation objects that are created below are
    // themselves created by *cloning* the newly cloned entity we need to
    // make sure that the references to property values are properly cloned
    // before cloning the fields. Otherwise calling
    // $items->getEntity()->isNew(), for example, would return the
    // $enforceIsNew value of the old entity.

    // Ensure the translations array is actually cloned by overwriting the
    // original reference with one pointing to a copy of the array.
    $translations = $this->translations;
    $this->translations = &$translations;

    // Ensure that the following properties are actually cloned by
    // overwriting the original references with ones pointing to copies of
    // them: enforceIsNew, newRevision, loadedRevisionId, fields, entityKeys,
    // translatableEntityKeys, values, isDefaultRevision and
    // enforceRevisionTranslationAffected.
    $enforce_is_new = $this->enforceIsNew;
    $this->enforceIsNew = &$enforce_is_new;

    $new_revision = $this->newRevision;
    $this->newRevision = &$new_revision;

    $original_revision_id = $this->loadedRevisionId;
    $this->loadedRevisionId = &$original_revision_id;

    $fields = $this->fields;
    $this->fields = &$fields;

    $entity_keys = $this->entityKeys;
    $this->entityKeys = &$entity_keys;

    $translatable_entity_keys = $this->translatableEntityKeys;
    $this->translatableEntityKeys = &$translatable_entity_keys;

    $values = $this->values;
    $this->values = &$values;

    $default_revision = $this->isDefaultRevision;
    $this->isDefaultRevision = &$default_revision;

    $is_revision_translation_affected_enforced = $this->enforceRevisionTranslationAffected;
    $this->enforceRevisionTranslationAffected = &$is_revision_translation_affected_enforced;

    $is_syncing = $this->isSyncing;
    $this->isSyncing = &$is_syncing;

    foreach ($this->fields as $name => $fields_by_langcode) {
      $this->fields[$name] = [];
      // Untranslatable fields may have multiple references for the same field
      // object keyed by language. To avoid creating different field objects
      // we retain just the original value, as references will be recreated
      // later as needed.
      if (!$definitions[$name]->isTranslatable() && count($fields_by_langcode) > 1) {
        $fields_by_langcode = array_intersect_key($fields_by_langcode, [LanguageInterface::LANGCODE_DEFAULT => TRUE]);
      }
      foreach ($fields_by_langcode as $langcode => $items) {
        $this->fields[$name][$langcode] = clone $items;
        $this->fields[$name][$langcode]->setContext($name, $this->getTranslation($langcode)->getTypedData());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if ($this->getEntityType()->getKey('label')) {
      return $this->getEntityKey('label');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    $referenced_entities = [];

    // Gather a list of referenced entities.
    foreach ($this->getFields() as $field_items) {
      foreach ($field_items as $field_item) {
        // Loop over all properties of a field item.
        foreach ($field_item->getProperties(TRUE) as $property) {
          if ($property instanceof EntityReference && $entity = $property->getValue()) {
            $referenced_entities[] = $entity;
          }
        }
      }
    }

    return $referenced_entities;
  }

  /**
   * Gets the value of the given entity key, if defined.
   *
   * @param string $key
   *   Name of the entity key, for example id, revision or bundle.
   *
   * @return mixed
   *   The value of the entity key, NULL if not defined.
   */
  protected function getEntityKey($key) {
    // If the value is known already, return it.
    if (isset($this->entityKeys[$key])) {
      return $this->entityKeys[$key];
    }
    if (isset($this->translatableEntityKeys[$key][$this->activeLangcode])) {
      return $this->translatableEntityKeys[$key][$this->activeLangcode];
    }

    // Otherwise fetch the value by creating a field object.
    $value = NULL;
    if ($this->getEntityType()->hasKey($key)) {
      $field_name = $this->getEntityType()->getKey($key);
      $definition = $this->getFieldDefinition($field_name);
      $property = $definition->getFieldStorageDefinition()->getMainPropertyName();
      $value = $this->get($field_name)->$property;

      // Put it in the right array, depending on whether it is translatable.
      if ($definition->isTranslatable()) {
        $this->translatableEntityKeys[$key][$this->activeLangcode] = $value;
      }
      else {
        $this->entityKeys[$key] = $value;
      }
    }
    else {
      $this->entityKeys[$key] = $value;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    if ($entity_type->hasKey('id')) {
      $fields[$entity_type->getKey('id')] = BaseFieldDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }
    if ($entity_type->hasKey('uuid')) {
      $fields[$entity_type->getKey('uuid')] = BaseFieldDefinition::create('uuid')
        ->setLabel(new TranslatableMarkup('UUID'))
        ->setReadOnly(TRUE);
    }
    if ($entity_type->hasKey('revision')) {
      $fields[$entity_type->getKey('revision')] = BaseFieldDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('Revision ID'))
        ->setReadOnly(TRUE)
        ->setSetting('unsigned', TRUE);
    }
    if ($entity_type->hasKey('langcode')) {
      $fields[$entity_type->getKey('langcode')] = BaseFieldDefinition::create('language')
        ->setLabel(new TranslatableMarkup('Language'))
        ->setDisplayOptions('view', [
          'region' => 'hidden',
        ])
        ->setDisplayOptions('form', [
          'type' => 'language_select',
          'weight' => 2,
        ]);
      if ($entity_type->isRevisionable()) {
        $fields[$entity_type->getKey('langcode')]->setRevisionable(TRUE);
      }
      if ($entity_type->isTranslatable()) {
        $fields[$entity_type->getKey('langcode')]->setTranslatable(TRUE);
      }
    }
    if ($entity_type->hasKey('bundle')) {
      if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
        $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('entity_reference')
          ->setLabel($entity_type->getBundleLabel())
          ->setSetting('target_type', $bundle_entity_type_id)
          ->setRequired(TRUE)
          ->setReadOnly(TRUE);
      }
      else {
        $fields[$entity_type->getKey('bundle')] = BaseFieldDefinition::create('string')
          ->setLabel($entity_type->getBundleLabel())
          ->setRequired(TRUE)
          ->setReadOnly(TRUE);
      }
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    return [];
  }

  /**
   * Returns an array of field names to skip in ::hasTranslationChanges.
   *
   * @return array
   *   An array of field names.
   */
  protected function getFieldsToSkipFromTranslationChangesCheck() {
    $bundle = $this->bundle();
    if (!isset(static::$fieldsToSkipFromTranslationChangesCheck[$this->entityTypeId][$bundle])) {
      static::$fieldsToSkipFromTranslationChangesCheck[$this->entityTypeId][$bundle] = $this->traitGetFieldsToSkipFromTranslationChangesCheck($this);
    }
    return static::$fieldsToSkipFromTranslationChangesCheck[$this->entityTypeId][$bundle];
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslationChanges() {
    if ($this->isNew()) {
      return TRUE;
    }

    // $this->original only exists during save. See
    // \Drupal\Core\Entity\EntityStorageBase::save(). If it exists we re-use it
    // here for performance reasons.
    /** @var \Drupal\Core\Entity\ContentEntityBase $original */
    $original = $this->original ? $this->original : NULL;

    if (!$original) {
      $id = $this->getOriginalId() ?? $this->id();
      $original = $this->entityTypeManager()->getStorage($this->getEntityTypeId())->loadUnchanged($id);
    }

    // If the current translation has just been added, we have a change.
    $translated = count($this->translations) > 1;
    if ($translated && !$original->hasTranslation($this->activeLangcode)) {
      return TRUE;
    }

    // Compare field item current values with the original ones to determine
    // whether we have changes. If a field is not translatable and the entity is
    // translated we skip it because, depending on the use case, it would make
    // sense to mark all translations as changed or none of them. We skip also
    // computed fields as comparing them with their original values might not be
    // possible or be meaningless.
    /** @var \Drupal\Core\Entity\ContentEntityBase $translation */
    $translation = $original->getTranslation($this->activeLangcode);
    $langcode = $this->language()->getId();

    // The list of fields to skip from the comparison.
    $skip_fields = $this->getFieldsToSkipFromTranslationChangesCheck();

    // We also check untranslatable fields, so that a change to those will mark
    // all translations as affected, unless they are configured to only affect
    // the default translation.
    $skip_untranslatable_fields = !$this->isDefaultTranslation() && $this->isDefaultTranslationAffectedOnly();

    foreach ($this->getFieldDefinitions() as $field_name => $definition) {
      // @todo Avoid special-casing the following fields. See
      //   https://www.drupal.org/node/2329253.
      if (in_array($field_name, $skip_fields, TRUE) || ($skip_untranslatable_fields && !$definition->isTranslatable())) {
        continue;
      }
      $items = $this->get($field_name)->filterEmptyItems();
      $original_items = $translation->get($field_name)->filterEmptyItems();
      if ($items->hasAffectingChanges($original_items, $langcode)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultTranslationAffectedOnly() {
    $bundle_name = $this->bundle();
    $bundle_info = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo($this->getEntityTypeId());
    return !empty($bundle_info[$bundle_name]['untranslatable_fields.default_translation_affected']);
  }

}
