<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before an entity is exported as default content.
 *
 * Subscribers to this event can attach callback functions which can be used
 * to export specific fields or field types. When exporting fields that either
 * have that name, or match that data type, callback will be called for each
 * field item with two arguments: the field item, and an object which holds
 * metadata (e.g., dependencies) about the entity being exported. The callback
 * should return an array of exported values for that field item, or NULL if the
 * item should not be exported.
 *
 * Subscribers may also mark specific fields as either not exportable, or
 * as explicitly exportable -- for example, computed fields are not normally
 * exported, but a subscriber could flag a computed field as exportable if
 * circumstances require it.
 */
final class PreExportEvent extends Event {

  /**
   * An array of export callbacks, keyed by field type.
   *
   * @var array<string, callable>
   */
  private array $callbacks = [];

  /**
   * Whether specific fields (keyed by name) should be exported or not.
   *
   * @var array<string, bool>
   */
  private array $allowList = [];

  public function __construct(
    public readonly ContentEntityInterface $entity,
    public readonly ExportMetadata $metadata,
  ) {}

  /**
   * Toggles whether a specific entity key should be exported.
   *
   * @param string $key
   *   An entity key, e.g. `uuid` or `langcode`. Can be a regular entity key, or
   *   a revision metadata key.
   * @param bool $export
   *   Whether to export the entity key, even if it is computed.
   */
  public function setEntityKeyExportable(string $key, bool $export = TRUE): void {
    $entity_type = $this->entity->getEntityType();
    assert($entity_type instanceof ContentEntityTypeInterface);

    if ($entity_type->hasKey($key)) {
      $this->setExportable($entity_type->getKey($key), $export);
    }
    elseif ($entity_type->hasRevisionMetadataKey($key)) {
      $this->setExportable($entity_type->getRevisionMetadataKey($key), $export);
    }
  }

  /**
   * Toggles whether a specific field should be exported.
   *
   * @param string $name
   *   The name of the field.
   * @param bool $export
   *   Whether to export the field, even if it is computed.
   */
  public function setExportable(string $name, bool $export = TRUE): void {
    $this->allowList[$name] = $export;
  }

  /**
   * Returns a map of which fields should be exported.
   *
   * @return bool[]
   *   An array whose keys are field names, and the values are booleans
   *   indicating whether the field should be exported, even if it is computed.
   */
  public function getAllowList(): array {
    return $this->allowList;
  }

  /**
   * Sets the export callback for a specific field name or data type.
   *
   * @param string $name_or_data_type
   *   A field name or field item data type, like `field_item:image`. If the
   *   callback should run for every field a given type, this should be prefixed
   *   with `field_item:`, which is the Typed Data prefix for field items. If
   *   there is no prefix, this is treated as a field name.
   * @param callable $callback
   *   The callback which should export items of the specified field type. See
   *   the class documentation for details.
   */
  public function setCallback(string $name_or_data_type, callable $callback): void {
    $this->callbacks[$name_or_data_type] = $callback;
  }

  /**
   * Returns the field export callbacks collected by this event.
   *
   * @return callable[]
   *   The export callbacks, keyed by field type.
   */
  public function getCallbacks(): array {
    return $this->callbacks;
  }

}
