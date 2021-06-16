<?php

namespace Drupal\jsonapi\Access;

use Drupal\content_moderation\Access\LatestRevisionCheck;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\media\Access\MediaRevisionAccessCheck;
use Drupal\media\MediaInterface;
use Drupal\node\Access\NodeRevisionAccessCheck;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Checks access to entities.
 *
 * JSON:API needs to check access to every single entity type. Some entity types
 * have non-standard access checking logic. This class centralizes entity access
 * checking logic.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class EntityAccessChecker {

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The router.
   *
   * @var \Symfony\Component\Routing\RouterInterface
   */
  protected $router;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The node revision access check service.
   *
   * This will be NULL unless the node module is installed.
   *
   * @var \Drupal\node\Access\NodeRevisionAccessCheck|null
   */
  protected $nodeRevisionAccessCheck = NULL;

  /**
   * The media revision access check service.
   *
   * This will be NULL unless the media module is installed.
   *
   * @var \Drupal\media\Access\MediaRevisionAccessCheck|null
   */
  protected $mediaRevisionAccessCheck = NULL;

  /**
   * The latest revision check service.
   *
   * This will be NULL unless the content_moderation module is installed. This
   * is a temporary measure. JSON:API should not need to be aware of the
   * Content Moderation module.
   *
   * @var \Drupal\content_moderation\Access\LatestRevisionCheck
   */
  protected $latestRevisionCheck = NULL;

  /**
   * EntityAccessChecker constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param \Symfony\Component\Routing\RouterInterface $router
   *   The router.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, RouterInterface $router, AccountInterface $account, EntityRepositoryInterface $entity_repository) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->router = $router;
    $this->currentUser = $account;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Sets the node revision access check service.
   *
   * This is only called when node module is installed.
   *
   * @param \Drupal\node\Access\NodeRevisionAccessCheck $node_revision_access_check
   *   The node revision access check service.
   */
  public function setNodeRevisionAccessCheck(NodeRevisionAccessCheck $node_revision_access_check) {
    $this->nodeRevisionAccessCheck = $node_revision_access_check;
  }

  /**
   * Sets the media revision access check service.
   *
   * This is only called when media module is installed.
   *
   * @param \Drupal\media\Access\MediaRevisionAccessCheck $media_revision_access_check
   *   The media revision access check service.
   */
  public function setMediaRevisionAccessCheck(MediaRevisionAccessCheck $media_revision_access_check) {
    $this->mediaRevisionAccessCheck = $media_revision_access_check;
  }

  /**
   * Sets the media revision access check service.
   *
   * This is only called when content_moderation module is installed.
   *
   * @param \Drupal\content_moderation\Access\LatestRevisionCheck $latest_revision_check
   *   The latest revision access check service provided by the
   *   content_moderation module.
   *
   * @see self::$latestRevisionCheck
   */
  public function setLatestRevisionCheck(LatestRevisionCheck $latest_revision_check) {
    $this->latestRevisionCheck = $latest_revision_check;
  }

  /**
   * Get the object to normalize and the access based on the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account with which access should be checked. Defaults to
   *   the current user.
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObject|\Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject|\Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
   *   The ResourceObject, a LabelOnlyResourceObject or an
   *   EntityAccessDeniedHttpException object if neither is accessible. All
   *   three possible return values carry the access result cacheability.
   */
  public function getAccessCheckedResourceObject(EntityInterface $entity, AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
    $entity = $this->entityRepository->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
    $access = $this->checkEntityAccess($entity, 'view', $account);
    $entity->addCacheableDependency($access);
    if (!$access->isAllowed()) {
      // If this is the default revision or the entity is not revisionable, then
      // check access to the entity label. Revision support is all or nothing.
      if (!$entity->getEntityType()->isRevisionable() || $entity->isDefaultRevision()) {
        $label_access = $entity->access('view label', NULL, TRUE);
        $entity->addCacheableDependency($label_access);
        if ($label_access->isAllowed()) {
          return LabelOnlyResourceObject::createFromEntity($resource_type, $entity);
        }
        $access = $access->orIf($label_access);
      }
      return new EntityAccessDeniedHttpException($entity, $access, '/data', 'The current user is not allowed to GET the selected resource.');
    }
    return ResourceObject::createFromEntity($resource_type, $entity);
  }

  /**
   * Checks access to the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which access should be evaluated.
   * @param string $operation
   *   The entity operation for which access should be evaluated.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account with which access should be checked. Defaults to
   *   the current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|\Drupal\Core\Access\AccessResultReasonInterface
   *   The access check result.
   */
  public function checkEntityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = $entity->access($operation, $account, TRUE);
    if ($entity->getEntityType()->isRevisionable()) {
      $access = AccessResult::neutral()->addCacheContexts(['url.query_args:' . JsonApiSpec::VERSION_QUERY_PARAMETER])->orIf($access);
      if (!$entity->isDefaultRevision()) {
        assert($operation === 'view', 'JSON:API does not yet support mutable operations on revisions.');
        $revision_access = $this->checkRevisionViewAccess($entity, $account);
        $access = $access->andIf($revision_access);
        // The revision access reason should trump the primary access reason.
        if (!$access->isAllowed()) {
          $reason = $access instanceof AccessResultReasonInterface ? $access->getReason() : '';
          $access->setReason(trim('The user does not have access to the requested version. ' . $reason));
        }
      }
    }
    return $access;
  }

  /**
   * Checks access to the given revision entity.
   *
   * This should only be called for non-default revisions.
   *
   * There is no standardized API for revision access checking in Drupal core
   * and this method shims that missing API.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The revised entity for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account with which access should be checked. Defaults to
   *   the current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|\Drupal\Core\Access\AccessResultReasonInterface
   *   The access check result.
   *
   * @todo: remove when a generic revision access API exists in Drupal core, and
   * also remove the injected "node" and "media" services.
   * @see https://www.drupal.org/project/drupal/issues/2992833#comment-12818386
   */
  protected function checkRevisionViewAccess(EntityInterface $entity, AccountInterface $account) {
    assert($entity instanceof RevisionableInterface);
    assert(!$entity->isDefaultRevision(), 'It is not necessary to check revision access when the entity is the default revision.');
    $entity_type = $entity->getEntityType();
    switch ($entity_type->id()) {
      case 'node':
        assert($entity instanceof NodeInterface);
        $access = AccessResult::allowedIf($this->nodeRevisionAccessCheck->checkAccess($entity, $account, 'view'))->cachePerPermissions()->addCacheableDependency($entity);
        break;

      case 'media':
        assert($entity instanceof MediaInterface);
        $access = AccessResult::allowedIf($this->mediaRevisionAccessCheck->checkAccess($entity, $account, 'view'))->cachePerPermissions()->addCacheableDependency($entity);
        break;

      default:
        $reason = 'Only node and media revisions are supported by JSON:API.';
        $reason .= ' For context, see https://www.drupal.org/project/drupal/issues/2992833#comment-12818258.';
        $reason .= ' To contribute, see https://www.drupal.org/project/drupal/issues/2350939 and https://www.drupal.org/project/drupal/issues/2809177.';
        $access = AccessResult::neutral($reason);
    }
    // Apply content_moderation's additional access logic.
    // @see \Drupal\content_moderation\Access\LatestRevisionCheck::access()
    if ($entity_type->getLinkTemplate('latest-version') && $entity->isLatestRevision() && isset($this->latestRevisionCheck)) {
      // The latest revision access checker only expects to be invoked by the
      // routing system, which makes it necessary to fake a route match.
      $routes = $this->router->getRouteCollection();
      $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
      $route_name = sprintf('jsonapi.%s.individual', $resource_type->getTypeName());
      $route = $routes->get($route_name);
      $route->setOption('_content_moderation_entity_type', 'entity');
      $route_match = new RouteMatch($route_name, $route, ['entity' => $entity], ['entity' => $entity->uuid()]);
      $moderation_access_result = $this->latestRevisionCheck->access($route, $route_match, $account);
      $access = $access->andIf($moderation_access_result);
    }
    return $access;
  }

}
