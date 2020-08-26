<?php

namespace Drupal\jsonapi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Routing\Routes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a class to check access to related and relationship routes.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class RelationshipFieldAccess implements AccessInterface {

  /**
   * The route requirement key for this access check.
   *
   * @var string
   */
  const ROUTE_REQUIREMENT_KEY = '_jsonapi_relationship_field_access';

  /**
   * The JSON:API entity access checker.
   *
   * @var \Drupal\jsonapi\Access\EntityAccessChecker
   */
  protected $entityAccessChecker;

  /**
   * RelationshipFieldAccess constructor.
   *
   * @param \Drupal\jsonapi\Access\EntityAccessChecker $entity_access_checker
   *   The JSON:API entity access checker.
   */
  public function __construct(EntityAccessChecker $entity_access_checker) {
    $this->entityAccessChecker = $entity_access_checker;
  }

  /**
   * Checks access to the relationship field on the given route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming HTTP request object.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, Route $route, AccountInterface $account) {
    $relationship_field_name = $route->getRequirement(static::ROUTE_REQUIREMENT_KEY);
    $field_operation = $request->isMethodCacheable() ? 'view' : 'edit';
    $entity_operation = $request->isMethodCacheable() ? 'view' : 'update';
    if ($resource_type = $request->get(Routes::RESOURCE_TYPE_KEY)) {
      assert($resource_type instanceof ResourceType);
      $entity = $request->get('entity');
      $internal_name = $resource_type->getInternalName($relationship_field_name);
      if ($entity instanceof FieldableEntityInterface && $entity->hasField($internal_name)) {
        $entity_access = $this->entityAccessChecker->checkEntityAccess($entity, $entity_operation, $account);
        $field_access = $entity->get($internal_name)->access($field_operation, $account, TRUE);
        // Ensure that access is respected for different entity revisions.
        $access_result = $entity_access->andIf($field_access);
        if (!$access_result->isAllowed()) {
          $reason = "The current user is not allowed to {$field_operation} this relationship.";
          $access_reason = $access_result instanceof AccessResultReasonInterface ? $access_result->getReason() : NULL;
          $detailed_reason = empty($access_reason) ? $reason : $reason . " {$access_reason}";
          $access_result->setReason($detailed_reason);
          if ($request->isMethodCacheable()) {
            throw new CacheableAccessDeniedHttpException(CacheableMetadata::createFromObject($access_result), $detailed_reason);
          }
        }
        return $access_result;
      }
    }
    return AccessResult::neutral();
  }

}
