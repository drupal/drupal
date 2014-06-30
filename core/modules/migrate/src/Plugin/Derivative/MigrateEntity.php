<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\Derivative\MigrateEntity.
 */

namespace Drupal\migrate\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrateEntity implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The entity definitions
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityDefinitions;

  /**
   * Constructs a MigrateEntity object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_definitions
   *   A list of entity definition objects.
   */
  public function __construct(array $entity_definitions) {
    $this->entityDefinitions = $entity_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getDefinitions()
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
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityDefinitions as $entity_type => $entity_info) {
      $class = is_subclass_of($entity_info->getClass(), 'Drupal\Core\Config\Entity\ConfigEntityInterface') ?
        'Drupal\migrate\Plugin\migrate\destination\EntityConfigBase' :
        'Drupal\migrate\Plugin\migrate\destination\EntityContentBase';
      $this->derivatives[$entity_type] = array(
        'id' => "entity:$entity_type",
        'class' => $class,
        'requirements_met' => 1,
      );
    }
    return $this->derivatives;
  }

}
