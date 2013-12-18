<?php

/**
 * @file
 * Contains Drupal\Core\EventSubscriber\ParamConverterSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\ParamConverter\ParamConverterManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteBuildEvent;

/**
 * Event subscriber for registering parameter converters with routes.
 */
class ParamConverterSubscriber implements EventSubscriberInterface {

  /**
   * The parameter converter manager.
   *
   * @var \Drupal\Core\ParamConverter\ParamConverterManager
   */
  protected $paramConverterManager;

  /**
   * Constructs a new ParamConverterSubscriber.
   *
   * @param \Drupal\Core\ParamConverter\ParamConverterManager $param_converter_manager
   *   The parameter converter manager that will be responsible for upcasting
   *   request attributes.
   */
  public function __construct(ParamConverterManager $param_converter_manager) {
    $this->paramConverterManager = $param_converter_manager;
  }

  /**
   * Applies parameter converters to route parameters.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterSetParameterConverters(RouteBuildEvent $event) {
    $this->paramConverterManager->setRouteParameterConverters($event->getRouteCollection());
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = array('onRoutingRouteAlterSetParameterConverters', -200);
    return $events;
  }
}
