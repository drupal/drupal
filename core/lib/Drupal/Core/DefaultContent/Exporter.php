<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\PasswordItem;
use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles exporting content entities.
 *
 * @internal
 *   This API is experimental.
 */
final class Exporter implements LoggerAwareInterface {

  use LoggerAwareTrait;

  public function __construct(
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly FileSystemInterface $fileSystem,
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Exports a single content entity as an array.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   *
   * @return \Drupal\Core\DefaultContent\ExportResult
   *   A read-only value object with the exported entity data, and any metadata
   *   that was collected while exporting the entity, including dependencies and
   *   attachments.
   */
  public function export(ContentEntityInterface $entity): ExportResult {
    $metadata = new ExportMetadata($entity);
    $event = new PreExportEvent($entity, $metadata);

    $field_definitions = $entity->getFieldDefinitions();
    // Ignore serial (integer) entity IDs by default, along with a number of
    // other keys that aren't useful for default content.
    $id_key = $entity->getEntityType()->getKey('id');
    if ($id_key && $field_definitions[$id_key]->getType() === 'integer') {
      $event->setEntityKeyExportable('id', FALSE);
    }
    $event->setEntityKeyExportable('uuid', FALSE);
    $event->setEntityKeyExportable('revision', FALSE);
    $event->setEntityKeyExportable('langcode', FALSE);
    $event->setEntityKeyExportable('bundle', FALSE);
    $event->setEntityKeyExportable('default_langcode', FALSE);
    $event->setEntityKeyExportable('revision_default', FALSE);
    $event->setEntityKeyExportable('revision_created', FALSE);

    // Ignore fields that don't make sense in default content:
    // - `changed` fields aren't needed because default content has no history.
    // - `created` fields aren't needed because default content should be
    //   "created" upon import.
    foreach ($field_definitions as $name => $definition) {
      if (in_array($definition->getType(), ['changed', 'created'], TRUE)) {
        $event->setExportable($name, FALSE);
      }
    }
    // Exported user accounts should include the hashed password.
    $event->setCallback('field_item:password', function (PasswordItem $item): array {
      return $item->set('pre_hashed', TRUE)->getValue();
    });
    // Ensure that all entity reference fields mark the referenced entity as a
    // dependency of the entity being exported.
    $event->setCallback('field_item:entity_reference', $this->exportReference(...));
    $event->setCallback('field_item:file', $this->exportReference(...));
    $event->setCallback('field_item:image', $this->exportReference(...));

    // Dispatch the event so modules can add and customize export callbacks, and
    // mark certain fields as ignored.
    $this->eventDispatcher->dispatch($event);

    $data = [];
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $values = $this->exportTranslation($translation, $metadata, $event->getCallbacks(), $event->getAllowList());

      if ($translation->isDefaultTranslation()) {
        $data['default'] = $values;
      }
      else {
        $data['translations'][$langcode] = $values;
      }
    }
    return new ExportResult($data, $metadata);
  }

  /**
   * Exports an entity to a YAML file in a directory.
   *
   * Any attachments to the entity (e.g., physical files) will be copied into
   * the destination directory, alongside the exported entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   * @param string $destination
   *   A destination path or URI; will be created if it does not exist. A
   *   subdirectory will be created for the entity type that is being exported.
   *
   * @return \Drupal\Core\DefaultContent\ExportResult
   *   The exported entity data and its metadata.
   */
  public function exportToFile(ContentEntityInterface $entity, string $destination): ExportResult {
    $destination .= '/' . $entity->getEntityTypeId();

    // Ensure the destination directory exists and is writable.
    $this->fileSystem->prepareDirectory(
      $destination,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
    ) || throw new DirectoryNotReadyException("Could not create destination directory '$destination'");

    $destination = $this->fileSystem->realpath($destination);
    if (empty($destination)) {
      throw new FileException("Could not resolve the destination directory '$destination'");
    }

    $path = $destination . '/' . $entity->uuid() . '.' . Yaml::getFileExtension();
    $result = $this->export($entity);
    file_put_contents($path, (string) $result) || throw new FileWriteException("Could not write file '$path'");

    foreach ($result->metadata->getAttachments() as $from => $to) {
      $this->fileSystem->copy($from, $destination . '/' . $to, FileExists::Replace);
    }
    return $result;
  }

  /**
   * Exports an entity and all of its dependencies to a directory.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   * @param string $destination
   *   A destination path or URI; will be created if it does not exist.
   *   Subdirectories will be created for each entity type that is exported.
   *
   * @return int
   *   The number of entities that were exported.
   */
  public function exportWithDependencies(ContentEntityInterface $entity, string $destination): int {
    $queue = [$entity];
    $done = [];

    while ($queue) {
      $entity = array_shift($queue);
      $uuid = $entity->uuid();
      // Don't export the same entity twice, both for performance and to prevent
      // an infinite loop caused by circular dependencies.
      if (isset($done[$uuid])) {
        continue;
      }

      $dependencies = $this->exportToFile($entity, $destination)->metadata->getDependencies();
      foreach ($dependencies as $dependency) {
        $dependency = $this->entityRepository->loadEntityByUuid(...$dependency);
        if ($dependency instanceof ContentEntityInterface) {
          $queue[] = $dependency;
        }
      }
      $done[$uuid] = TRUE;
    }
    return count($done);
  }

