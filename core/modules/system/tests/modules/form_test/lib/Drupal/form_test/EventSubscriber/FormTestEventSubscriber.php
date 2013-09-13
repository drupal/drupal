<?php

/**
 * @file
 * Contains \Drupal\form_test\EventSubscriber\FormTestEventSubscriber.
 */

namespace Drupal\form_test\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test event subscriber to add new attributes to the request.
 */
class FormTestEventSubscriber implements EventSubscriberInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a FormTestController object.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Adds custom attributes to the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The kernel request event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->attributes->set('custom_attributes', 'custom_value');
    $request->attributes->set('request_attribute', 'request_value');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    $events[RoutingEvents::DYNAMIC] = 'routes';

    return $events;
  }

  /**
   * Adds routes for the Form Tests.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $defaults = array();

    if ($this->moduleHandler->moduleExists('node')) {
      $route = new Route(
        "form-test/two-instances-of-same-form",
        array('_content' => '\Drupal\form_test\Controller\FormTestController::twoFormInstances'),
        array('_permission' => 'create page content')
      );
      $collection->add("form_test.two_instances", $route);
    }
  }

}
