<?php

namespace Drupal\jsonapi\Revisions;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;

/**
 * Provides a version negotiator manager.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\Revisions\VersionNegotiatorInterface
 */
class VersionNegotiator {

  /**
   * The separator between the version negotiator name and the version argument.
   *
   * @var string
   */
  const SEPARATOR = ':';

  /**
   * An array of named version negotiators.
   *
   * @var \Drupal\jsonapi\Revisions\VersionNegotiatorInterface[]
   */
  protected $negotiators = [];

  /**
   * Adds a version negotiator.
   *
   * @param \Drupal\jsonapi\Revisions\VersionNegotiatorInterface $version_negotiator
   *   The version negotiator.
   * @param string $negotiator_name
   *   The name of the negotiation strategy used by the version negotiator.
   */
  public function addVersionNegotiator(VersionNegotiatorInterface $version_negotiator, $negotiator_name) {
    assert(strpos(get_class($version_negotiator), 'Drupal\\jsonapi\\') === 0, 'Version negotiators are not a public API.');
    $this->negotiators[$negotiator_name] = $version_negotiator;
  }

  /**
   * Gets a negotiated entity revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $resource_version_identifier
   *   A value used to derive a revision for the given entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The loaded revision.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableNotFoundHttpException
   *   When the revision does not exist.
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   *   When the revision ID cannot be negotiated.
   */
  public function getRevision(EntityInterface $entity, $resource_version_identifier) {
    try {
      list($version_negotiator_name, $version_argument) = explode(VersionNegotiator::SEPARATOR, $resource_version_identifier, 2);
      if (!isset($this->negotiators[$version_negotiator_name])) {
        static::throwBadRequestHttpException($resource_version_identifier);
      }
      return $this->negotiators[$version_negotiator_name]->getRevision($entity, $version_argument);
    }
    catch (VersionNotFoundException $exception) {
      static::throwNotFoundHttpException($entity, $resource_version_identifier);
    }
    catch (InvalidVersionIdentifierException $exception) {
      static::throwBadRequestHttpException($resource_version_identifier);
    }
  }

  /**
   * Throws a cacheable error exception.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which a revision was requested.
   * @param string $resource_version_identifier
   *   The user input for the revision negotiation.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableNotFoundHttpException
   */
  protected static function throwNotFoundHttpException(EntityInterface $entity, $resource_version_identifier) {
    $cacheability = CacheableMetadata::createFromObject($entity)->addCacheContexts(['url.path', 'url.query_args:' . ResourceVersionRouteEnhancer::RESOURCE_VERSION_QUERY_PARAMETER]);
    $reason = sprintf('The requested version, identified by `%s`, could not be found.', $resource_version_identifier);
    throw new CacheableNotFoundHttpException($cacheability, $reason);
  }

  /**
   * Throws a cacheable error exception.
   *
   * @param string $resource_version_identifier
   *   The user input for the revision negotiation.
   *
   * @throws \Drupal\Core\Http\Exception\CacheableBadRequestHttpException
   */
  protected static function throwBadRequestHttpException($resource_version_identifier) {
    $cacheability = (new CacheableMetadata())->addCacheContexts(['url.query_args:' . ResourceVersionRouteEnhancer::RESOURCE_VERSION_QUERY_PARAMETER]);
    $message = sprintf('An invalid resource version identifier, `%s`, was provided.', $resource_version_identifier);
    throw new CacheableBadRequestHttpException($cacheability, $message);
  }

}
