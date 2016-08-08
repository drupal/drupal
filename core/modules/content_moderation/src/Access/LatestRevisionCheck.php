<?php

namespace Drupal\content_moderation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\content_moderation\ModerationInformationInterface;
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
   * Checks that there is a forward revision available.
   *
   * This checker assumes the presence of an '_entity_access' requirement key
   * in the same form as used by EntityAccessCheck.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Entity\EntityAccessCheck
   */
  public function access(Route $route, RouteMatchInterface $route_match) {
    // This tab should not show up unless there's a reason to show it.
    $entity = $this->loadEntity($route, $route_match);
    return $this->moderationInfo->hasForwardRevision($entity)
      ? AccessResult::allowed()->addCacheableDependency($entity)
      : AccessResult::forbidden()->addCacheableDependency($entity);
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
   * @throws \Exception
   *   A generic exception is thrown if the entity couldn't be loaded. This
   *   almost always implies a developer error, so it should get turned into
   *   an HTTP 500.
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    $entity_type = $route->getOption('_content_moderation_entity_type');

    if ($entity = $route_match->getParameter($entity_type)) {
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }
    throw new \Exception(sprintf('%s is not a valid entity route. The LatestRevisionCheck access checker may only be used with a route that has a single entity parameter.', $route_match->getRouteName()));
  }

}
