<?php

namespace Drupal\config_translation\Event;

use Drupal\config_translation\ConfigMapperInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Provides a class for events related to configuration translation mappers.
 */
class ConfigMapperPopulateEvent extends Event {

  /**
   * Constructs a ConfigMapperPopulateEvent object.
   *
   * @param \Drupal\config_translation\ConfigMapperInterface $mapper
   *   The configuration mapper this event is related to.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match this event is related to.
   */
  public function __construct(
    protected ConfigMapperInterface $mapper,
    protected RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * Gets the configuration mapper this event is related to.
   *
   * @return \Drupal\config_translation\ConfigMapperInterface
   *   The configuration mapper this event is related to.
   */
  public function getMapper() {
    return $this->mapper;
  }

  /**
   * Gets the route match this event is related to.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match this event is related to.
   */
  public function getRouteMatch() {
    return $this->routeMatch;
  }

}
