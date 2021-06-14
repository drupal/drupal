<?php

namespace Drupal\Core\Routing;

/**
 * Provides constants used for retrieving matched routes.
 */
interface RouteObjectInterface {

  /**
   * Key for the route name.
   *
   * @var string
   */
  const ROUTE_NAME = '_route';

  /**
   * Key for the route object.
   *
   * @var string
   */
  const ROUTE_OBJECT = '_route_object';

  /**
   * Key for the controller.
   */
  const CONTROLLER_NAME = '_controller';

}
