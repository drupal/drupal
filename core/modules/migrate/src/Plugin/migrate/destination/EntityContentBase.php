<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\migrate\Audit\HighestIdInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides destination class for all content entities lacking a specific class.
 *
 * Available configuration keys:
 * - translations: (optional) Boolean, indicates if the entity is translatable,
 *   defaults to FALSE.
 * - overwrite_properties: (optional) A list of properties that will be
 *   overwritten if an entity with the same ID already exists. Any properties
 *   that are not listed will not be overwritten.
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
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\EntityRevision
 */
class EntityContentBase extends Entity implements HighestIdInterface {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

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
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles);
    $this->entityManager = $entity_manager;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type)),
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;
    $entity = $this->getEntity($row, $old_destination_id_values);
    if (!$entity) {
      throw new MigrateException('Unable to get entity');
    }

    $ids = $this->save($entity, $old_destination_id_values);
    if (!empty($this->configuration['translations'])) {
      $ids[] = $entity->language()->getId();
    }
    return $ids;
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
    $id_key = $this->getKey('id');
    $ids[$id_key] = $this->getDefinitionFromEntity($id_key);

    if ($this->isTranslationDestination()) {
      if (!$langcode_key = $this->getKey('langcode')) {
        throw new MigrateException('This entity type does not support translation.');
      }
      $ids[$langcode_key] = $this->getDefinitionFromEntity($langcode_key);
    }

    return $ids;
  }

  /**
   * Updates an entity with the new values from row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An updated entity, or NULL if it's the same as the one passed in.
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
   * Populates as much of the stub row as possible.
   *
   * @param \Drupal\migrate\Row $row
   *   The row of data.
   */
  protected function processStubRow(Row $row) {
    $bundle_key = $this->getKey('bundle');
    if ($bundle_key && empty($row->getDestinationProperty($bundle_key))) {
      if (empty($this->bundles)) {
        throw new MigrateException('Stubbing failed, no bundles available for entity type: ' . $this->storage->getEntityTypeId());
      }
      $row->setDestinationProperty($bundle_key, reset($this->bundles));
    }

    // Populate any required fields not already populated.
    $fields = $this->entityManager
      ->getFieldDefinitions($this->storage->getEntityTypeId(), $bundle_key);
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->isRequired() && is_null($row->getDestinationProperty($field_name))) {
        // Use the configured default value for this specific field, if any.
        if ($default_value = $field_definition->getDefaultValueLiteral()) {
          $values = $default_value;
        }
        else {
          // Otherwise, ask the field type to generate a sample value.
          $field_type = $field_definition->getType();
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
   * Gets the field definition from a specific entity base field.
   *
   * The method takes the field ID as an argument and returns the field storage
   * definition to be used in getIds() by querying the destination entity base
   * field definition.
   *
   * @param string $key
   *   The field ID key.
   *
   * @return array
   *   An associative array with a structure that contains the field type, keyed
   *   as 'type', together with field storage settings as they are returned by
   *   FieldStorageDefinitionInterface::getSettings().
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface::getSettings()
   */
  protected function getDefinitionFromEntity($key) {
    $entity_type_id = static::getEntityTypeId($this->getPluginId());
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $definitions */
    $definitions = $this->entityManager->getBaseFieldDefinitions($entity_type_id);
    $field_definition = $definitions[$key];

    return [
      'type' => $field_definition->getType(),
    ] + $field_definition->getSettings();
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

}
