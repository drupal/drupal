<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\Routing\Routes;
use Drupal\serialization\EventSubscriber\DefaultExceptionSubscriber as SerializationDefaultExceptionSubscriber;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Serializes exceptions in compliance with the  JSON:API specification.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class DefaultExceptionSubscriber extends SerializationDefaultExceptionSubscriber {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return parent::getPriority() + 25;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['api_json'];
  }

  /**
   * {@inheritdoc}
   */
  public function onException(ExceptionEvent $event) {
    if (!$this->isJsonApiExceptionEvent($event)) {
      return;
    }
    if (($exception = $event->getThrowable()) && !$exception instanceof HttpException) {
      $exception = new HttpException(500, $exception->getMessage(), $exception);
      $event->setThrowable($exception);
    }

    $this->setEventResponse($event, $exception->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  protected function setEventResponse(ExceptionEvent $event, $status) {
    /* @var \Symfony\Component\HttpKernel\Exception\HttpException $exception */
    $exception = $event->getThrowable();
    $response = new ResourceResponse(new JsonApiDocumentTopLevel(new ErrorCollection([$exception]), new NullIncludedData(), new LinkCollection([])), $exception->getStatusCode(), $exception->getHeaders());
    $response->addCacheableDependency($exception);
    $event->setResponse($response);
  }

  /**
   * Check if the error should be formatted using JSON:API.
   *
   * The JSON:API format is supported if the format is explicitly set or the
   * request is for a known JSON:API route.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $exception_event
   *   The exception event.
   *
   * @return bool
   *   TRUE if it needs to be formatted using JSON:API. FALSE otherwise.
   */
  protected function isJsonApiExceptionEvent(ExceptionEvent $exception_event) {
    $request = $exception_event->getRequest();
    $parameters = $request->attributes->all();
    return $request->getRequestFormat() === 'api_json' || (bool) Routes::getResourceTypeNameFromParameters($parameters);
  }

}
