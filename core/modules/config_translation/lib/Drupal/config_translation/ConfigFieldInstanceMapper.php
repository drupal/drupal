<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigFieldInstanceMapper.
 */

namespace Drupal\config_translation;

/**
 * Configuration mapper for field instances.
 *
 * On top of plugin definition values on ConfigEntityMapper, the plugin
 * definition for field instance mappers are required to contain the following
 * additional keys:
 * - base_entity_type: The name of the entity type the field instances are
 *   attached to.
 */
class ConfigFieldInstanceMapper extends ConfigEntityMapper {

  /**
   * Loaded entity instance to help produce the translation interface.
   *
   * @var \Drupal\field\FieldInstanceConfigInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    $parameters = parent::getBaseRouteParameters();
    $base_entity_info = $this->entityManager->getDefinition($this->pluginDefinition['base_entity_type']);
    $parameters[$base_entity_info->getBundleEntityType()] = $this->entity->targetBundle();
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    $base_entity_info = $this->entityManager->getDefinition($this->pluginDefinition['base_entity_type']);
    return $this->t('@label fields', array('@label' => $base_entity_info->getLabel()));
  }

}
