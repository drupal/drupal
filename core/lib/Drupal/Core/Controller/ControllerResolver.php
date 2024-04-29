<?php

namespace Drupal\Core\Controller;

use Drupal\Core\Utility\CallableResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * ControllerResolver to enhance controllers beyond Symfony's basic handling.
 *
 * It adds one behavior:
 *
 *  - By default, a controller name follows the class::method notation. This
 *    class adds the possibility to use a service from the container as a
 *    controller by using a service:method notation (Symfony uses the same
 *    convention).
 */
class ControllerResolver implements ControllerResolverInterface {

  /**
   * Constructs a new ControllerResolver.
   *
   * @param \Drupal\Core\Utility\CallableResolver $callableResolver
   *   The callable resolver.
   */
  public function __construct(protected CallableResolver $callableResolver) {
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerFromDefinition($controller, $path = '') {
    try {
      $callable = $this->callableResolver->getCallableFromDefinition($controller);
    }
    catch (\InvalidArgumentException $e) {
      throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable.', $path), 0, $e);
    }
    return $callable;
  }

  /**
   * {@inheritdoc}
   */
  public function getController(Request $request): callable|FALSE {
    if (!$controller = $request->attributes->get('_controller')) {
      return FALSE;
    }
    return $this->getControllerFromDefinition($controller, $request->getPathInfo());
  }

}
