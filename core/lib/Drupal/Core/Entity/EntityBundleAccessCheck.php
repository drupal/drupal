<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an entity bundle checker for the _entity_bundles route requirement.
 *
 * @todo Deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Specify
 *   the list of bundles in the entity parameter, under "bundle" key, as a
 *   sequence, instead.
 *
 * @see https://www.drupal.org/node/3155569
 */
class EntityBundleAccessCheck implements AccessInterface {

  /**
   * Checks entity bundle match based on the _entity_bundles route requirement.
   *
   * @code
   * example.route:
   *   path: foo/{example_entity_type}/{other_parameter}
   *   requirements:
   *     _entity_bundles: 'example_entity_type:example_bundle|other_example_bundle'
   * @endcode
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    @trigger_error('The ' . __NAMESPACE__ . '\EntityBundleAccessCheck is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Specify the list of bundles in the entity parameter, under "bundle" key, as a sequence, instead. See https://www.drupal.org/node/3155569', E_USER_DEPRECATED);
    if ($route->hasRequirement('_entity_bundles')) {
      [$entity_type, $bundle_definition] = explode(':', $route->getRequirement('_entity_bundles'));
      $bundles = explode('|', $bundle_definition);
      $parameters = $route_match->getParameters();
      if ($parameters->has($entity_type)) {
        $entity = $parameters->get($entity_type);
        if ($entity instanceof EntityInterface && in_array($entity->bundle(), $bundles, TRUE)) {
          return AccessResult::allowed()->addCacheableDependency($entity);
        }
      }
    }
    return AccessResult::neutral('The entity bundle does not match the route _entity_bundles requirement.');
  }

}
