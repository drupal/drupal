<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @file
 *
 * Definition of Drupal\Core\EventSubscriber\HtmlSubscriber;
 */

/**
 * Main subscriber for JSON-type HTTP responses.
 */
class JsonSubscriber implements EventSubscriberInterface {

  /**
   * Determines if we are dealing with an JSON-style response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   * @return boolean
   *   True if it is an event we should process as JSON, False otherwise.
   */
  protected function isJsonRequestEvent(GetResponseEvent $event) {
    return in_array('application/json', $event->getRequest()->getAcceptableContentTypes());
  }

  protected function createJsonResponse() {
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json; charset=utf-8');

    return $response;
  }

  /**
   * Processes an AccessDenied exception into an HTTP 403 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onAccessDeniedException(GetResponseEvent $event) {
    if ($this->isJsonRequestEvent($event) && $event->getException() instanceof AccessDeniedHttpException) {
      $response = $this->createJsonResponse();
      $response->setStatusCode(403, 'Access Denied');
      $event->setResponse($response);
    }
  }

  /**
   * Processes a NotFound exception into an HTTP 404 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onNotFoundHttpException(GetResponseEvent $event) {
    if ($this->isJsonRequestEvent($event) && $event->getException() instanceof NotFoundHttpException) {
      $response = $this->createJsonResponse();
      $response->setStatusCode(404, 'Not Found');
      $event->setResponse($response);
    }
  }

  /**
   * Processes a MethodNotAllowed exception into an HTTP 405 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onMethodAllowedException(GetResponseEvent $event) {
    if ($this->isJsonRequestEvent($event) && $event->getException() instanceof MethodNotAllowedException) {
      $response = $this->createJsonResponse();
      $response->setStatusCode(405, 'Method Not Allowed');
      $event->setResponse($response);
    }
  }

  /**
   * Processes a successful controller into an HTTP 200 response.
   *
   * Some controllers may not return a response object but simply the body of
   * one.  The VIEW event is called in that case, to allow us to mutate that
   * body into a Response object.  In particular we assume that the return
   * from an JSON-type response is a JSON string, so just wrap it into a
   * Response object.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onView(GetResponseEvent $event) {
    if ($this->isJsonRequestEvent($event)) {
      $page_callback_result = $event->getControllerResult();

      $response = $this->createJsonResponse();
      $response->setContent($page_callback_result);

      $event->setResponse($response);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('onNotFoundHttpException');
    $events[KernelEvents::EXCEPTION][] = array('onAccessDeniedException');
    $events[KernelEvents::EXCEPTION][] = array('onMethodAllowedException');

    $events[KernelEvents::VIEW][] = array('onView');

    return $events;
  }
}
