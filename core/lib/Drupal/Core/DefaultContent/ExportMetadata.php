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

  /**
   * Files that should accompany the exported entity.
   *
   * @var array<string, string>
   *
   * @see ::getAttachments()
   */
  private array $attachments = [];

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

  /**
   * Returns the dependencies of the exported entity.
   *
   * @return string[][]
   *   An array of dependencies, where each dependency is a tuple with two
   *   elements: an entity type ID, and a UUID.
   */
  public function getDependencies(): array {
    $dependencies = [];
    foreach ($this->metadata['depends'] ?? [] as $uuid => $entity_type_id) {
      $dependencies[] = [$entity_type_id, $uuid];
    }
    return $dependencies;
  }

  /**
   * Attaches a file to the exported entity.
   *
   * @param string $uri
   *   The URI of the file, which may or may not physically exist.
   * @param string $name
   *   The name of the exported file.
   */
  public function addAttachment(string $uri, string $name): void {
    $this->attachments[$uri] = $name;
  }

  /**
   * Returns the files attached to this entity.
   *
   * @return array<string, string>
   *   The keys are the files' current URIs, and the values are the names of the
   *   files when they are exported.
   */
  public function getAttachments(): array {
    return $this->attachments;
  }

}
