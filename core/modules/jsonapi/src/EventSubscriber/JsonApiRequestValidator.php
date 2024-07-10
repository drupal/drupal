<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\jsonapi\JsonApiSpec;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\Http\Exception\CacheableBadRequestHttpException;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber that validates the query parameter names on a JSON:API request.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class JsonApiRequestValidator implements EventSubscriberInterface {

  /**
   * Validates JSON:API requests.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    if ($request->getRequestFormat() !== 'api_json') {
      return;
    }

    $this->validateQueryParams($request);
  }

  /**
   * Validates JSON:API requests.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) {
    $request = $event->getRequest();
    if ($request->getRequestFormat() !== 'api_json') {
      return;
    }

    // At this point, we've already run validation on the request by checking
    // the query arguments. This means that if the query arguments change, we
    // may need to run this validation again. Therefore, all responses need to
    // vary by url.query_args.
    $response = $event->getResponse();
    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['url.query_args']));
    }
  }

  /**
   * Validates custom (implementation-specific) query parameter names.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for which to validate JSON:API query parameters.
   *
   * @return \Drupal\jsonapi\ResourceResponse|null
   *   A JSON:API resource response.
   *
   * @see http://jsonapi.org/format/#query-parameters
   */
  protected function validateQueryParams(Request $request) {
    $invalid_query_params = [];
    foreach (array_keys($request->query->all()) as $query_parameter_name) {
      // Ignore reserved (official) query parameters.
      if (in_array($query_parameter_name, JsonApiSpec::getReservedQueryParameters())) {
        continue;
      }

      if (!JsonApiSpec::isValidCustomQueryParameter($query_parameter_name)) {
        $invalid_query_params[] = $query_parameter_name;
      }
    }

    if (empty($invalid_query_params)) {
      return NULL;
    }

    // Drupal uses the `_format` query parameter for Content-Type negotiation.
    // Using it violates the JSON:API spec. Nudge people nicely in the correct
    // direction. (This is special cased because using it is pretty common.)
    if (in_array('_format', $invalid_query_params, TRUE)) {
      $uri_without_query_string = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
      $exception = new CacheableBadRequestHttpException((new CacheableMetadata())->addCacheContexts(['url.query_args']), 'JSON:API does not need that ugly \'_format\' query string! 🤘 Use the URL provided in \'links\' 🙏');
      $exception->setHeaders(['Link' => $uri_without_query_string]);
      throw $exception;
    }

    $message = sprintf('The following query parameters violate the JSON:API spec: \'%s\'.', implode("', '", $invalid_query_params));
    $exception = new CacheableBadRequestHttpException((new CacheableMetadata())->addCacheContexts(['url.query_args']), $message);
    $exception->setHeaders(['Link' => 'http://jsonapi.org/format/#query-parameters']);
    throw $exception;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest'];

    // Run before the resource response subscriber (priority 128), so that said
    // subscriber gets the cacheable metadata from this one.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 129];
    return $events;
  }

}
