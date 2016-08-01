<?php

namespace Drupal\rest;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Acts as intermediate request forwarder for resource plugins.
 */
class RequestHandler implements ContainerAwareInterface, ContainerInjectionInterface {

  use ContainerAwareTrait;

  /**
   * The resource configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $resourceStorage;

  /**
   * Creates a new RequestHandler instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The resource configuration storage.
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->resourceStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager')->getStorage('rest_resource_config'));
  }

  /**
   * Handles a web API request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function handle(RouteMatchInterface $route_match, Request $request) {
    $method = strtolower($request->getMethod());

    // Symfony is built to transparently map HEAD requests to a GET request. In
    // the case of the REST module's RequestHandler though, we essentially have
    // our own light-weight routing system on top of the Drupal/symfony routing
    // system. So, we have to do the same as what the UrlMatcher does: map HEAD
    // requests to the logic for GET. This also guarantees response headers for
    // HEAD requests are identical to those for GET requests, because we just
    // return a GET response. Response::prepare() will transform it to a HEAD
    // response at the very last moment.
    // @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
    // @see \Symfony\Component\Routing\Matcher\UrlMatcher::matchCollection()
    // @see \Symfony\Component\HttpFoundation\Response::prepare()
    if ($method === 'head') {
      $method = 'get';
    }

    $resource_config_id = $route_match->getRouteObject()->getDefault('_rest_resource_config');
    /** @var \Drupal\rest\RestResourceConfigInterface $resource_config */
    $resource_config = $this->resourceStorage->load($resource_config_id);
    $resource = $resource_config->getResourcePlugin();

    // Deserialize incoming data if available.
    /** @var \Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = $this->container->get('serializer');
    $received = $request->getContent();
    $unserialized = NULL;
    if (!empty($received)) {
      $format = $request->getContentType();

      // Only allow serialization formats that are explicitly configured. If no
      // formats are configured allow all and hope that the serializer knows the
      // format. If the serializer cannot handle it an exception will be thrown
      // that bubbles up to the client.
      $request_method = $request->getMethod();
      if (in_array($format, $resource_config->getFormats($request_method))) {
        $definition = $resource->getPluginDefinition();
        try {
          if (!empty($definition['serialization_class'])) {
            $unserialized = $serializer->deserialize($received, $definition['serialization_class'], $format, array('request_method' => $method));
          }
          // If the plugin does not specify a serialization class just decode
          // the received data.
          else {
            $unserialized = $serializer->decode($received, $format, array('request_method' => $method));
          }
        }
        catch (UnexpectedValueException $e) {
          $error['error'] = $e->getMessage();
          $content = $serializer->serialize($error, $format);
          return new Response($content, 400, array('Content-Type' => $request->getMimeType($format)));
        }
      }
      else {
        throw new UnsupportedMediaTypeHttpException();
      }
    }

    // Determine the request parameters that should be passed to the resource
    // plugin.
    $route_parameters = $route_match->getParameters();
    $parameters = array();
    // Filter out all internal parameters starting with "_".
    foreach ($route_parameters as $key => $parameter) {
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }

    // Invoke the operation on the resource plugin.
    $format = $this->getResponseFormat($route_match, $request);
    $response = call_user_func_array(array($resource, $method), array_merge($parameters, array($unserialized, $request)));

    return $response instanceof ResourceResponseInterface ?
      $this->renderResponse($request, $response, $serializer, $format, $resource_config) :
      $response;
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
  protected function getResponseFormat(RouteMatchInterface $route_match, Request $request) {
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
   * Renders a resource response.
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
   * @param \Drupal\rest\RestResourceConfigInterface $resource_config
   *   The resource config.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The altered response.
   *
   * @todo Add test coverage for language negotiation contexts in
   *   https://www.drupal.org/node/2135829.
   */
  protected function renderResponse(Request $request, ResourceResponseInterface $response, SerializerInterface $serializer, $format, RestResourceConfigInterface $resource_config) {
    $data = $response->getResponseData();

    if ($response instanceof CacheableResponseInterface) {
      // Add rest config's cache tags.
      $response->addCacheableDependency($resource_config);
    }

    // If there is data to send, serialize and set it as the response body.
    if ($data !== NULL) {
      if ($response instanceof CacheableResponseInterface) {
        $context = new RenderContext();
        $output = $this->container->get('renderer')
          ->executeInRenderContext($context, function () use ($serializer, $data, $format) {
            return $serializer->serialize($data, $format);
          });

        if (!$context->isEmpty()) {
          $response->addCacheableDependency($context->pop());
        }
      }
      else {
        $output = $serializer->serialize($data, $format);
      }

      $response->setContent($output);
      $response->headers->set('Content-Type', $request->getMimeType($format));
    }

    return $response;
  }

}
