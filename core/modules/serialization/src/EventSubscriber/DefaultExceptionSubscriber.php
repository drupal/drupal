<?php

namespace Drupal\serialization\EventSubscriber;

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
    // are still more common than HTTP requests.
    return -75;
  }

  /**
   * Handles a 400 error for HTTP.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on400(GetResponseForExceptionEvent $event) {
    $this->setEventResponse($event, Response::HTTP_BAD_REQUEST);
  }

  /**
   * Handles a 403 error for HTTP.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $this->setEventResponse($event, Response::HTTP_FORBIDDEN);
  }

  /**
   * Handles a 404 error for HTTP.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $this->setEventResponse($event, Response::HTTP_NOT_FOUND);
  }

  /**
   * Handles a 405 error for HTTP.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on405(GetResponseForExceptionEvent $event) {
    $this->setEventResponse($event, Response::HTTP_METHOD_NOT_ALLOWED);
  }

  /**
   * Handles a 406 error for HTTP.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on406(GetResponseForExceptionEvent $event) {
    $this->setEventResponse($event, Response::HTTP_NOT_ACCEPTABLE);
  }

  /**
   * Handles a 422 error for HTTP.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on422(GetResponseForExceptionEvent $event) {
    $this->setEventResponse($event, Response::HTTP_UNPROCESSABLE_ENTITY);
  }

  /**
   * Sets the Response for the exception event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The current exception event.
   * @param int $status
   *   The HTTP status code to set for the response.
   */
  protected function setEventResponse(GetResponseForExceptionEvent $event, $status) {
    $format = $event->getRequest()->getRequestFormat();
    $content = ['message' => $event->getException()->getMessage()];
    $encoded_content = $this->serializer->serialize($content, $format);
    $response = new Response($encoded_content, $status);
    $event->setResponse($response);
  }

}
