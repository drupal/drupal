<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\CacheableResourceResponse;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Controller for the API entry point.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class EntryPoint extends ControllerBase {

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * EntryPoint constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, AccountInterface $user) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('current_user')
    );
  }

  /**
   * Controller to list all the resources.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response object.
   */
  public function index() {
    $cacheability = (new CacheableMetadata())
      ->addCacheContexts(['user.roles:authenticated'])
      ->addCacheTags(['jsonapi_resource_types']);

    // Only build URLs for exposed resources.
    $resources = array_filter($this->resourceTypeRepository->all(), function ($resource) {
      return !$resource->isInternal();
    });

    $self_link = new Link(new CacheableMetadata(), Url::fromRoute('jsonapi.resource_list'), 'self');
    $urls = array_reduce($resources, function (LinkCollection $carry, ResourceType $resource_type) {
      if ($resource_type->isLocatable() || $resource_type->isMutable()) {
        $route_suffix = $resource_type->isLocatable() ? 'collection' : 'collection.post';
        $url = Url::fromRoute(sprintf('jsonapi.%s.%s', $resource_type->getTypeName(), $route_suffix))->setAbsolute();
        // Using a resource type name in place of a link relation type is not
        // technically valid. However, since it matches the link key, it will
        // not actually be serialized since the rel is omitted if it matches the
        // link key; because of that no client can rely on it. Once an extension
        // relation type is implemented for links to a collection, that should
        // be used instead. Unfortunately, the `collection` link relation type
        // would not be semantically correct since it would imply that the
        // entrypoint is a *member* of the link target.
        // @todo Implement an extension relation type to signal that this is a primary collection resource.
        $link_relation_type = $resource_type->getTypeName();
        return $carry->withLink($resource_type->getTypeName(), new Link(new CacheableMetadata(), $url, $link_relation_type));
      }
      return $carry;
    }, new LinkCollection(['self' => $self_link]));

    $meta = [];
    if ($this->user->isAuthenticated()) {
      $current_user_uuid = $this->entityTypeManager()->getStorage('user')->load($this->user->id())->uuid();
      $meta['links']['me'] = ['meta' => ['id' => $current_user_uuid]];
      $cacheability->addCacheContexts(['user']);
      try {
        $me_url = Url::fromRoute(
          'jsonapi.user--user.individual',
          ['entity' => $current_user_uuid]
        )
          ->setAbsolute()
          ->toString(TRUE);
        $meta['links']['me']['href'] = $me_url->getGeneratedUrl();
        // The cacheability of the `me` URL is the cacheability of that URL
        // itself and the currently authenticated user.
        $cacheability = $cacheability->merge($me_url);
      }
      catch (RouteNotFoundException) {
        // Do not add the link if the route is disabled or marked as internal.
      }
    }

    $response = new CacheableResourceResponse(new JsonApiDocumentTopLevel(new ResourceObjectData([]), new NullIncludedData(), $urls, $meta));
    return $response->addCacheableDependency($cacheability);
  }

}
