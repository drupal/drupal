<?php

namespace Drupal\jsonapi\Revisions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;

/**
 * Revision ID implementation for the default or latest revisions.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class VersionByRel extends NegotiatorBase {

  /**
   * Version argument which loads the revision known to be the "working copy".
   *
   * In Drupal terms, a "working copy" is the latest revision. It may or may not
   * be a "default" revision. This revision is the working copy because it is
   * the revision to which new work will be applied. In other words, it denotes
   * the most recent revision which might be considered a work-in-progress.
   *
   * @var string
   */
  const WORKING_COPY = 'working-copy';

  /**
   * Version argument which loads the revision known to be the "latest version".
   *
   * In Drupal terms, the "latest version" is the latest "default" revision. It
   * may or may not have later revisions after it, as long as none of them are
   * "default" revisions. This revision is the latest version because it is the
   * last revision where work was considered finished. Typically, this means
   * that it is the most recent "published" revision.
   *
   * @var string
   */
  const LATEST_VERSION = 'latest-version';

  /**
   * {@inheritdoc}
   */
  protected function getRevisionId(EntityInterface $entity, $version_argument) {
    assert($entity instanceof RevisionableInterface);
    switch ($version_argument) {
      case static::WORKING_COPY:
        /* @var \Drupal\Core\Entity\RevisionableStorageInterface $entity_storage */
        $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        return static::ensureVersionExists($entity_storage->getLatestRevisionId($entity->id()));

      case static::LATEST_VERSION:
        // The already loaded revision will be the latest version by default.
        // @see \Drupal\Core\Entity\Sql\SqlContentEntityStorage::buildQuery().
        return $entity->getLoadedRevisionId();

      default:
        $message = sprintf('The version specifier must be either `%s` or `%s`, `%s` given.', static::LATEST_VERSION, static::WORKING_COPY, $version_argument);
        throw new InvalidVersionIdentifierException($message);
    }
  }

}
