<?php

/*
 * @file
 * Definition of Drupal\Core\EventSubscriber\ExceptionListener.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Override of Symfony EventListener class to kill 403 and 404 from server logs.
 *
 * This is mostly a copy of Symfony's ExceptionListener but it doesn't have a
 * $logger property as we are not currently using a logger. The class from
 * Symfony will, in the absense of a logger, call error_log() on every http
 * exception.
 *
 * @todo Remove this class once we introduce a logger.
 *
 * @see http://drupal.org/node/1803338
 */
class ExceptionListener implements EventSubscriberInterface {
  private $controller;

  public function __construct($controller) {
    $this->controller = $controller;
  }

  public function onKernelException(GetResponseForExceptionEvent $event) {
    static $handling;

    if ($handling) {
      return FALSE;
    }

    $handling = TRUE;

    $exception = $event->getException();
    $request = $event->getRequest();
    // Do not put a line in the server logs for every HTTP error.
    if (!$exception instanceof HttpExceptionInterface || $exception->getStatusCode() >= 500) {
        error_log(sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));
    }

    $attributes = array(
      '_controller' => $this->controller,
      'exception'   => FlattenException::create($exception),
      'logger'      => NULL,
      'format'      => $request->getRequestFormat(),
    );

    $request = $request->duplicate(NULL, NULL, $attributes);
    $request->setMethod('GET');

    try {
      $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, TRUE);
    }
    catch (\Exception $e) {
      $message = sprintf('Exception thrown when handling an exception (%s: %s)', get_class($e), $e->getMessage());
      error_log($message);
      // Set handling to false otherwise it won't be able to handle further
      // exceptions.
      $handling = FALSE;
      return;
    }

    $event->setResponse($response);
    $handling = FALSE;
  }

  public static function getSubscribedEvents() {
    return array(
      KernelEvents::EXCEPTION => array('onKernelException', -128),
    );
  }
}
