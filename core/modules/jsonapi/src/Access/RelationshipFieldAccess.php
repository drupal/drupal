<?php

namespace Drupal\jsonapi\Access;

use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Defines a class to check access to related and relationship routes.
 *
 * @todo Deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is
 *   no replacement. JSON:API's access checkers are not part of its public API.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/node/3194641
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
    @trigger_error(sprintf("The %s access check is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no replacement. JSON:API's route access checks are internal. See https://www.drupal.org/node/3194641.", static::ROUTE_REQUIREMENT_KEY), E_USER_DEPRECATED);
    $relationship_route_access_checker = \Drupal::service('access_check.jsonapi.relationship_route_access');
    assert($relationship_route_access_checker instanceof RelationshipRouteAccessCheck);
    $access_result = $relationship_route_access_checker->access($route, RouteMatch::createFromRequest($request), $account);
    assert($access_result instanceof AccessResultReasonInterface);
    if (!$access_result->isAllowed() && $request->isMethodCacheable()) {
      throw new CacheableAccessDeniedHttpException(CacheableMetadata::createFromObject($access_result), $access_result->getReason());
    }
    return $access_result;
  }

}
