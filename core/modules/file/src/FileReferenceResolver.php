<?php

declare(strict_types=1);

namespace Drupal\file;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Retrieves file references.
 */
class FileReferenceResolver {

  /**
   * The number of revisions that are queried and returned per entity type.
   */
  protected const int REVISION_LOOKUP_LIMIT = 50;

  /**
   * File reference field information, keyed by entity type and bundle.
   *
   * @var array
   */
  protected array $fileFields = [];

  /**
   * Stores the field column that references the file, keyed by file type.
   *
   * @var array
   */
  protected array $fieldColumns = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'cache.memory')]
    protected MemoryCacheInterface $memoryCache,
    protected FileUsageInterface $fileUsage,
  ) {

  }

  /**
   * Retrieves a list of references to a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return \Generator<int, \Drupal\file\FileReferenceUsage>
   *   This yields FileReferenceUsage objects.
   *
   * @ingroup file
   */
  public function getReferences(FileInterface $file): \Generator {
    $cid = 'file_references:' . $file->id();
    $cache = $this->memoryCache->get($cid);
    if ($cache) {
      return yield from $cache->data;
    }

    $references = [];

    $cacheability_metadata = new CacheableMetadata();
    $cacheability_metadata->addCacheTags(['file_references']);
    $cacheability_metadata->addCacheableDependency($file);

    $revision_reference_count = [];

    // Loop over all usages registered by the file module.
    $usages_by_file_module = $this->fileUsage->listUsage($file)['file'] ?? [];
    foreach ($usages_by_file_module as $entity_type_id => $entity_ids) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entities = $storage->loadMultiple(array_keys($entity_ids));

      $revision_reference_count[$entity_type_id] = 0;

      /** @var \Drupal\Core\Entity\FieldableEntityInterface[] $entities */
      foreach ($entities as $entity) {
        $cacheability_metadata->addCacheableDependency($entity);

        // If there are no file reference fields then this is a stale reference
        // ignore it.
        $file_reference_fields = $this->getFileReferenceFields($entity);
        if (!$file_reference_fields) {
          continue;
        }

        $default_revision_match = FALSE;
        foreach ($file_reference_fields as $field_name => $field_column) {
          if ($this->isFileReferencedByField($entity, $file, $field_name, $field_column)) {
            $default_revision_match = TRUE;
            $references[] = new FileReferenceUsage($entity->getEntityTypeId(), $field_name, id: $entity->id());
          }
        }

        if (!$default_revision_match && $entity_type->isRevisionable() && $storage instanceof RevisionableStorageInterface) {
          // Only attempt to find a limited amount of revisions per entity type,
          // very few sites should hit this limit, if they do, they will
          // need to override this with a different, if possible optimized
          // implementation.
          if ($revision_reference_count[$entity_type_id] > static::REVISION_LOOKUP_LIMIT) {
            continue;
          }
          $revision_reference_count[$entity_type_id]++;

          // The file reference was not found in any field in the current
          // entity, it may be referenced in a non-default revision. At this
          // point, the possible fields are known, create a query that fetches
          // the most recent revision that references the given file in any of
          // those fields.
          $revision = $this->getReferencingRevision($entity, $file);
          if ($revision === NULL) {
            continue;
          }

          $cacheability_metadata->addCacheableDependency($revision);
          foreach ($this->getFileReferenceFields($entity) as $field_name => $field_column) {
            if ($this->isFileReferencedByField($revision, $file, $field_name, $field_column)) {
              $references[] = new FileReferenceUsage($revision->getEntityTypeId(), $field_name, revisionId: $revision->getRevisionId());
            }
          }
        }
      }
    }
    $this->memoryCache->set($cid, $references, tags: $cacheability_metadata->getCacheTags());
    return yield from $references;
  }

  /**
   * Loads an entity from the passed usage definition.
   *
   * @param \Drupal\file\FileReferenceUsage $usage
   *   The file reference usage with either ID or revision ID set.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity using the file.
   */
  public function loadEntityFromUsage(FileReferenceUsage $usage): FieldableEntityInterface {
    $storage = $this->entityTypeManager->getStorage($usage->entityTypeId);
    if ($usage->id) {
      return $storage->load($usage->id);
    }
    else {
      assert($storage instanceof RevisionableStorageInterface);
      return $storage->loadRevision($usage->revisionId);
    }
  }

  /**
   * Determine whether a field references files stored in {file_managed}.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   A field definition.
   *
   * @return string|false
   *   The field column if the field references {file_managed}.fid, typically
   *   fid, FALSE if it does not.
   */
  protected function findFileReferenceColumns(FieldDefinitionInterface $field): string|false {
    $schema = $field->getFieldStorageDefinition()->getSchema();
    foreach ($schema['foreign keys'] as $data) {
      if ($data['table'] == 'file_managed') {
        foreach ($data['columns'] as $field_column => $column) {
          if ($column == 'fid') {
            return $field_column;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns file fields for this entity type and bundle.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to return field information for.
   *
   * @return array<string, string>
   *   List of field columns keyed by field name.
   */
  protected function getFileReferenceFields(FieldableEntityInterface $entity): array {

    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    if (!isset($this->fileFields[$entity_type_id][$bundle])) {
      $this->fileFields[$entity_type_id][$bundle] = [];
      // This contains the possible field names.
      foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
        // If this is the first time this field type is seen, check
        // whether it references files.
        if (!isset($this->fieldColumns[$field_definition->getType()])) {
          $this->fieldColumns[$field_definition->getType()] = $this->findFileReferenceColumns($field_definition);
        }
        // If the field type does reference files then record it.
        if ($this->fieldColumns[$field_definition->getType()]) {
          $this->fileFields[$entity_type_id][$bundle][$field_name] = $this->fieldColumns[$field_definition->getType()];
        }
      }
    }

    return $this->fileFields[$entity_type_id][$bundle];
  }

  /**
   * Returns the most recent revision of a specific entity referencing the file.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to do the query for.
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   A revision referencing that file, if there is any.
   */
  protected function getReferencingRevision(FieldableEntityInterface $entity, FileInterface $file): ?FieldableEntityInterface {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    assert($storage instanceof RevisionableStorageInterface);
    $entity_type = $this->entityTypeManager->getDefinition($entity->getEntityTypeId());
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id());

    $fields_condition = $query->orConditionGroup();
    foreach ($this->getFileReferenceFields($entity) as $field_name => $field_column) {
      $fields_condition->condition($field_name . '.' . $field_column, $file->id());
    }
    $results = $query
      ->condition($fields_condition)
      ->sort($entity_type->getKey('revision'), 'DESC')
      ->range(0, 1)
      ->execute();

    if ($results) {
      return $storage->loadRevision(key($results));
    }
    return NULL;
  }

  /**
   * Returns whether the file is referenced by the given field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\file\FileInterface $file
   *   The file to look for.
   * @param string $field_name
   *   The field name.
   * @param string $field_column
   *   The field column/property.
   *
   * @return bool
   *   True if the file is referenced, false if not.
   */
  protected function isFileReferencedByField(FieldableEntityInterface $entity, FileInterface $file, string $field_name, string $field_column): bool {
    // Iterate over the field items to find the referenced file and
    // field name. We also iterate over all translations because a file
    // can be linked to a language other than the default.
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      foreach ($entity->getTranslation($langcode)->get($field_name) as $item) {
        if ($file->id() == $item->{$field_column}) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
