<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\EntityFieldDefinitionTrait;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin to get content entities from the current version of Drupal.
 *
 * This plugin uses the Entity API to export entity data. If the source entity
 * type has custom field storage fields or computed fields, this class will need
 * to be extended and the new class will need to load/calculate the values for
 * those fields.
 *
 * Available configuration keys:
 * - entity_type: The entity type ID of the entities being exported. This is
 *   calculated dynamically by the deriver so it is only needed if the deriver
 *   is not utilized, i.e., a custom source plugin.
 * - bundle: (optional) If the entity type is bundleable, only return entities
 *   of this bundle.
 * - include_translations: (optional) Indicates if the entity translations
 *   should be included, defaults to TRUE.
 * - add_revision_id: (optional) Indicates if the revision key is added to the
 *   source IDs, defaults to TRUE.
 *
 * Examples:
 *
 * This will return the default revision for all nodes, from every bundle and
 * every translation. The revision key is added to the source IDs.
 * @code
 * source:
 *   plugin: content_entity:node
 * @endcode
 *
 * This will return the default revision for all nodes, from every bundle and
 * every translation. The revision key is not added to the source IDs.
 * @code
 * source:
 *   plugin: content_entity:node
 *   add_revision_id: false
 * @endcode
 *
 * This will only return nodes of type 'article' in their default language.
 * @code
 * source:
 *   plugin: content_entity:node
 *   bundle: article
 *   include_translations: false
 * @endcode
 *
 * For additional configuration keys, refer to the parent class:
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "content_entity",
 *   source_module = "migrate_drupal",
 *   deriver = "\Drupal\migrate_drupal\Plugin\migrate\source\ContentEntityDeriver",
 * )
 */
class ContentEntity extends SourcePluginBase implements ContainerFactoryPluginInterface {
  use EntityFieldDefinitionTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The plugin's default configuration.
   *
   * @var array
   */
  protected $defaultConfiguration = [
    'bundle' => NULL,
    'include_translations' => TRUE,
    'add_revision_id' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    if (empty($plugin_definition['entity_type'])) {
      throw new InvalidPluginDefinitionException($plugin_id, 'Missing required "entity_type" definition.');
    }
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityType = $this->entityTypeManager->getDefinition($plugin_definition['entity_type']);
    if (!$this->entityType instanceof ContentEntityTypeInterface) {
      throw new InvalidPluginDefinitionException($plugin_id, sprintf('The entity type (%s) is not supported. The "content_entity" source plugin only supports content entities.', $plugin_definition['entity_type']));
    }
    if (!empty($configuration['bundle'])) {
      if (!$this->entityType->hasKey('bundle')) {
        throw new \InvalidArgumentException(sprintf('A bundle was provided but the entity type (%s) is not bundleable.', $plugin_definition['entity_type']));
      }
      $bundle_info = array_keys($this->entityTypeBundleInfo->getBundleInfo($this->entityType->id()));
      if (!in_array($configuration['bundle'], $bundle_info, TRUE)) {
        throw new \InvalidArgumentException(sprintf('The provided bundle (%s) is not valid for the (%s) entity type.', $configuration['bundle'], $plugin_definition['entity_type']));
      }
    }
    parent::__construct($configuration + $this->defaultConfiguration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return (string) $this->entityType->getPluralLabel();
  }

  /**
   * Initializes the iterator with the source data.
   *
   * @return \Generator
   *   A data generator for this source.
   */
  protected function initializeIterator() {
    $ids = $this->query()->execute();
    return $this->yieldEntities($ids);
  }

  /**
   * Loads and yields entities, one at a time.
   *
   * @param array $ids
   *   The entity IDs.
   *
   * @return \Generator
   *   An iterable of the loaded entities.
   */
  protected function yieldEntities(array $ids) {
    $storage = $this->entityTypeManager
      ->getStorage($this->entityType->id());
    foreach ($ids as $id) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $storage->load($id);
      yield $this->toArray($entity);
      if ($this->configuration['include_translations']) {
        foreach ($entity->getTranslationLanguages(FALSE) as $language) {
          yield $this->toArray($entity->getTranslation($language->getId()));
        }
      }
    }
  }

  /**
   * Converts an entity to an array.
   *
   * Makes all IDs into flat values. All other values are returned as per
   * $entity->toArray(), which is a nested array.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to convert.
   *
   * @return array
   *   The entity, represented as an array.
   */
  protected function toArray(ContentEntityInterface $entity) {
    $return = $entity->toArray();
    // This is necessary because the IDs must be flat. They cannot be nested for
    // the ID map.
    foreach (array_keys($this->getIds()) as $id) {
      /** @var \Drupal\Core\TypedData\Plugin\DataType\ItemList $value */
      $value = $entity->get($id);
      // Force the IDs on top of the previous values.
      $return[$id] = $value->first()->getString();
    }
    return $return;
  }

  /**
   * Query to retrieve the entities.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query.
   */
  public function query() {
    $query = $this->entityTypeManager
      ->getStorage($this->entityType->id())
      ->getQuery()
      ->accessCheck(FALSE);
    if (!empty($this->configuration['bundle'])) {
      $query->condition($this->entityType->getKey('bundle'), $this->configuration['bundle']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    // If no translations are included, then a simple query is possible.
    if (!$this->configuration['include_translations']) {
      return parent::count($refresh);
    }
    // @TODO: Determine a better way to retrieve a valid count for translations.
    // https://www.drupal.org/project/drupal/issues/2937166
    return -1;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    return $this->query()->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Retrieving fields from a non-fieldable content entity will throw a
    // LogicException. Return an empty list of fields instead.
    if (!$this->entityType->entityClassImplements('Drupal\Core\Entity\FieldableEntityInterface')) {
      return [];
    }
    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($this->entityType->id());
    if (!empty($this->configuration['bundle'])) {
      $field_definitions += $this->entityFieldManager->getFieldDefinitions($this->entityType->id(), $this->configuration['bundle']);
    }
    $fields = array_map(function ($definition) {
      return (string) $definition->getLabel();
    }, $field_definitions);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $id_key = $this->entityType->getKey('id');
    $ids[$id_key] = $this->getDefinitionFromEntity($id_key);
    if ($this->configuration['add_revision_id'] && $this->entityType->isRevisionable()) {
      $revision_key = $this->entityType->getKey('revision');
      $ids[$revision_key] = $this->getDefinitionFromEntity($revision_key);
    }
    if ($this->entityType->isTranslatable()) {
      $langcode_key = $this->entityType->getKey('langcode');
      $ids[$langcode_key] = $this->getDefinitionFromEntity($langcode_key);
    }
    return $ids;
  }

}
