<?php

namespace Drupal\Core\EventSubscriber;

use Psr\Http\Message\ResponseInterface;

use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber for handling PSR-7 responses.
 */
class PsrResponseSubscriber implements EventSubscriberInterface {

  /**
   * The httpFoundation factory.
   *
   * @var \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface
   */
  protected $httpFoundationFactory;

  /**
   * Constructs a new PathRootsSubscriber instance.
   *
   * @param \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface $http_foundation_factory
   *    The httpFoundation factory.
   */
  public function __construct(HttpFoundationFactoryInterface $http_foundation_factory) {
    $this->httpFoundationFactory = $http_foundation_factory;
  }

  /**
   * Converts a PSR-7 response to a Symfony response.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent $event
   *   The Event to process.
   */
  public function onKernelView(GetResponseForControllerResultEvent $event) {
    $controller_result = $event->getControllerResult();

    if ($controller_result instanceof ResponseInterface) {
      $event->setResponse($this->httpFoundationFactory->createResponse($controller_result));
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onKernelView'];
    return $events;
  }

}
