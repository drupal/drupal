<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Derivative\EntityDerivative.
 */

namespace Drupal\rest\Plugin\Derivative;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides a resource plugin definition for every entity type.
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class EntityDerivative implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs an EntityDerivative object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(EntityManagerInterface $entity_manager, RouteProviderInterface $route_provider, RouteBuilderInterface $route_builder) {
    $this->entityManager = $entity_manager;
    $this->routeProvider = $route_provider;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager'),
      $container->get('router.route_provider'),
      $container->get('router.builder')
    );
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      // Add in the default plugin configuration and the resource type.
      foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
        $this->derivatives[$entity_type_id] = array(
          'id' => 'entity:' . $entity_type_id,
          'entity_type' => $entity_type_id,
          'serialization_class' => $entity_type->getClass(),
          'label' => $entity_type->getLabel(),
        );

        $default_uris = array(
          'canonical' => "/entity/$entity_type_id/" . '{' . $entity_type_id . '}',
          'http://drupal.org/link-relations/create' => "/entity/$entity_type_id",
        );

        foreach ($default_uris as $link_relation => $default_uri) {
          // Check if there are link templates defined for the entity type and
          // use the path from the route instead of the default.
          if ($route_name = $entity_type->getLinkTemplate($link_relation)) {
            // @todo remove the try/catch as part of
            // http://drupal.org/node/2281645
            try {
              if (($collection = $this->routeBuilder->getCollectionDuringRebuild()) && $route = $collection->get($route_name)) {
              }
              else {
                $route = $this->routeProvider->getRouteByName($route_name);
              }
              $this->derivatives[$entity_type_id]['uri_paths'][$link_relation] = $route->getPath();
            }
            catch (RouteNotFoundException $e) {
              // If the route does not exist it means we are in a brittle state
              // of module enabling/disabling, so we simply exclude this entity
              // type.
              unset($this->derivatives[$entity_type_id]);
              // Continue with the next entity type;
              continue 2;
            }
          }
          else {
            $this->derivatives[$entity_type_id]['uri_paths'][$link_relation] = $default_uri;
          }
        }

        $this->derivatives[$entity_type_id] += $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }
}
