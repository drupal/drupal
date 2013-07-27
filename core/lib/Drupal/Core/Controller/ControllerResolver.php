<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\ControllerResolver.
 */

namespace Drupal\Core\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class ControllerResolver extends BaseControllerResolver {

  /**
   * The injection container that should be injected into all controllers.
   *
   * @var Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new ControllerResolver.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A ContainerInterface instance.
   * @param Symfony\Component\HttpKernel\Log\LoggerInterface $logger
   *   (optional) A LoggerInterface instance.
   */
  public function __construct(ContainerInterface $container, LoggerInterface $logger = NULL) {
    $this->container = $container;

    parent::__construct($logger);
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
      list($service, $method) = explode(':', $controller, 2);
      return array($this->container->get($service), $method);
    }

    // Controller in the class::method notation.
    if (strpos($controller, '::') !== FALSE) {
      list($class, $method) = explode('::', $controller, 2);
      if (!class_exists($class)) {
        throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
      }
      // @todo Remove the second in_array() once that interface has been removed.
      if (in_array('Drupal\Core\Controller\ControllerInterface', class_implements($class))) {
        $controller = $class::create($this->container);
      }
      else {
        $controller = new $class();
      }
    }
    else {
      throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller));
    }

    if ($controller instanceof ContainerAwareInterface) {
      $controller->setContainer($this->container);
    }

    return array($controller, $method);
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetArguments(Request $request, $controller, array $parameters) {
    $arguments = parent::doGetArguments($request, $controller, $parameters);

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
