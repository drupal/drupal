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
 * Main subscriber for HTML-type HTTP responses.
 */
class HtmlSubscriber implements EventSubscriberInterface {

  /**
   * Determines if we are dealing with an HTML-style response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   * @return boolean
   *   True if it is an event we should process as HTML, False otherwise.
   */
  protected function isHtmlRequestEvent(GetResponseEvent $event) {
    $acceptable_content_types = $event->getRequest()->getAcceptableContentTypes();
    return in_array('text/html', $acceptable_content_types) || in_array('*/*', $acceptable_content_types);
  }

  /**
   * Processes an AccessDenied exception into an HTTP 403 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onAccessDeniedException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof AccessDeniedHttpException) {
      $event->setResponse(new Response('Access Denied', 403));
    }
  }

  /**
   * Processes a NotFound exception into an HTTP 404 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onNotFoundHttpException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof NotFoundHttpException) {
      $event->setResponse(new Response('Not Found', 404));
    }
  }

  /**
   * Processes a MethodNotAllowed exception into an HTTP 405 response.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onMethodAllowedException(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event) && $event->getException() instanceof MethodNotAllowedException) {
      $event->setResponse(new Response('Method Not Allowed', 405));
    }
  }

  /**
   * Processes a successful controller into an HTTP 200 response.
   *
   * Some controllers may not return a response object but simply the body of
   * one.  The VIEW event is called in that case, to allow us to mutate that
   * body into a Response object.  In particular we assume that the return
   * from an HTML-type response is a render array from a legacy page callback
   * and render it.
   *
   * @param GetResponseEvent $event
   *   The Event to process.
   */
  public function onView(GetResponseEvent $event) {
    if ($this->isHtmlRequestEvent($event)) {
      $page_callback_result = $event->getControllerResult();
      $event->setResponse(new Response(drupal_render_page($page_callback_result)));
    }
    else {
      $event->setResponse(new Response('Unsupported Media Type', 415));
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // Since we want HTML to be our default, catch-all response type, give its
    // listeners a very low priority so that they always check last.
    $events[KernelEvents::EXCEPTION][] = array('onNotFoundHttpException', -5);
    $events[KernelEvents::EXCEPTION][] = array('onAccessDeniedException', -5);
    $events[KernelEvents::EXCEPTION][] = array('onMethodAllowedException', -5);

    $events[KernelEvents::VIEW][] = array('onView', -5);

    return $events;
  }
}
