<?php

namespace Drupal\rest\EventSubscriber;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rest\ResourceResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
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
   * forget to specify a request format in case of a POST or PATCH. Rather than
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
    $acceptable_request_formats = $route->hasRequirement('_format') ? explode('|', $route->getRequirement('_format')) : [];
    $acceptable_content_type_formats = $route->hasRequirement('_content_type_format') ? explode('|', $route->getRequirement('_content_type_format')) : [];
    $acceptable_formats = $request->isMethodSafe() ? $acceptable_request_formats : $acceptable_content_type_formats;

    $requested_format = $request->getRequestFormat();
    $content_type_format = $request->getContentType();

    // If an acceptable format is requested, then use that. Otherwise, including
    // and particularly when the client forgot to specify a format, then use
    // heuristics to select the format that is most likely expected.
    if (in_array($requested_format, $acceptable_formats)) {
      return $requested_format;
    }
    // If a request body is present, then use the format corresponding to the
    // request body's Content-Type for the response, if it's an acceptable
    // format for the request.
    elseif (!empty($request->getContent()) && in_array($content_type_format, $acceptable_content_type_formats)) {
      return $content_type_format;
    }
    // Otherwise, use the first acceptable format.
    elseif (!empty($acceptable_formats)) {
      return $acceptable_formats[0];
    }
    // Sometimes, there are no acceptable formats, e.g. DELETE routes.
    else {
      return NULL;
    }
  }

  /**
   * Renders a resource response body.
   *
   * Serialization can invoke rendering (e.g., generating URLs), but the
   * serialization API does not provide a mechanism to collect the
   * bubbleable metadata associated with that (e.g., language and other
   * contexts), so instead, allow those to "leak" and collect them here in
   * a render context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\rest\ResourceResponseInterface $response
   *   The response from the REST resource.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer to use.
   * @param string|null $format
   *   The response format, or NULL in case the response does not need a format,
   *   for example for the response to a DELETE request.
   *
   * @todo Add test coverage for language negotiation contexts in
   *   https://www.drupal.org/node/2135829.
   */
  protected function renderResponseBody(Request $request, ResourceResponseInterface $response, SerializerInterface $serializer, $format) {
    $data = $response->getResponseData();

    // If there is data to send, serialize and set it as the response body.
    if ($data !== NULL) {
      $context = new RenderContext();
      $output = $this->renderer
        ->executeInRenderContext($context, function () use ($serializer, $data, $format) {
          return $serializer->serialize($data, $format);
        });

      if ($response instanceof CacheableResponseInterface && !$context->isEmpty()) {
        $response->addCacheableDependency($context->pop());
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
    $final_response->setCharset($response->getCharset());
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
    // Run shortly before \Drupal\Core\EventSubscriber\FinishResponseSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 5];
    return $events;
  }

}
