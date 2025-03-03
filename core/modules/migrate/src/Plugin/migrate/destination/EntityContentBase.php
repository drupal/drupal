<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\migrate\Audit\HighestIdInterface;
use Drupal\migrate\Exception\EntityValidationException;
use Drupal\migrate\Plugin\MigrateValidatableEntityInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore huhuu maailma otsikko sivun validatable

/**
 * Provides destination class for all content entities lacking a specific class.
 *
 * Available configuration keys:
 * - translations: (optional) A boolean that indicates if the entity is
 *   translatable. When TRUE, migration rows will be considered as translations.
 *   This means the migration will attempt to load an existing entity and, if
 *   found, save the row data into it as a new translation rather than creating
 *   a new entity. For this functionality, the migration process definition must
 *   include mappings for the entity ID and the entity language field. If this
 *   property is TRUE, the migration will also have an additional destination ID
 *   for the language code.
 * - overwrite_properties: (optional) A list of properties that will be
 *   overwritten if an entity with the same ID already exists. Any properties
 *   that are not listed will not be overwritten.
 * - validate: (optional) Boolean, indicates whether an entity should be
 *   validated, defaults to FALSE.
 *
 * Example:
 *
 * The example below will create a 'node' entity of content type 'article'.
 *
 * The language of the source will be used because the configuration
 * 'translations: true' was set. Without this configuration option the site's
 * default language would be used.
 *
 * The example content type has fields 'title', 'body' and 'field_example'.
 * The text format of the body field is defaulted to 'basic_html'. The example
 * uses the EmbeddedDataSource source plugin for the sake of simplicity.
 *
 * If the migration is executed again in an update mode, any updates done in the
 * destination Drupal site to the 'title' and 'body' fields would be overwritten
 * with the original source values. Updates done to 'field_example' would be
 * preserved because 'field_example' is not included in 'overwrite_properties'
 * configuration.
 * @code
 * id: custom_article_migration
 * label: Custom article migration
 * source:
 *   plugin: embedded_data
 *   data_rows:
 *     -
 *       id: 1
 *       langcode: 'fi'
 *       title: 'Sivun otsikko'
 *       field_example: 'Huhuu'
 *       content: '<p>Hoi maailma</p>'
 *   ids:
 *     id:
 *       type: integer
 * process:
 *   nid: id
 *   langcode: langcode
 *   title: title
 *   field_example: field_example
 *   'body/0/value': content
 *   'body/0/format':
 *     plugin: default_value
 *     default_value: basic_html
 * destination:
 *   plugin: entity:node
 *   default_bundle: article
 *   translations: true
 *   overwrite_properties:
 *     - title
 *     - body
 *   # Run entity and fields validation before saving an entity.
 *   # @see \Drupal\Core\Entity\FieldableEntityInterface::validate()
 *   validate: true
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\Entity
 * @see \Drupal\migrate\Plugin\migrate\destination\EntityRevision
 */
