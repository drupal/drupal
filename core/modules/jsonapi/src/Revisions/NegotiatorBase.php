<?php

namespace Drupal\jsonapi\Revisions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Base implementation for version negotiators.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
abstract class NegotiatorBase implements VersionNegotiatorInterface {

  /**
   * The entity type manager to load the revision.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a version negotiator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the revision ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $version_argument
   *   A value used to derive a revision ID for the given entity.
   *
   * @return int
   *   The revision ID.
   *
   * @throws \Drupal\jsonapi\Revisions\VersionNotFoundException
   *   When the revision does not exist.
   * @throws \Drupal\jsonapi\Revisions\InvalidVersionIdentifierException
   *   When the revision ID is not valid.
   */
  abstract protected function getRevisionId(EntityInterface $entity, $version_argument);

  /**
   * {@inheritdoc}
   */
  public function getRevision(EntityInterface $entity, $version_argument) {
    return $this->loadRevision($entity, $this->getRevisionId($entity, $version_argument));
  }

  /**
   * Loads an entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to load a revision.
   * @param int $revision_id
   *   The revision ID to be loaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The revision or NULL if the revision does not exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function loadRevision(EntityInterface $entity, $revision_id) {
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $revision = static::ensureVersionExists($storage->loadRevision($revision_id));
    if ($revision->id() !== $entity->id()) {
      throw new VersionNotFoundException(sprintf('The requested resource does not have a version with ID %s.', $revision_id));
    }
    return $revision;
  }

  /**
   * Helper method that ensures that a version exists.
   *
   * @param int|\Drupal\Core\Entity\EntityInterface $revision
   *   A revision ID, or NULL if one was not found.
   *
   * @return int|\Drupal\Core\Entity\EntityInterface
   *   A revision or revision ID, if one was found.
   *
   * @throws \Drupal\jsonapi\Revisions\VersionNotFoundException
   *   Thrown if the given value is NULL, meaning the requested version was not
   *   found.
   */
  protected static function ensureVersionExists($revision) {
    if (is_null($revision)) {
      throw new VersionNotFoundException();
    }
    return $revision;
  }

}