  /**
   * Exports a single translation of a content entity.
   *
   * Any fields that are explicitly marked non-exportable (including computed
   * properties by default) will not be exported.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   *   The translation to export.
   * @param \Drupal\Core\DefaultContent\ExportMetadata $metadata
   *   Any metadata about the entity being exported (e.g., dependencies).
   * @param callable[] $callbacks
   *   Custom export functions for specific field types, keyed by field type.
   * @param array<string, bool> $allow_list
   *   An array of booleans that indicate whether a specific field should be
   *   exported or not, even if it is computed. Keyed by field name.
   *
   * @return array
   *   The exported translation.
   */
  private function exportTranslation(ContentEntityInterface $translation, ExportMetadata $metadata, array $callbacks, array $allow_list): array {
    $data = [];

    foreach ($translation->getFields() as $name => $items) {
      // Skip the field if it's empty, or it was explicitly disallowed, or is a
      // computed field that wasn't explicitly allowed.
      $allowed = $allow_list[$name] ?? NULL;
      if ($allowed === FALSE || ($allowed === NULL && $items->getDataDefinition()->isComputed()) || $items->isEmpty()) {
        continue;
      }

      // Try to find a callback for this specific field, then for the field's
      // data type, and finally fall back to a generic callback.
      $data_type = $items->getFieldDefinition()
        ->getItemDefinition()
        ->getDataType();
      $callback = $callbacks[$name] ?? $callbacks[$data_type] ?? $this->exportFieldItem(...);

      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      foreach ($items as $item) {
        $values = $callback($item, $metadata);
        // If the callback returns NULL, this item should not be exported.
        if (is_array($values)) {
          $data[$name][] = $values;
        }
      }
    }
    return $data;
  }

  /**
   * Exports a single field item generically.
   *
   * Any properties of the item that are explicitly marked non-exportable (which
   * includes computed properties by default) will not be exported.
   *
   * Field types that need special handling should provide a custom callback
   * function to the exporter by subscribing to
   * \Drupal\Core\DefaultContent\PreExportEvent.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item to export.
   *
   * @return array
   *   The exported field values.
   *
   * @see \Drupal\Core\DefaultContent\PreExportEvent::setCallback()
   */
  private function exportFieldItem(FieldItemInterface $item): array {
    $custom_serialized = Importer::getCustomSerializedPropertyNames($item);

    $values = [];
    foreach ($item->getProperties() as $name => $property) {
      $value = $property instanceof PrimitiveInterface ? $property->getCastedValue() : $property->getValue();

      if (is_string($value) && in_array($name, $custom_serialized, TRUE)) {
        $value = unserialize($value);
      }
      $values[$name] = $value;
    }
    return $values;
  }

  /**
   * Exports an entity reference field item.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface&\Drupal\Core\Field\FieldItemInterface $item
   *   The field item to export.
   * @param \Drupal\Core\DefaultContent\ExportMetadata $metadata
   *   Any metadata about the entity being exported (e.g., dependencies).
   *
   * @return array|null
   *   The exported field values, or NULL if no entity is referenced and the
   *   item should not be exported.
   */
  private function exportReference(EntityReferenceItemInterface&FieldItemInterface $item, ExportMetadata $metadata): ?array {
    $entity = $item->get('entity')->getValue();
    // No entity is referenced, so there's nothing else we can do here.
    if ($entity === NULL) {
      $referencer = $item->getEntity();
      $field_definition = $item->getFieldDefinition();
      $this->logger?->warning('Failed to export reference to @target_type %missing_id referenced by %field on @entity_type %label because the referenced @target_type does not exist.', [
        '@target_type' => (string) $this->entityTypeManager->getDefinition($field_definition->getFieldStorageDefinition()->getSetting('target_type'))->getSingularLabel(),
        '%missing_id' => $item->get('target_id')->getValue(),
        '%field' => $field_definition->getLabel(),
        '@entity_type' => (string) $referencer->getEntityType()->getSingularLabel(),
        '%label' => $referencer->label(),
      ]);
      return NULL;
    }
    $values = $this->exportFieldItem($item);

    if ($entity instanceof ContentEntityInterface) {
      // If the referenced entity is user 0 or 1, we can skip further
      // processing because user 0 is guaranteed to exist, and user 1 is
      // guaranteed to have existed at some point. Either way, there's no chance
      // of accidentally referencing the wrong entity on import.
      if ($entity instanceof AccountInterface && intval($entity->id()) < 2) {
        return array_map('intval', $values);
      }
      // Mark the referenced entity as a dependency of the one we're exporting.
      $metadata->addDependency($entity);

      $entity_type = $entity->getEntityType();
      // If the referenced entity ID is numeric, refer to it by UUID, which is
      // portable. If the ID isn't numeric, assume it's meant to be consistent
      // (like a config entity ID) and leave the reference as-is. Workspaces
      // are an example of an entity type that should be treated this way.
      if ($entity_type->hasKey('id') && $entity->getFieldDefinition($entity_type->getKey('id'))->getType() === 'integer') {
        $values['entity'] = $entity->uuid();
        unset($values['target_id']);
      }
    }
    return $values;
  }

}
