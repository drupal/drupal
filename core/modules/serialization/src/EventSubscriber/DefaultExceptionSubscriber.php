<?php

namespace Drupal\serialization\EventSubscriber;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles default error responses in serialization formats.
 */
class DefaultExceptionSubscriber extends HttpExceptionSubscriberBase {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * DefaultExceptionSubscriber constructor.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer service.
   * @param array $serializer_formats
   *   The available serialization formats.
   */
  public function __construct(SerializerInterface $serializer, array $serializer_formats) {
    $this->serializer = $serializer;
    $this->serializerFormats = $serializer_formats;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return $this->serializerFormats;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    // This will fire after the most common HTML handler, since HTML requests
    // are still more common than HTTP requests. But it has a lower priority
    // than \Drupal\Core\EventSubscriber\ExceptionJsonSubscriber::on4xx(), so
    // that this also handles the 'json' format. Then all serialization formats
    // (::getHandledFormats()) are handled by this exception subscriber, which
    // results in better consistency.
    return -70;
  }

  /**
   * Handles all 4xx errors for all serialization failures.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on4xx(GetResponseForExceptionEvent $event) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception */
    $exception = $event->getException();
    $request = $event->getRequest();

    $format = $request->getRequestFormat();
    $content = ['message' => $exception->getMessage()];
    $encoded_content = $this->serializer->serialize($content, $format);
    $headers = $exception->getHeaders();

    // Add the MIME type from the request to send back in the header.
    $headers['Content-Type'] = $request->getMimeType($format);

    // If the exception is cacheable, generate a cacheable response.
    if ($exception instanceof CacheableDependencyInterface) {
      $response = new CacheableResponse($encoded_content, $exception->getStatusCode(), $headers);
      $response->addCacheableDependency($exception);
    }
    else {
      $response = new Response($encoded_content, $exception->getStatusCode(), $headers);
    }

    $event->setResponse($response);
  }

}
