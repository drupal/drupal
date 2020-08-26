<?php

namespace Drupal\jsonapi\Revisions;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Drupal\Core\Http\Exception\CacheableHttpException;
use Drupal\Core\Routing\EnhancerInterface;
use Drupal\jsonapi\Routing\Routes;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Loads an appropriate revision for the requested resource version.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
final class ResourceVersionRouteEnhancer implements EnhancerInterface {

  /**
   * The route default parameter name.
   *
   * @var string
   */
  const REVISION_ID_KEY = 'revision_id';

  /**
   * The query parameter for providing a version (revision) value.
   *
   * @var string
   */
  const RESOURCE_VERSION_QUERY_PARAMETER = 'resourceVersion';

  /**
   * A route parameter key which indicates that working copies were requested.
   *
   * @var string
   */
  const WORKING_COPIES_REQUESTED = 'working_copies_requested';

  /**
   * The cache context by which vary the loaded entity revision.
   *
   * @var string
   *
   * @todo When D8 requires PHP >=5.6, convert to expression using the RESOURCE_VERSION_QUERY_PARAMETER constant.
   */
  const CACHE_CONTEXT = 'url.query_args:resourceVersion';

  /**
   * Resource version validation regex.
   *
   * @var string
   *
   * @todo When D8 requires PHP >=5.6, convert to expression using the VersionNegotiator::SEPARATOR constant.
   */
  const VERSION_IDENTIFIER_VALIDATOR = '/^[a-z]+[a-z_]*[a-z]+:[a-zA-Z0-9\-]+(:[a-zA-Z0-9\-]+)*$/';

  /**
   * The revision ID negotiator.
   *
   * @var \Drupal\jsonapi\Revisions\VersionNegotiator
   */
  protected $versionNegotiator;

  /**
   * ResourceVersionRouteEnhancer constructor.
   *
   * @param \Drupal\jsonapi\Revisions\VersionNegotiator $version_negotiator_manager
   *   The version negotiator.
   */
  public function __construct(VersionNegotiator $version_negotiator_manager) {
    $this->versionNegotiator = $version_negotiator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!Routes::isJsonApiRequest($defaults) || !($resource_type = Routes::getResourceTypeNameFromParameters($defaults))) {
      return $defaults;
    }

    $has_version_param = $request->query->has(static::RESOURCE_VERSION_QUERY_PARAMETER);

    // If the resource type is not versionable, then nothing needs to be
    // enhanced.
    if (!$resource_type->isVersionable()) {
      // If the query parameter was provided but the resource type is not
      // versionable, provide a helpful error.
      if ($has_version_param) {
        // Until Drupal core has a generic revision access API, it is only safe
        // to support the `node` and `media` entity types because they are the
        // only // entity types that have revision access checks for forward
        // revisions that are not the default and not the latest revision.
        $cacheability = (new CacheableMetadata())->addCacheContexts(['url.path', static::CACHE_CONTEXT]);
        /* Uncomment the next line and remove the following one when https://www.drupal.org/project/drupal/issues/3002352 lands in core. */
        /* throw new CacheableHttpException($cacheability, 501, 'Resource versioning is not yet supported for this resource type.'); */
        $message = 'JSON:API does not yet support resource versioning for this resource type.';
        $message .= ' For context, see https://www.drupal.org/project/drupal/issues/2992833#comment-12818258.';
        $message .= ' To contribute, see https://www.drupal.org/project/drupal/issues/2350939 and https://www.drupal.org/project/drupal/issues/2809177.';
        throw new CacheableHttpException($cacheability, 501, $message, NULL, []);
      }
      return $defaults;
    }

    // Since the resource type is versionable, responses must always vary by the
    // requested version, without regard for whether a version query parameter
    // was provided or not.
    if (isset($defaults['entity'])) {
      assert($defaults['entity'] instanceof EntityInterface);
      $defaults['entity']->addCacheContexts([static::CACHE_CONTEXT]);
    }

    // If no version was specified, nothing is left to enhance.
    if (!$has_version_param) {
      return $defaults;
    }

    // Provide a helpful error when a version is specified with an unsafe
    // method.
    if (!$request->isMethodCacheable()) {
      throw new BadRequestHttpException(sprintf('%s requests with a `%s` query parameter are not supported.', $request->getMethod(), static::RESOURCE_VERSION_QUERY_PARAMETER));
    }

    $resource_version_identifier = $request->query->get(static::RESOURCE_VERSION_QUERY_PARAMETER);

    if (!static::isValidVersionIdentifier($resource_version_identifier)) {
      $cacheability = (new CacheableMetadata())->addCacheContexts([static::CACHE_CONTEXT]);
      $message = sprintf('A resource version identifier was provided in an invalid format: `%s`', $resource_version_identifier);
      throw new CacheableBadRequestHttpException($cacheability, $message);
    }

    // Determine if the request is for a collection resource.
    if ($defaults[RouteObjectInterface::CONTROLLER_NAME] === Routes::CONTROLLER_SERVICE_NAME . ':getCollection') {
      $latest_version_identifier = 'rel' . VersionNegotiator::SEPARATOR . 'latest-version';
      $working_copy_identifier = 'rel' . VersionNegotiator::SEPARATOR . 'working-copy';
      // Until Drupal core has a revision access API that works on entity
      // queries, filtering is not permitted on non-default revisions.
      if ($request->query->has('filter') && $resource_version_identifier !== $latest_version_identifier) {
        $cache_contexts = [
          'url.path',
          static::CACHE_CONTEXT,
          'url.query_args:filter',
        ];
        $cacheability = (new CacheableMetadata())->addCacheContexts($cache_contexts);
        $message = 'JSON:API does not support filtering on revisions other than the latest version because a secure Drupal core API does not yet exist to do so.';
        throw new CacheableHttpException($cacheability, 501, $message, NULL, []);
      }
      // 'latest-version' and 'working-copy' are the only acceptable version
      // identifiers for a collection resource.
      if (!in_array($resource_version_identifier, [$latest_version_identifier, $working_copy_identifier])) {
        $cacheability = (new CacheableMetadata())->addCacheContexts(['url.path', static::CACHE_CONTEXT]);
        $message = sprintf('Collection resources only support the following resource version identifiers: %s', implode(', ', [
          $latest_version_identifier,
          $working_copy_identifier,
        ]));
        throw new CacheableBadRequestHttpException($cacheability, $message);
      }
      // Whether the collection to be loaded should include only working copies.
      $defaults[static::WORKING_COPIES_REQUESTED] = $resource_version_identifier === $working_copy_identifier;
      return $defaults;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $defaults['entity'];

    /** @var \Drupal\jsonapi\Revisions\VersionNegotiatorInterface $negotiator */
    $resolved_revision = $this->versionNegotiator->getRevision($entity, $resource_version_identifier);
    // Ensure none of the original entity cacheability is lost, especially the
    // query argument's cache context.
    $resolved_revision->addCacheableDependency($entity);
    return ['entity' => $resolved_revision] + $defaults;
  }

  /**
   * Validates the user input.
   *
   * @param string $resource_version
   *   The requested resource version identifier.
   *
   * @return bool
   *   TRUE if the received resource version value is valid, FALSE otherwise.
   */
  protected static function isValidVersionIdentifier($resource_version) {
    return preg_match(static::VERSION_IDENTIFIER_VALIDATOR, $resource_version) === 1;
  }

}
