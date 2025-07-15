<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Collects metadata about an entity being exported.
 *
 * @internal
 *   This API is experimental.
 */
final class ExportMetadata {

  /**
   * The collected export metadata.
   */
  private array $metadata = ['version' => '1.0'];

  public function __construct(ContentEntityInterface $entity) {
    $this->metadata['entity_type'] = $entity->getEntityTypeId();
    $this->metadata['uuid'] = $entity->uuid();

    $entity_type = $entity->getEntityType();
    if ($entity_type->hasKey('bundle')) {
      $this->metadata['bundle'] = $entity->bundle();
    }
    if ($entity_type->hasKey('langcode')) {
      $this->metadata['default_langcode'] = $entity->language()->getId();
    }
  }

  /**
   * Returns the collected metadata as an array.
   *
   * @return array
   *   The collected export metadata.
   */
  public function get(): array {
    return $this->metadata;
  }

  /**
   * Adds a dependency on another content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity we depend upon.
   */
  public function addDependency(ContentEntityInterface $entity): void {
    $uuid = $entity->uuid();
    if ($uuid === $this->metadata['uuid']) {
      throw new \LogicException('An entity cannot depend on itself.');
    }
    $this->metadata['depends'][$uuid] = $entity->getEntityTypeId();
  }

}
