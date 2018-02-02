<?php

namespace Drupal\Core\Entity\Plugin\DataType\Deriver;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides data type plugins for each existing entity type and bundle.
 */
class EntityDeriver implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * Constructs an EntityDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct($base_plugin_id, EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $bundle_info_service) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $entity_manager;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Also keep the 'entity' defined as is.
    $this->derivatives[''] = $base_plugin_definition;
    // Add definitions for each entity type and bundle.
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id] = [
        'label' => $entity_type->getLabel(),
        'constraints' => $entity_type->getConstraints(),
        'internal' => $entity_type->isInternal(),
      ] + $base_plugin_definition;

      // Incorporate the bundles as entity:$entity_type:$bundle, if any.
      foreach ($this->bundleInfoService->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
        if ($bundle !== $entity_type_id) {
          $this->derivatives[$entity_type_id . ':' . $bundle] = [
            'label' => $bundle_info['label'],
            'constraints' => $this->derivatives[$entity_type_id]['constraints']
          ] + $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
