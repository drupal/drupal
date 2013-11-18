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
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    $parameters = parent::getBaseRouteParameters();
    // @todo All core content entity path placeholders can be fully filled in
    //   with an additional {bundle} value in their paths, but a more
    //   predictable solution would be ideal. See
    //   https://drupal.org/node/2134871
    // @todo Field instances have no method to return the bundle the instance is
    //   attached to. See https://drupal.org/node/2134861
    $parameters['bundle'] = $this->entity->bundle;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    $base_entity_info = $this->entityManager->getDefinition($this->pluginDefinition['base_entity_type']);
    return $this->t('@label fields', array('@label' => $base_entity_info['label']));
  }

}
