<?php

namespace Drupal\jsonapi\Revisions;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the common interface for all version negotiators.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\Revisions\VersionNegotiator
 */
interface VersionNegotiatorInterface {

  /**
   * Gets the identified revision.
   *
   * The JSON:API module exposes revisions in terms of RFC5829. As such, the
   * public API always refers to "versions" and "working copies" instead of
   * "revisions". There are multiple ways to request a specific revision. For
   * example, one might like to load a particular revision by its ID. On the
   * other hand, it may be useful if an HTTP consumer is able to always request
   * the "latest version" regardless of its ID. It is possible to imagine other
   * scenarios as well, like fetching a revision based on a date or time.
   *
   * Each version negotiator provides one of these strategies and is able to map
   * a version argument to an existing revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which a revision should be resolved.
   * @param string $version_argument
   *   A value used to derive a revision for the given entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The identified entity revision.
   *
   * @throws \Drupal\jsonapi\Revisions\VersionNotFoundException
   *   When the revision does not exist.
   * @throws \Drupal\jsonapi\Revisions\InvalidVersionIdentifierException
   *   When the revision ID is invalid.
   */
  public function getRevision(EntityInterface $entity, $version_argument);

}
