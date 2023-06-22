<?php

namespace Drupal\Core\Controller;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Utility\CallableResolver;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * ControllerResolver to enhance controllers beyond Symfony's basic handling.
 *
 * It adds two behaviors:
 *
 *  - When creating a new object-based controller that implements
 *    ContainerAwareInterface, inject the container into it. While not always
 *    necessary, that allows a controller to vary the services it needs at
 *    runtime.
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
   * @param \Drupal\Core\Utility\CallableResolver|\Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface $callableResolver
   *   The callable resolver.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface|null $class_resolver
   *   The class resolver.
   */
  public function __construct(protected CallableResolver|HttpMessageFactoryInterface $callableResolver, ClassResolverInterface $class_resolver = NULL) {
    if ($callableResolver instanceof HttpMessageFactoryInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $http_message_factory argument is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3353869', E_USER_DEPRECATED);
      $this->callableResolver = \Drupal::service("callable_resolver");
    }
    if ($class_resolver !== NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $class_resolver argument is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3353869', E_USER_DEPRECATED);
    }
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