class EntityContentBase extends Entity implements HighestIdInterface, MigrateValidatableEntityInterface {

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructs a content entity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ?AccountSwitcherInterface $account_switcher = NULL, ?EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles);
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->accountSwitcher = $account_switcher;
    if ($entity_type_bundle_info === NULL) {
      @trigger_error('Calling ' . __NAMESPACE__ . '\EntityContentBase::__construct() without the $entity_type_bundle_info argument is deprecated in drupal:11.2.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3476634', E_USER_DEPRECATED);
      $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
    }
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type)),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('account_switcher'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   *   When an entity cannot be looked up.
   * @throws \Drupal\migrate\Exception\EntityValidationException
   *   When an entity validation hasn't been passed.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;
    $entity = $this->getEntity($row, $old_destination_id_values);
    if (!$entity) {
      throw new MigrateException('Unable to get entity');
    }
    assert($entity instanceof ContentEntityInterface);
    if ($this->isEntityValidationRequired($entity)) {
      $this->validateEntity($entity);
    }
    $ids = $this->save($entity, $old_destination_id_values);
    if ($this->isTranslationDestination()) {
      $ids[] = $entity->language()->getId();
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityValidationRequired(FieldableEntityInterface $entity) {
    // Prioritize the entity method over migration config because it won't be
    // possible to save that entity non validated.
    /* @see \Drupal\Core\Entity\ContentEntityBase::preSave() */
    return $entity->isValidationRequired() || !empty($this->configuration['validate']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateEntity(FieldableEntityInterface $entity) {
    // Entity validation can require the user that owns the entity. Switch to
    // use that user during validation.
    // As an example:
    // @see \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint
    $account = $entity instanceof EntityOwnerInterface ? $entity->getOwner() : NULL;
    // Validate account exists as the owner reference could be invalid for any
    // number of reasons.
    if ($account) {
      $this->accountSwitcher->switchTo($account);
    }
    // This finally block ensures that the account is always switched back, even
    // if an exception was thrown. Any validation exceptions are intentionally
    // left unhandled. They should be caught and logged by a catch block
    // surrounding the row import and then added to the migration messages table
    // for the current row.
    try {
      $violations = $entity->validate();
    } finally {
      if ($account) {
        $this->accountSwitcher->switchBack();
      }
    }

    if (count($violations) > 0) {
      throw new EntityValidationException($violations);
    }
  }

  /**
   * Saves the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param array $old_destination_id_values
   *   (optional) An array of destination ID values. Defaults to an empty array.
   *
   * @return array
   *   An array containing the entity ID.
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    $entity->setSyncing(TRUE);
    $entity->save();
    return [$entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslationDestination() {
    return !empty($this->configuration['translations']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];

    $id_key = $this->getKey('id');
    $ids[$id_key] = $this->getDefinitionFromEntity($id_key);

    if ($this->isTranslationDestination()) {
      $langcode_key = $this->getKey('langcode');
      if (!$langcode_key) {
        throw new MigrateException(sprintf('The "%s" entity type does not support translations.', $this->storage->getEntityTypeId()));
      }
      $ids[$langcode_key] = $this->getDefinitionFromEntity($langcode_key);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    $empty_destinations = $row->getEmptyDestinationProperties();
    // By default, an update will be preserved.
    $rollback_action = MigrateIdMapInterface::ROLLBACK_PRESERVE;

    // Make sure we have the right translation.
    if ($this->isTranslationDestination()) {
      $property = $this->storage->getEntityType()->getKey('langcode');
      if ($row->hasDestinationProperty($property)) {
        $language = $row->getDestinationProperty($property);
        if (!$entity->hasTranslation($language)) {
          $entity->addTranslation($language);

          // We're adding a translation, so delete it on rollback.
          $rollback_action = MigrateIdMapInterface::ROLLBACK_DELETE;
        }
        $entity = $entity->getTranslation($language);
      }
    }

    // If the migration has specified a list of properties to be overwritten,
    // clone the row with an empty set of destination values, and re-add only
    // the specified properties.
    if (isset($this->configuration['overwrite_properties'])) {
      $empty_destinations = array_intersect($empty_destinations, $this->configuration['overwrite_properties']);
      $clone = $row->cloneWithoutDestination();
      foreach ($this->configuration['overwrite_properties'] as $property) {
        $clone->setDestinationProperty($property, $row->getDestinationProperty($property));
      }
      $row = $clone;
    }

    foreach ($row->getDestination() as $field_name => $values) {
      $field = $entity->$field_name;
      if ($field instanceof TypedDataInterface) {
        $field->setValue($values);
      }
    }
    foreach ($empty_destinations as $field_name) {
      $entity->$field_name = NULL;
    }

    $this->setRollbackAction($row->getIdMap(), $rollback_action);

    // We might have a different (translated) entity, so return it.
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    $bundle_key = $this->getKey('bundle');
    if ($bundle_key && empty($row->getDestinationProperty($bundle_key))) {
      if (empty($this->bundles)) {
        throw new MigrateException('Stubbing failed, no bundles available for entity type: ' . $this->storage->getEntityTypeId());
      }
      $row->setDestinationProperty($bundle_key, reset($this->bundles));
    }

    $bundle = $row->getDestinationProperty($bundle_key) ?? $this->storage->getEntityTypeId();
    // Populate any required fields not already populated.
    $fields = $this->entityFieldManager
      ->getFieldDefinitions($this->storage->getEntityTypeId(), $bundle);
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->isRequired() && is_null($row->getDestinationProperty($field_name))) {
        // Use the configured default value for this specific field, if any.
        if ($default_value = $field_definition->getDefaultValueLiteral()) {
          $values = $default_value;
        }
        else {
          /** @var \Drupal\Core\Field\FieldItemInterface $field_type_class */
          $field_type_class = $this->fieldTypeManager
            ->getPluginClass($field_definition->getType());
          $values = $field_type_class::generateSampleValue($field_definition);
          if (is_null($values)) {
            // Handle failure to generate a sample value.
            throw new MigrateException('Stubbing failed, unable to generate value for field ' . $field_name);
          }
        }

        $row->setDestinationProperty($field_name, $values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    if ($this->isTranslationDestination()) {
      // Attempt to remove the translation.
      $entity = $this->storage->load(reset($destination_identifier));
      if ($entity && $entity instanceof TranslatableInterface) {
        if ($key = $this->getKey('langcode')) {
          if (isset($destination_identifier[$key])) {
            $langcode = $destination_identifier[$key];
            if ($entity->hasTranslation($langcode)) {
              // Make sure we don't remove the default translation.
              $translation = $entity->getTranslation($langcode);
              if (!$translation->isDefaultTranslation()) {
                $entity->removeTranslation($langcode);
                $entity->setSyncing(TRUE);
                $entity->save();
              }
            }
          }
        }
      }
    }
    else {
      parent::rollback($destination_identifier);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestId() {
    $values = $this->storage->getQuery()
      ->accessCheck(FALSE)
      ->sort($this->getKey('id'), 'DESC')
      ->range(0, 1)
      ->execute();
    return (int) current($values);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $entity_type = $this->storage->getEntityType();
    // Retrieving fields from a non-fieldable content entity will return a
    // LogicException. Return an empty list of fields instead.
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      return [];
    }

    // Try to determine the bundle to use when getting fields.
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type->id());

    if (!empty($this->configuration['default_bundle'])) {
      // If the migration destination configuration specifies a default_bundle,
      // then use that.
      $bundle = $this->configuration['default_bundle'];

      if (!isset($bundle_info[$bundle])) {
        throw new MigrateException(sprintf("The default_bundle value '%s' is not a valid bundle for the destination entity type '%s'.", $bundle, $entity_type->id()));
      }
    }
    elseif (count($bundle_info) === 1) {
      // If the destination entity type has only one bundle, use that.
      $bundle = array_key_first($bundle_info);
    }

    if (isset($bundle)) {
      // If we have a bundle, get all the fields.
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type->id(), $bundle);
    }
    else {
      // Without a bundle, we can only get the base fields.
      $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type->id());
    }

    $fields = [];
    foreach ($field_definitions as $field_name => $definition) {
      $fields[$field_name] = (string) $definition->getLabel();
    }
    return $fields;
  }

}
