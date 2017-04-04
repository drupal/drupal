<?php

namespace Drupal\rest\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\rest\RestResourceConfigInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for REST-style routes.
 */
class ResourceRoutes extends RouteSubscriberBase {

  /**
   * The plugin manager for REST plugins.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * The REST resource config storage.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $resourceConfigStorage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $manager
   *   The resource plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ResourcePluginManager $manager, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->manager = $manager;
    $this->resourceConfigStorage = $entity_type_manager->getStorage('rest_resource_config');
    $this->logger = $logger;
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   * @return array
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Iterate over all enabled REST resource config entities.
    /** @var \Drupal\rest\RestResourceConfigInterface[] $resource_configs */
    $resource_configs = $this->resourceConfigStorage->loadMultiple();
    foreach ($resource_configs as $resource_config) {
      if ($resource_config->status()) {
        $resource_routes = $this->getRoutesForResourceConfig($resource_config);
        $collection->addCollection($resource_routes);
      }
    }
  }

  /**
   * Provides all routes for a given REST resource config.
   *
   * This method determines where a resource is reachable, what path
   * replacements are used, the required HTTP method for the operation etc.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $rest_resource_config
   *   The rest resource config.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  protected function getRoutesForResourceConfig(RestResourceConfigInterface $rest_resource_config) {
    $plugin = $rest_resource_config->getResourcePlugin();
    $collection = new RouteCollection();

    foreach ($plugin->routes() as $name => $route) {
      /** @var \Symfony\Component\Routing\Route $route */
      // @todo: Are multiple methods possible here?
      $methods = $route->getMethods();
      // Only expose routes where the method is enabled in the configuration.
      if ($methods && ($method = $methods[0]) && $supported_formats = $rest_resource_config->getFormats($method)) {
        $route->setRequirement('_csrf_request_header_token', 'TRUE');

        // Check that authentication providers are defined.
        if (empty($rest_resource_config->getAuthenticationProviders($method))) {
          $this->logger->error('At least one authentication provider must be defined for resource @id', [':id' => $rest_resource_config->id()]);
          continue;
        }

        // Check that formats are defined.
        if (empty($rest_resource_config->getFormats($method))) {
          $this->logger->error('At least one format must be defined for resource @id', [':id' => $rest_resource_config->id()]);
          continue;
        }

        // If the route has a format requirement, then verify that the
        // resource has it.
        $format_requirement = $route->getRequirement('_format');
        if ($format_requirement && !in_array($format_requirement, $rest_resource_config->getFormats($method))) {
          continue;
        }

        // The configuration has been validated, so we update the route to:
        // - set the allowed request body content types/formats for methods that
        //   allow request bodies to be sent
        // - set the allowed authentication providers
        if (in_array($method, ['POST', 'PATCH', 'PUT'], TRUE)) {
          // Restrict the incoming HTTP Content-type header to the allowed
          // formats.
          $route->addRequirements(['_content_type_format' => implode('|', $rest_resource_config->getFormats($method))]);
        }
        $route->setOption('_auth', $rest_resource_config->getAuthenticationProviders($method));
        $route->setDefault('_rest_resource_config', $rest_resource_config->id());
        $collection->add("rest.$name", $route);
      }

    }
    return $collection;
  }

}
