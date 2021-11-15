<?php

namespace Drupal\Core\Controller;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;

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
class ControllerResolver extends BaseControllerResolver implements ControllerResolverInterface {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The PSR-7 converter.
   *
   * @var \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface
   */
  protected $httpMessageFactory;

  /**
   * Constructs a new ControllerResolver.
   *
   * @param \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface $http_message_factory
   *   The PSR-7 converter.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(HttpMessageFactoryInterface $http_message_factory, ClassResolverInterface $class_resolver) {
    $this->httpMessageFactory = $http_message_factory;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerFromDefinition($controller, $path = '') {
    if (is_array($controller) || (is_object($controller) && method_exists($controller, '__invoke'))) {
      return $controller;
    }

    if (strpos($controller, ':') === FALSE) {
      if (function_exists($controller)) {
        return $controller;
      }
      return $this->classResolver->getInstanceFromDefinition($controller);
    }

    $callable = $this->createController($controller);

    if (!is_callable($callable)) {
      throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable.', $path));
    }

    return $callable;
  }

  /**
   * {@inheritdoc}
   */
  public function getController(Request $request) {
    if (!$controller = $request->attributes->get('_controller')) {
      return FALSE;
    }
    return $this->getControllerFromDefinition($controller, $request->getPathInfo());
  }

  /**
   * Returns a callable for the given controller.
   *
   * @param string $controller
   *   A Controller string.
   *
   * @return mixed
   *   A PHP callable.
   *
   * @throws \LogicException
   *   If the controller cannot be parsed.
   *
   * @throws \InvalidArgumentException
   *   If the controller class does not exist.
   */
  protected function createController($controller) {
    // Controller in the service:method notation.
    $count = substr_count($controller, ':');
    if ($count == 1) {
      [$class_or_service, $method] = explode(':', $controller, 2);
    }
    // Controller in the class::method notation.
    elseif (strpos($controller, '::') !== FALSE) {
      [$class_or_service, $method] = explode('::', $controller, 2);
    }
    else {
      throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller));
    }

    $controller = $this->classResolver->getInstanceFromDefinition($class_or_service);

    return [$controller, $method];
  }

}
