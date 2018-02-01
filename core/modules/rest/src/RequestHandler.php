<?php

namespace Drupal\rest;

use Drupal\Component\Utility\ArgumentsResolver;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rest\Plugin\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Acts as intermediate request forwarder for resource plugins.
 *
 * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 */
class RequestHandler implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface
   */
  protected $serializer;

  /**
   * Creates a new RequestHandler instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\Serializer\SerializerInterface|\Symfony\Component\Serializer\Encoder\DecoderInterface $serializer
   *   The serializer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SerializerInterface $serializer) {
    $this->configFactory = $config_factory;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('serializer')
    );
  }

  /**
   * Handles a REST API request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\rest\RestResourceConfigInterface $_rest_resource_config
   *   REST resource config entity ID.
   *
   * @return \Drupal\rest\ResourceResponseInterface|\Symfony\Component\HttpFoundation\Response
   *   The REST resource response.
   */
  public function handle(RouteMatchInterface $route_match, Request $request, RestResourceConfigInterface $_rest_resource_config) {
    $response = $this->delegateToRestResourcePlugin($route_match, $request, $_rest_resource_config->getResourcePlugin());

    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($_rest_resource_config);
      // Add global rest settings config's cache tag, for BC flags.
      // @see \Drupal\rest\Plugin\rest\resource\EntityResource::permissions()
      // @see \Drupal\rest\EventSubscriber\RestConfigSubscriber
      // @todo Remove in https://www.drupal.org/node/2893804
      $response->addCacheableDependency($this->configFactory->get('rest.settings'));
    }

    return $response;
  }

  /**
   * Gets the normalized HTTP request method of the matched route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return string
   *   The normalized HTTP request method.
   */
  protected static function getNormalizedRequestMethod(RouteMatchInterface $route_match) {
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
    return $method;
  }

  /**
   * Deserializes request body, if any.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\rest\Plugin\ResourceInterface $resource
   *   The REST resource plugin.
   *
   * @return array|null
   *   An object normalization, ikf there is a valid request body. NULL if there
   *   is no request body.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown if the request body cannot be decoded.
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   Thrown if the request body cannot be denormalized.
   */
  protected function deserialize(RouteMatchInterface $route_match, Request $request, ResourceInterface $resource) {
    // Deserialize incoming data if available.
    $received = $request->getContent();
    $unserialized = NULL;
    if (!empty($received)) {
      $method = static::getNormalizedRequestMethod($route_match);
      $format = $request->getContentType();

      $definition = $resource->getPluginDefinition();

      // First decode the request data. We can then determine if the
      // serialized data was malformed.
      try {
        $unserialized = $this->serializer->decode($received, $format, ['request_method' => $method]);
      }
      catch (UnexpectedValueException $e) {
        // If an exception was thrown at this stage, there was a problem
        // decoding the data. Throw a 400 http exception.
        throw new BadRequestHttpException($e->getMessage());
      }

      // Then attempt to denormalize if there is a serialization class.
      if (!empty($definition['serialization_class'])) {
        try {
          $unserialized = $this->serializer->denormalize($unserialized, $definition['serialization_class'], $format, ['request_method' => $method]);
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

    return $unserialized;
  }

  /**
   * Delegates an incoming request to the appropriate REST resource plugin.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\rest\Plugin\ResourceInterface $resource
   *   The REST resource plugin.
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Drupal\rest\ResourceResponseInterface
   *   The REST resource response.
   */
  protected function delegateToRestResourcePlugin(RouteMatchInterface $route_match, Request $request, ResourceInterface $resource) {
    $unserialized = $this->deserialize($route_match, $request, $resource);
    $method = static::getNormalizedRequestMethod($route_match);

    // Determine the request parameters that should be passed to the resource
    // plugin.
    $argument_resolver = $this->createArgumentResolver($route_match, $unserialized, $request);
    try {
      $arguments = $argument_resolver->getArguments([$resource, $method]);
    }
    catch (\RuntimeException $exception) {
      @trigger_error('Passing in arguments the legacy way is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Provide the right parameter names in the method, similar to controllers. See https://www.drupal.org/node/2894819', E_USER_DEPRECATED);
      $arguments = $this->getLegacyParameters($route_match, $unserialized, $request);
    }

    // Invoke the operation on the resource plugin.
    return call_user_func_array([$resource, $method], $arguments);
  }

  /**
   * Creates an argument resolver, containing all REST parameters.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param mixed $unserialized
   *   The unserialized data.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Component\Utility\ArgumentsResolver
   *   An instance of the argument resolver containing information like the
   *   'entity' we process and the 'unserialized' content from the request body.
   */
  protected function createArgumentResolver(RouteMatchInterface $route_match, $unserialized, Request $request) {
    $route = $route_match->getRouteObject();

    // Defaults for the parameters defined on the route object need to be added
    // to the raw arguments.
    $raw_route_arguments = $route_match->getRawParameters()->all() + $route->getDefaults();

    $route_arguments = $route_match->getParameters()->all();
    $upcasted_route_arguments = $route_arguments;

    // For request methods that have request bodies, ResourceInterface plugin
    // methods historically receive the unserialized request body as the N+1th
    // method argument, where N is the number of route parameters specified on
    // the accompanying route. To be able to use the argument resolver, which is
    // not based on position but on name and typehint, specify commonly used
    // names here. Similarly, those methods receive the original stored object
    // as the first method argument.

    $route_arguments_entity = NULL;
    // Try to find a parameter which is an entity.
    foreach ($route_arguments as $value) {
      if ($value instanceof EntityInterface) {
        $route_arguments_entity = $value;
        break;
      }
    }

    if (in_array($request->getMethod(), ['PATCH', 'POST'], TRUE)) {
      $upcasted_route_arguments['entity'] = $unserialized;
      $upcasted_route_arguments['data'] = $unserialized;
      $upcasted_route_arguments['unserialized'] = $unserialized;
      $upcasted_route_arguments['original_entity'] = $route_arguments_entity;
    }
    else {
      $upcasted_route_arguments['entity'] = $route_arguments_entity;
    }

    // Parameters which are not defined on the route object, but still are
    // essential for access checking are passed as wildcards to the argument
    // resolver.
    $wildcard_arguments = [$route, $route_match];
    $wildcard_arguments[] = $request;
    if (isset($unserialized)) {
      $wildcard_arguments[] = $unserialized;
    }

    return new ArgumentsResolver($raw_route_arguments, $upcasted_route_arguments, $wildcard_arguments);
  }

  /**
   * Provides the parameter usable without an argument resolver.
   *
   * This creates an list of parameters in a statically defined order.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match
   * @param mixed $unserialized
   *   The unserialized data.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @deprecated in Drupal 8.4.0, will be removed before Drupal 9.0.0. Use the
   *   argument resolver method instead, see ::createArgumentResolver().
   *
   * @see https://www.drupal.org/node/2894819
   *
   * @return array
   *   An array of parameters.
   */
  protected function getLegacyParameters(RouteMatchInterface $route_match, $unserialized, Request $request) {
    $route_parameters = $route_match->getParameters();
    $parameters = [];
    // Filter out all internal parameters starting with "_".
    foreach ($route_parameters as $key => $parameter) {
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }

    return array_merge($parameters, [$unserialized, $request]);
  }

}
