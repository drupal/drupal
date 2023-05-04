<?php

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rest\ResourceResponseInterface;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Response subscriber that serializes and removes ResourceResponses' data.
 */
class ResourceResponseSubscriber implements EventSubscriberInterface {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ResourceResponseSubscriber object.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(SerializerInterface $serializer, RendererInterface $renderer, RouteMatchInterface $route_match) {
    $this->serializer = $serializer;
    $this->renderer = $renderer;
    $this->routeMatch = $route_match;
  }

  /**
   * Serializes ResourceResponse responses' data, and removes that data.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof ResourceResponseInterface) {
      return;
    }

    $request = $event->getRequest();
    $format = $this->getResponseFormat($this->routeMatch, $request);
    $this->renderResponseBody($request, $response, $this->serializer, $format);
    $event->setResponse($this->flattenResponse($response));
  }

  /**
   * Determines the format to respond in.
   *
   * Respects the requested format if one is specified. However, it is common to
   * forget to specify a response format in case of a POST or PATCH. Rather than
   * simply throwing an error, we apply the robustness principle: when POSTing
   * or PATCHing using a certain format, you probably expect a response in that
   * same format.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The response format.
   */
  public function getResponseFormat(RouteMatchInterface $route_match, Request $request) {
    $route = $route_match->getRouteObject();
    $acceptable_response_formats = $route->hasRequirement('_format') ? explode('|', $route->getRequirement('_format')) : [];
    $acceptable_request_formats = $route->hasRequirement('_content_type_format') ? explode('|', $route->getRequirement('_content_type_format')) : [];
    $acceptable_formats = $request->isMethodCacheable() ? $acceptable_response_formats : $acceptable_request_formats;

    $requested_format = $request->getRequestFormat();
    $content_type_format = $request->getContentType();

    // If an acceptable response format is requested, then use that. Otherwise,
    // including and particularly when the client forgot to specify a response
    // format, then use heuristics to select the format that is most likely
    // expected.
    if (in_array($requested_format, $acceptable_response_formats, TRUE)) {
      return $requested_format;
    }

    // If a request body is present, then use the format corresponding to the
    // request body's Content-Type for the response, if it's an acceptable
    // format for the request.
    if (!empty($request->getContent()) && in_array($content_type_format, $acceptable_request_formats, TRUE)) {
      return $content_type_format;
    }

    // Otherwise, use the first acceptable format.
    if (!empty($acceptable_formats)) {
      return $acceptable_formats[0];
    }

    // Sometimes, there are no acceptable formats.
    return NULL;
  }

  /**
   * Renders a resource response body.
   *
   * During serialization, encoders and normalizers are able to explicitly
   * bubble cacheability metadata via the 'cacheability' key-value pair in the
   * received context. This bubbled cacheability metadata will be applied to the
   * the response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\rest\ResourceResponseInterface $response
   *   The response from the REST resource.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer to use.
   * @param string|null $format
   *   The response format, or NULL in case the response does not need a format.
   *
   * @todo Add test coverage for language negotiation contexts in
   *   https://www.drupal.org/node/2135829.
   */
  protected function renderResponseBody(Request $request, ResourceResponseInterface $response, SerializerInterface $serializer, $format) {
    $data = $response->getResponseData();

    // If there is data to send, serialize and set it as the response body.
    if ($data !== NULL) {
      $serialization_context = [
        'request' => $request,
        CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => new CacheableMetadata(),
      ];

      $output = $serializer->serialize($data, $format, $serialization_context);

      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($serialization_context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]);
      }

      $response->setContent($output);
      $response->headers->set('Content-Type', $request->getMimeType($format));
    }
  }

  /**
   * Flattens a fully rendered resource response.
   *
   * Ensures that complex data structures in ResourceResponse::getResponseData()
   * are not serialized. Not doing this means that caching this response object
   * requires unserializing the PHP data when reading this response object from
   * cache, which can be very costly, and is unnecessary.
   *
   * @param \Drupal\rest\ResourceResponseInterface $response
   *   A fully rendered resource response.
   *
   * @return \Drupal\Core\Cache\CacheableResponse|\Symfony\Component\HttpFoundation\Response
   *   The flattened response.
   */
  protected function flattenResponse(ResourceResponseInterface $response) {
    $final_response = ($response instanceof CacheableResponseInterface) ? new CacheableResponse() : new Response();
    $final_response->setContent($response->getContent());
    $final_response->setStatusCode($response->getStatusCode());
    $final_response->setProtocolVersion($response->getProtocolVersion());
    if ($response->getCharset()) {
      $final_response->setCharset($response->getCharset());
    }
    $final_response->headers = clone $response->headers;
    if ($final_response instanceof CacheableResponseInterface) {
      $final_response->addCacheableDependency($response->getCacheableMetadata());
    }
    return $final_response;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber
    // (priority 100), so that Dynamic Page Cache can cache flattened responses.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 128];
    return $events;
  }

}
