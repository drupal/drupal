<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ExceptionJsonSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Default handling for JSON errors.
 */
class ExceptionJsonSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritDoc}
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
   * Handles a 403 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(NULL, Response::HTTP_FORBIDDEN);
    $event->setResponse($response);
  }

  /**
   * Handles a 404 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(NULL, Response::HTTP_NOT_FOUND);
    $event->setResponse($response);
  }

  /**
   * Handles a 405 error for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on405(GetResponseForExceptionEvent $event) {
    $response = new JsonResponse(NULL, Response::HTTP_METHOD_NOT_ALLOWED);
    $event->setResponse($response);
  }

}
