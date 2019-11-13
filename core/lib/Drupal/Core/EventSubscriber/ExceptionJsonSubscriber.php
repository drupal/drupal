<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Default handling for JSON errors.
 */
class ExceptionJsonSubscriber extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['json', 'drupal_modal', 'drupal_dialog', 'drupal_ajax'];
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
   * Handles all 4xx errors for JSON.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on4xx(GetResponseForExceptionEvent $event) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception */
    $exception = $event->getThrowable();

    // If the exception is cacheable, generate a cacheable response.
    if ($exception instanceof CacheableDependencyInterface) {
      $response = new CacheableJsonResponse(['message' => $event->getThrowable()->getMessage()], $exception->getStatusCode(), $exception->getHeaders());
      $response->addCacheableDependency($exception);
    }
    else {
      $response = new JsonResponse(['message' => $event->getThrowable()->getMessage()], $exception->getStatusCode(), $exception->getHeaders());
    }

    $event->setResponse($response);
  }

}
