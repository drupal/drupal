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
   * The configuration mapper this event is related to.
   *
   * @var \Drupal\config_translation\ConfigMapperInterface
   */
  protected $mapper;

  /**
   * The route match this event is related to.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ConfigMapperPopulateEvent object.
   *
   * @param \Drupal\config_translation\ConfigMapperInterface $mapper
   *   The configuration mapper this event is related to.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match this event is related to.
   */
  public function __construct(ConfigMapperInterface $mapper, RouteMatchInterface $route_match) {
    $this->mapper = $mapper;
    $this->routeMatch = $route_match;
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
