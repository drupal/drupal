<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Deriver\EntityDerivative.
 */

namespace Drupal\rest\Plugin\Deriver;

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
class EntityDeriver implements ContainerDeriverInterface {

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
   * Constructs an EntityDerivative object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
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
          if ($link_template = $entity_type->getLinkTemplate($link_relation)) {
            $this->derivatives[$entity_type_id]['uri_paths'][$link_relation] = '/' . $link_template;
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
