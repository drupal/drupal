<?php

namespace Drupal\content_moderation\Access;

use Drupal\Core\Access\AccessException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for the entity moderation tab.
 */
class LatestRevisionCheck implements AccessInterface {

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a new LatestRevisionCheck.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct(ModerationInformationInterface $moderation_information) {
    $this->moderationInfo = $moderation_information;
  }

  /**
   * Checks that there is a pending revision available.
   *
   * This checker assumes the presence of an '_entity_access' requirement key
   * in the same form as used by EntityAccessCheck.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Entity\EntityAccessCheck
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // This tab should not show up unless there's a reason to show it.
    $entity = $this->loadEntity($route, $route_match);
    if ($this->moderationInfo->hasPendingRevision($entity)) {
      // Check the global permissions first.
      $access_result = AccessResult::allowedIfHasPermissions($account, ['view latest version', 'view any unpublished content']);
      if (!$access_result->isAllowed()) {
        // Check entity owner access.
        $owner_access = AccessResult::allowedIfHasPermissions($account, ['view latest version', 'view own unpublished content']);
        $owner_access = $owner_access->andIf((AccessResult::allowedIf($entity instanceof EntityOwnerInterface && ($entity->getOwnerId() == $account->id()))));
        $access_result = $access_result->orIf($owner_access);
      }

      return $access_result->addCacheableDependency($entity);
    }

    return AccessResult::forbidden()->addCacheableDependency($entity);
  }

  /**
   * Returns the default revision of the entity this route is for.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   returns the Entity in question.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   An AccessException is thrown if the entity couldn't be loaded.
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    $entity_type = $route->getOption('_content_moderation_entity_type');

    if ($entity = $route_match->getParameter($entity_type)) {
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }
    throw new AccessException(sprintf('%s is not a valid entity route. The LatestRevisionCheck access checker may only be used with a route that has a single entity parameter.', $route_match->getRouteName()));
  }

}
