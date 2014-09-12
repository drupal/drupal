<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\ControllerResolver.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Routing\RouteMatch;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Drupal\Core\DependencyInjection\ClassResolverInterface;

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
   * The PSR-3 logger. (optional)
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new ControllerResolver.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Psr\Log\LoggerInterface $logger
   *   (optional) A LoggerInterface instance.
   */
  public function __construct(ClassResolverInterface $class_resolver, LoggerInterface $logger = NULL) {
    $this->classResolver = $class_resolver;

    parent::__construct($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerFromDefinition($controller, $path = '') {
    if (is_array($controller) || (is_object($controller) && method_exists($controller, '__invoke'))) {
      return $controller;
    }

    if (strpos($controller, ':') === FALSE) {
      if (method_exists($controller, '__invoke')) {
        return new $controller;
      }
      elseif (function_exists($controller)) {
        return $controller;
      }
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
      if ($this->logger !== NULL) {
        $this->logger->warning('Unable to look for the controller as the "_controller" parameter is missing');
      }

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
   *   If the controller cannot be parsed
   *
   * @throws \InvalidArgumentException
   *   If the controller class does not exist
   */
  protected function createController($controller) {
    // Controller in the service:method notation.
    $count = substr_count($controller, ':');
    if ($count == 1) {
      list($class_or_service, $method) = explode(':', $controller, 2);
    }
    // Controller in the class::method notation.
    elseif (strpos($controller, '::') !== FALSE) {
      list($class_or_service, $method) = explode('::', $controller, 2);
    }
    else {
      throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller));
    }

    $controller = $this->classResolver->getInstanceFromDefinition($class_or_service);

    return array($controller, $method);
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetArguments(Request $request, $controller, array $parameters) {
    $attributes = $request->attributes->all();
    $arguments = array();
    foreach ($parameters as $param) {
      if (array_key_exists($param->name, $attributes)) {
        $arguments[] = $attributes[$param->name];
      }
      elseif ($param->getClass() && $param->getClass()->isInstance($request)) {
        $arguments[] = $request;
      }
      elseif ($param->getClass() && ($param->getClass()->name == 'Drupal\Core\Routing\RouteMatchInterface' || is_subclass_of($param->getClass()->name, 'Drupal\Core\Routing\RouteMatchInterface'))) {
        $arguments[] = RouteMatch::createFromRequest($request);
      }
      elseif ($param->isDefaultValueAvailable()) {
        $arguments[] = $param->getDefaultValue();
      }
      else {
        if (is_array($controller)) {
          $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
        }
        elseif (is_object($controller)) {
          $repr = get_class($controller);
        }
        else {
          $repr = $controller;
        }

        throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
      }
    }

    // The parameter converter overrides the raw request attributes with the
    // upcasted objects. However, it keeps a backup copy of the original, raw
    // values in a special request attribute ('_raw_variables'). If a controller
    // argument has a type hint, we pass it the upcasted object, otherwise we
    // pass it the original, raw value.
    if ($request->attributes->has('_raw_variables') && $raw = $request->attributes->get('_raw_variables')->all()) {
      foreach ($parameters as $parameter) {
        // Use the raw value if a parameter has no typehint.
        if (!$parameter->getClass() && isset($raw[$parameter->name])) {
          $position = $parameter->getPosition();
          $arguments[$position] = $raw[$parameter->name];
        }
      }
    }
    return $arguments;
  }

}
