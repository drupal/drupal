<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigFieldMapper.
 */

namespace Drupal\config_translation;

/**
 * Configuration mapper for fields.
 *
 * On top of plugin definition values on ConfigEntityMapper, the plugin
 * definition for field mappers are required to contain the following
 * additional keys:
 * - base_entity_type: The name of the entity type the fields are attached to.
 */
class ConfigFieldMapper extends ConfigEntityMapper {

  /**
   * Loaded entity instance to help produce the translation interface.
   *
   * @var \Drupal\field\FieldConfigInterface
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
