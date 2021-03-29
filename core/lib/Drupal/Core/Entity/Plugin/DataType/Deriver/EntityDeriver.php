<?php

namespace Drupal\Core\Entity\Plugin\DataType\Deriver;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service
   *   The bundle info service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info_service) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
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
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $class = $entity_type->entityClassImplements(ConfigEntityInterface::class) ? ConfigEntityAdapter::class : EntityAdapter::class;
      $this->derivatives[$entity_type_id] = [
        'class' => $class,
        'label' => $entity_type->getLabel(),
        'constraints' => $entity_type->getConstraints(),
        'internal' => $entity_type->isInternal(),
      ] + $base_plugin_definition;

      // Incorporate the bundles as entity:$entity_type:$bundle, if any.
      $bundle_info = $this->bundleInfoService->getBundleInfo($entity_type_id);
      if (count($bundle_info) > 1 || $entity_type->getKey('bundle')) {
        foreach ($bundle_info as $bundle => $info) {
          $this->derivatives[$entity_type_id . ':' . $bundle] = [
            'class' => $class,
            'label' => $info['label'],
            'constraints' => $this->derivatives[$entity_type_id]['constraints'],
          ] + $base_plugin_definition;
        }
      }
    }
    return $this->derivatives;
  }

}
