<?php

namespace Drupal\Core\Entity\Plugin\Condition\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver that creates a condition for each entity type with bundles.
 */
class EntityBundle extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityBundle.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasKey('bundle')) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['label'] = $entity_type->getBundleLabel();
        $this->derivatives[$entity_type_id]['provider'] = $entity_type->getProvider();
        $this->derivatives[$entity_type_id]['context_definitions'] = [
          $entity_type_id => EntityContextDefinition::fromEntityType($entity_type),
        ];
      }
    }
    return $this->derivatives;
  }

}
