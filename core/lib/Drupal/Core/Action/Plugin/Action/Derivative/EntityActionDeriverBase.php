<?php

namespace Drupal\Core\Action\Plugin\Action\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base action for each entity type with specific interfaces.
 */
abstract class EntityActionDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityActionDeriverBase object.
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
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Indicates whether the deriver can be used for the provided entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return bool
   *   TRUE if the entity type can be used, FALSE otherwise.
   */
  abstract protected function isApplicable(EntityTypeInterface $entity_type);

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach ($this->getApplicableEntityTypes() as $entity_type_id => $entity_type) {
        $definition = $base_plugin_definition;
        $definition['type'] = $entity_type_id;
        $definition['label'] = sprintf('%s %s', $base_plugin_definition['action_label'], $entity_type->getSingularLabel());
        $definitions[$entity_type_id] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  /**
   * Gets a list of applicable entity types.
   *
   * The list consists of all entity types which match the conditions for the
   * given deriver.
   * For example, if the action applies to entities that are publishable,
   * this method will find all entity types that are publishable.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The applicable entity types, keyed by entity type ID.
   */
  protected function getApplicableEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
      return $this->isApplicable($entity_type);
    });

    return $entity_types;
  }

}
