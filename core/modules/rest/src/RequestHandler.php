<?php

namespace Drupal\rest;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Acts as intermediate request forwarder for resource plugins.
 *
 * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
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

      $definition = $resource->getPluginDefinition();
      try {
        if (!empty($definition['serialization_class'])) {
          $unserialized = $serializer->deserialize($received, $definition['serialization_class'], $format, ['request_method' => $method]);
        }
        // If the plugin does not specify a serialization class just decode
        // the received data.
        else {
          $unserialized = $serializer->decode($received, $format, ['request_method' => $method]);
        }
      }
      catch (UnexpectedValueException $e) {
        throw new BadRequestHttpException($e->getMessage());
      }
    }

    // Determine the request parameters that should be passed to the resource
    // plugin.
    $route_parameters = $route_match->getParameters();
    $parameters = [];
    // Filter out all internal parameters starting with "_".
    foreach ($route_parameters as $key => $parameter) {
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }

    // Invoke the operation on the resource plugin.
    $response = call_user_func_array([$resource, $method], array_merge($parameters, [$unserialized, $request]));

    if ($response instanceof CacheableResponseInterface) {
      // Add rest config's cache tags.
      $response->addCacheableDependency($resource_config);
    }

    return $response;
  }

}
