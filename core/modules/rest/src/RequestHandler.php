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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

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
    // Symfony is built to transparently map HEAD requests to a GET request. In
    // the case of the REST module's RequestHandler though, we essentially have
    // our own light-weight routing system on top of the Drupal/symfony routing
    // system. So, we have to respect the decision that the routing system made:
    // we look not at the request method, but at the route's method. All REST
    // routes are guaranteed to have _method set.
    // Response::prepare() will transform it to a HEAD response at the very last
    // moment.
    // @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
    // @see \Symfony\Component\Routing\Matcher\UrlMatcher::matchCollection()
    // @see \Symfony\Component\HttpFoundation\Response::prepare()
    $method = strtolower($route_match->getRouteObject()->getMethods()[0]);
    assert(count($route_match->getRouteObject()->getMethods()) === 1);

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

      // First decode the request data. We can then determine if the
      // serialized data was malformed.
      try {
        $unserialized = $serializer->decode($received, $format, ['request_method' => $method]);
      }
      catch (UnexpectedValueException $e) {
        // If an exception was thrown at this stage, there was a problem
        // decoding the data. Throw a 400 http exception.
        throw new BadRequestHttpException($e->getMessage());
      }

      // Then attempt to denormalize if there is a serialization class.
      if (!empty($definition['serialization_class'])) {
        try {
          $unserialized = $serializer->denormalize($unserialized, $definition['serialization_class'], $format, ['request_method' => $method]);
        }
        // These two serialization exception types mean there was a problem
        // with the structure of the decoded data and it's not valid.
        catch (UnexpectedValueException $e) {
          throw new UnprocessableEntityHttpException($e->getMessage());
        }
        catch (InvalidArgumentException $e) {
          throw new UnprocessableEntityHttpException($e->getMessage());
        }
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
