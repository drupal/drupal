<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\PasswordItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles exporting content entities.
 *
 * @internal
 *   This API is experimental.
 */
final readonly class Exporter {

  public function __construct(
    private EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Exports a single content entity to a file.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   *
   * @return array{'_meta': array, 'default': array<array>, 'translations': array<string, array<array>>}
   *   The exported entity data.
   */
  public function export(ContentEntityInterface $entity): array {
    $event = new PreExportEvent($entity);

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

    // Default content has no history, so it doesn't make much sense to export
    // `changed` fields.
    foreach ($field_definitions as $name => $definition) {
      if ($definition->getType() === 'changed') {
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
    $metadata = new ExportMetadata($entity);

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
    // Add the metadata we've collected (e.g., dependencies) while exporting
    // this entity and its translations.
    $data['_meta'] = $metadata->get();

    return $data;
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
