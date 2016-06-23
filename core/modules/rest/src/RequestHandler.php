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
use Symfony\Component\HttpKernel\Exception\HttpException;
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
    // All REST routes are restricted to exactly one format, so instead of
    // parsing it out of the Accept headers again, we can simply retrieve the
    // format requirement. If there is no format associated, just pick JSON.
    $format = $route_match->getRouteObject()->getRequirement('_format') ?: 'json';
    try {
      $response = call_user_func_array(array($resource, $method), array_merge($parameters, array($unserialized, $request)));
    }
    catch (HttpException $e) {
      $error['error'] = $e->getMessage();
      $content = $serializer->serialize($error, $format);
      // Add the default content type, but only if the headers from the
      // exception have not specified it already.
      $headers = $e->getHeaders() + array('Content-Type' => $request->getMimeType($format));
      return new Response($content, $e->getStatusCode(), $headers);
    }

    return $response instanceof ResourceResponseInterface ?
      $this->renderResponse($request, $response, $serializer, $format, $resource_config) :
      $response;
  }

  /**
   * Generates a CSRF protecting session token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function csrfToken() {
    return new Response(\Drupal::csrfToken()->get('rest'), 200, array('Content-Type' => 'text/plain'));
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
   * @param string $format
   *   The response format.
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
      $context = new RenderContext();
      $output = $this->container->get('renderer')
        ->executeInRenderContext($context, function () use ($serializer, $data, $format) {
          return $serializer->serialize($data, $format);
        });

      if (!$context->isEmpty()) {
        $response->addCacheableDependency($context->pop());
      }

      // Add rest config's cache tags.
      $response->addCacheableDependency($resource_config);
    }
    else {
      $output = $serializer->serialize($data, $format);
    }
    $response->setContent($output);
    $response->headers->set('Content-Type', $request->getMimeType($format));

    return $response;
  }

}
