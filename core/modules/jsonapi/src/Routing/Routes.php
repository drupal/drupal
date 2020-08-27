<?php

namespace Drupal\jsonapi\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\Access\RelationshipFieldAccess;
use Drupal\jsonapi\Controller\EntryPoint;
use Drupal\jsonapi\ParamConverter\ResourceTypeConverter;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class Routes implements ContainerInjectionInterface {

  /**
   * The service name for the primary JSON:API controller.
   *
   * All resources except the entrypoint are served by this controller.
   *
   * @var string
   */
  const CONTROLLER_SERVICE_NAME = 'jsonapi.entity_resource';

  /**
   * A key with which to flag a route as belonging to the JSON:API module.
   *
   * @var string
   */
  const JSON_API_ROUTE_FLAG_KEY = '_is_jsonapi';

  /**
   * The route default key for the route's resource type information.
   *
   * @var string
   */
  const RESOURCE_TYPE_KEY = 'resource_type';

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * List of providers.
   *
   * @var string[]
   */
  protected $providerIds;

  /**
   * The JSON:API base path.
   *
   * @var string
   */
  protected $jsonApiBasePath;

  /**
   * Instantiates a Routes object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param string[] $authentication_providers
   *   The authentication providers, keyed by ID.
   * @param string $jsonapi_base_path
   *   The JSON:API base path.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, array $authentication_providers, $jsonapi_base_path) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->providerIds = array_keys($authentication_providers);
    assert(is_string($jsonapi_base_path));
    assert(
      $jsonapi_base_path[0] === '/',
      sprintf('The provided base path should contain a leading slash "/". Given: "%s".', $jsonapi_base_path)
    );
    assert(
      substr($jsonapi_base_path, -1) !== '/',
      sprintf('The provided base path should not contain a trailing slash "/". Given: "%s".', $jsonapi_base_path)
    );
    $this->jsonApiBasePath = $jsonapi_base_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->getParameter('authentication_providers'),
      $container->getParameter('jsonapi.base_path')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();
    $upload_routes = new RouteCollection();

    // JSON:API's routes: entry point + routes for every resource type.
    foreach ($this->resourceTypeRepository->all() as $resource_type) {
      $routes->addCollection(static::getRoutesForResourceType($resource_type, $this->jsonApiBasePath));
      $upload_routes->addCollection(static::getFileUploadRoutesForResourceType($resource_type, $this->jsonApiBasePath));
    }
    $routes->add('jsonapi.resource_list', static::getEntryPointRoute($this->jsonApiBasePath));

    // Require the JSON:API media type header on every route, except on file
    // upload routes, where we require `application/octet-stream`.
    $routes->addRequirements(['_content_type_format' => 'api_json']);
    $upload_routes->addRequirements(['_content_type_format' => 'bin']);

    $routes->addCollection($upload_routes);

    // Enable all available authentication providers.
    $routes->addOptions(['_auth' => $this->providerIds]);

    // Flag every route as belonging to the JSON:API module.
    $routes->addDefaults([static::JSON_API_ROUTE_FLAG_KEY => TRUE]);

    // All routes serve only the JSON:API media type.
    $routes->addRequirements(['_format' => 'api_json']);

    return $routes;
  }

  /**
   * Gets applicable resource routes for a JSON:API resource type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON:API resource type for which to get the routes.
   * @param string $path_prefix
   *   The root path prefix.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A collection of routes for the given resource type.
   */
  protected static function getRoutesForResourceType(ResourceType $resource_type, $path_prefix) {
    // Internal resources have no routes.
    if ($resource_type->isInternal()) {
      return new RouteCollection();
    }

    $routes = new RouteCollection();

    // Collection route like `/jsonapi/node/article`.
    if ($resource_type->isLocatable()) {
      $collection_route = new Route("/{$resource_type->getPath()}");
      $collection_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ':getCollection']);
      $collection_route->setMethods(['GET']);
      // Allow anybody access because "view" and "view label" access are checked
      // in the controller.
      $collection_route->setRequirement('_access', 'TRUE');
      $routes->add(static::getRouteName($resource_type, 'collection'), $collection_route);
    }

    // Creation route.
    if ($resource_type->isMutable()) {
      $collection_create_route = new Route("/{$resource_type->getPath()}");
      $collection_create_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ':createIndividual']);
      $collection_create_route->setMethods(['POST']);
      $create_requirement = sprintf("%s:%s", $resource_type->getEntityTypeId(), $resource_type->getBundle());
      $collection_create_route->setRequirement('_entity_create_access', $create_requirement);
      $collection_create_route->setRequirement('_csrf_request_header_token', 'TRUE');
      $routes->add(static::getRouteName($resource_type, 'collection.post'), $collection_create_route);
    }

    // Individual routes like `/jsonapi/node/article/{uuid}` or
    // `/jsonapi/node/article/{uuid}/relationships/uid`.
    $routes->addCollection(static::getIndividualRoutesForResourceType($resource_type));

    // Add the resource type as a parameter to every resource route.
    foreach ($routes as $route) {
      static::addRouteParameter($route, static::RESOURCE_TYPE_KEY, ['type' => ResourceTypeConverter::PARAM_TYPE_ID]);
      $route->addDefaults([static::RESOURCE_TYPE_KEY => $resource_type->getTypeName()]);
    }

    // Resource routes all have the same base path.
    $routes->addPrefix($path_prefix);

    return $routes;
  }

  /**
   * Gets the file upload route collection for the given resource type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for which the route collection should be created.
   * @param string $path_prefix
   *   The root path prefix.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  protected static function getFileUploadRoutesForResourceType(ResourceType $resource_type, $path_prefix) {
    $routes = new RouteCollection();

    // Internal resources have no routes; individual routes require locations.
    if ($resource_type->isInternal() || !$resource_type->isLocatable()) {
      return $routes;
    }

    // File upload routes are only necessary for resource types that have file
    // fields.
    $has_file_field = array_reduce($resource_type->getRelatableResourceTypes(), function ($carry, array $target_resource_types) {
      return $carry || static::hasNonInternalFileTargetResourceTypes($target_resource_types);
    }, FALSE);
    if (!$has_file_field) {
      return $routes;
    }

    if ($resource_type->isMutable()) {
      $path = $resource_type->getPath();
      $entity_type_id = $resource_type->getEntityTypeId();

      $new_resource_file_upload_route = new Route("/{$path}/{file_field_name}");
      $new_resource_file_upload_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => 'jsonapi.file_upload:handleFileUploadForNewResource']);
      $new_resource_file_upload_route->setMethods(['POST']);
      $new_resource_file_upload_route->setRequirement('_csrf_request_header_token', 'TRUE');
      $routes->add(static::getFileUploadRouteName($resource_type, 'new_resource'), $new_resource_file_upload_route);

      $existing_resource_file_upload_route = new Route("/{$path}/{entity}/{file_field_name}");
      $existing_resource_file_upload_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => 'jsonapi.file_upload:handleFileUploadForExistingResource']);
      $existing_resource_file_upload_route->setMethods(['POST']);
      $existing_resource_file_upload_route->setRequirement('_csrf_request_header_token', 'TRUE');
      $routes->add(static::getFileUploadRouteName($resource_type, 'existing_resource'), $existing_resource_file_upload_route);

      // Add entity parameter conversion to every route.
      $routes->addOptions(['parameters' => ['entity' => ['type' => 'entity:' . $entity_type_id]]]);

      // Add the resource type as a parameter to every resource route.
      foreach ($routes as $route) {
        static::addRouteParameter($route, static::RESOURCE_TYPE_KEY, ['type' => ResourceTypeConverter::PARAM_TYPE_ID]);
        $route->addDefaults([static::RESOURCE_TYPE_KEY => $resource_type->getTypeName()]);
      }
    }

    // File upload routes all have the same base path.
    $routes->addPrefix($path_prefix);

    return $routes;
  }

  /**
   * Determines if the given request is for a JSON:API generated route.
   *
   * @param array $defaults
   *   The request's route defaults.
   *
   * @return bool
   *   Whether the request targets a generated route.
   */
  public static function isJsonApiRequest(array $defaults) {
    return isset($defaults[RouteObjectInterface::CONTROLLER_NAME])
      && strpos($defaults[RouteObjectInterface::CONTROLLER_NAME], static::CONTROLLER_SERVICE_NAME) === 0;
  }

  /**
   * Gets a route collection for the given resource type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for which the route collection should be created.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  protected static function getIndividualRoutesForResourceType(ResourceType $resource_type) {
    if (!$resource_type->isLocatable()) {
      return new RouteCollection();
    }

    $routes = new RouteCollection();

    $path = $resource_type->getPath();
    $entity_type_id = $resource_type->getEntityTypeId();

    // Individual read, update and remove.
    $individual_route = new Route("/{$path}/{entity}");
    $individual_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ':getIndividual']);
    $individual_route->setMethods(['GET']);
    // No _entity_access requirement because "view" and "view label" access are
    // checked in the controller. So it's safe to allow anybody access.
    $individual_route->setRequirement('_access', 'TRUE');
    $routes->add(static::getRouteName($resource_type, 'individual'), $individual_route);
    if ($resource_type->isMutable()) {
      $individual_update_route = new Route($individual_route->getPath());
      $individual_update_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ':patchIndividual']);
      $individual_update_route->setMethods(['PATCH']);
      $individual_update_route->setRequirement('_entity_access', "entity.update");
      $individual_update_route->setRequirement('_csrf_request_header_token', 'TRUE');
      $routes->add(static::getRouteName($resource_type, 'individual.patch'), $individual_update_route);
      $individual_remove_route = new Route($individual_route->getPath());
      $individual_remove_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ':deleteIndividual']);
      $individual_remove_route->setMethods(['DELETE']);
      $individual_remove_route->setRequirement('_entity_access', "entity.delete");
      $individual_remove_route->setRequirement('_csrf_request_header_token', 'TRUE');
      $routes->add(static::getRouteName($resource_type, 'individual.delete'), $individual_remove_route);
    }

    foreach ($resource_type->getRelatableResourceTypes() as $relationship_field_name => $target_resource_types) {
      // Read, update, add, or remove an individual resources relationships to
      // other resources.
      $relationship_route = new Route("/{$path}/{entity}/relationships/{$relationship_field_name}");
      $relationship_route->addDefaults(['_on_relationship' => TRUE]);
      $relationship_route->addDefaults(['related' => $relationship_field_name]);
      $relationship_route->setRequirement(RelationshipFieldAccess::ROUTE_REQUIREMENT_KEY, $relationship_field_name);
      $relationship_route->setRequirement('_csrf_request_header_token', 'TRUE');
      $relationship_route_methods = $resource_type->isMutable()
        ? ['GET', 'POST', 'PATCH', 'DELETE']
        : ['GET'];
      $relationship_controller_methods = [
        'GET' => 'getRelationship',
        'POST' => 'addToRelationshipData',
        'PATCH' => 'replaceRelationshipData',
        'DELETE' => 'removeFromRelationshipData',
      ];
      foreach ($relationship_route_methods as $method) {
        $method_specific_relationship_route = clone $relationship_route;
        $method_specific_relationship_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ":{$relationship_controller_methods[$method]}"]);
        $method_specific_relationship_route->setMethods($method);
        $routes->add(static::getRouteName($resource_type, sprintf("%s.relationship.%s", $relationship_field_name, strtolower($method))), $method_specific_relationship_route);
      }

      // Only create routes for related routes that target at least one
      // non-internal resource type.
      if (static::hasNonInternalTargetResourceTypes($target_resource_types)) {
        // Get an individual resource's related resources.
        $related_route = new Route("/{$path}/{entity}/{$relationship_field_name}");
        $related_route->setMethods(['GET']);
        $related_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_SERVICE_NAME . ':getRelated']);
        $related_route->addDefaults(['related' => $relationship_field_name]);
        $related_route->setRequirement(RelationshipFieldAccess::ROUTE_REQUIREMENT_KEY, $relationship_field_name);
        $routes->add(static::getRouteName($resource_type, "$relationship_field_name.related"), $related_route);
      }
    }

    // Add entity parameter conversion to every route.
    $routes->addOptions(['parameters' => ['entity' => ['type' => 'entity:' . $entity_type_id]]]);

    return $routes;
  }

  /**
   * Provides the entry point route.
   *
   * @param string $path_prefix
   *   The root path prefix.
   *
   * @return \Symfony\Component\Routing\Route
   *   The entry point route.
   */
  protected function getEntryPointRoute($path_prefix) {
    $entry_point = new Route("/{$path_prefix}");
    $entry_point->addDefaults([RouteObjectInterface::CONTROLLER_NAME => EntryPoint::class . '::index']);
    $entry_point->setRequirement('_access', 'TRUE');
    $entry_point->setMethods(['GET']);
    return $entry_point;
  }

  /**
   * Adds a parameter option to a route, overrides options of the same name.
   *
   * The Symfony Route class only has a method for adding options which
   * overrides any previous values. Therefore, it is tedious to add a single
   * parameter while keeping those that are already set.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to which the parameter is to be added.
   * @param string $name
   *   The name of the parameter.
   * @param mixed $parameter
   *   The parameter's options.
   */
  protected static function addRouteParameter(Route $route, $name, $parameter) {
    $parameters = $route->getOption('parameters') ?: [];
    $parameters[$name] = $parameter;
    $route->setOption('parameters', $parameters);
  }

  /**
   * Get a unique route name for the JSON:API resource type and route type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for which the route collection should be created.
   * @param string $route_type
   *   The route type. E.g. 'individual' or 'collection'.
   *
   * @return string
   *   The generated route name.
   */
  public static function getRouteName(ResourceType $resource_type, $route_type) {
    return sprintf('jsonapi.%s.%s', $resource_type->getTypeName(), $route_type);
  }

  /**
   * Get a unique route name for the file upload resource type and route type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type for which the route collection should be created.
   * @param string $route_type
   *   The route type. E.g. 'individual' or 'collection'.
   *
   * @return string
   *   The generated route name.
   */
  protected static function getFileUploadRouteName(ResourceType $resource_type, $route_type) {
    return sprintf('jsonapi.%s.%s.%s', $resource_type->getTypeName(), 'file_upload', $route_type);
  }

  /**
   * Determines if an array of resource types has any non-internal ones.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types to check.
   *
   * @return bool
   *   TRUE if there is at least one non-internal resource type in the given
   *   array; FALSE otherwise.
   */
  protected static function hasNonInternalTargetResourceTypes(array $resource_types) {
    return array_reduce($resource_types, function ($carry, ResourceType $target) {
      return $carry || !$target->isInternal();
    }, FALSE);
  }

  /**
   * Determines if an array of resource types lists non-internal "file" ones.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types to check.
   *
   * @return bool
   *   TRUE if there is at least one non-internal "file" resource type in the
   *   given array; FALSE otherwise.
   */
  protected static function hasNonInternalFileTargetResourceTypes(array $resource_types) {
    return array_reduce($resource_types, function ($carry, ResourceType $target) {
      return $carry || (!$target->isInternal() && $target->getEntityTypeId() === 'file');
    }, FALSE);
  }

  /**
   * Gets the resource type from a route or request's parameters.
   *
   * @param array $parameters
   *   An array of parameters. These may be obtained from a route's
   *   parameter defaults or from a request object.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType|null
   *   The resource type, NULL if one cannot be found from the given parameters.
   */
  public static function getResourceTypeNameFromParameters(array $parameters) {
    if (isset($parameters[static::JSON_API_ROUTE_FLAG_KEY]) && $parameters[static::JSON_API_ROUTE_FLAG_KEY]) {
      return isset($parameters[static::RESOURCE_TYPE_KEY]) ? $parameters[static::RESOURCE_TYPE_KEY] : NULL;
    }
    return NULL;
  }

  /**
   * Invalidates any JSON:API resource type dependent responses and routes.
   */
  public static function rebuild() {
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['jsonapi_resource_types']);
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

}
