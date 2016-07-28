<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Default handling for JSON errors.
 */
class ExceptionJsonSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['json'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // This will fire after the most common HTML handler, since HTML requests
    // are still more common than JSON requests.
    return -75;
  }

  /**
   * Handles a 400 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on400(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(array('message' => $event->getException()->getMessage()), Response::HTTP_BAD_REQUEST);
    $event->setResponse($response);
  }

  /**
   * Handles a 403 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(array('message' => $event->getException()->getMessage()), Response::HTTP_FORBIDDEN);
    $event->setResponse($response);
  }

  /**
   * Handles a 404 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(array('message' => $event->getException()->getMessage()), Response::HTTP_NOT_FOUND);
    $event->setResponse($response);
  }

  /**
   * Handles a 405 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on405(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(array('message' => $event->getException()->getMessage()), Response::HTTP_METHOD_NOT_ALLOWED);
    $event->setResponse($response);
  }

  /**
   * Handles a 406 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on406(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(['message' => $event->getException()->getMessage()], Response::HTTP_NOT_ACCEPTABLE);
    $event->setResponse($response);
  }

  /**
   * Handles a 415 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on415(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(['message' => $event->getException()->getMessage()], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    $event->setResponse($response);
  }

}
