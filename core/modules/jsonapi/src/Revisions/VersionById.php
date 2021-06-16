<?php

namespace Drupal\jsonapi\Revisions;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a revision ID implementation for entity revision ID values.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class VersionById extends NegotiatorBase implements VersionNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  protected function getRevisionId(EntityInterface $entity, $version_argument) {
    if (!is_numeric($version_argument)) {
      throw new InvalidVersionIdentifierException('The revision ID must be an integer.');
    }
    return $version_argument;
  }

}
