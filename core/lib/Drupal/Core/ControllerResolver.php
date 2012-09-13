<?php

/**
 * @file
 * Definition of Drupal\Core\ControllerResolver.
 */

namespace Drupal\Core;

use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ControllerResolver to enhance controllers beyond Symfony's basic handling.
 *
 * When creating a new object-based controller that implements
 * ContainerAwareInterface, inject the container into it. While not always
 * necessary, that allows a controller to vary the services it needs at runtime.
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
   */
  protected function createController($controller) {
    $controller = parent::createController($controller);

    // $controller will be an array of object and method name, per PHP's
    // definition of a callable. Index 0 therefore is the object we want to
    // enhance.
    if ($controller[0] instanceof ContainerAwareInterface) {
      $controller[0]->setContainer($this->container);
    }

    return $controller;
  }
}
