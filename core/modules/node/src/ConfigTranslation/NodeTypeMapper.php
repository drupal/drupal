<?php

/**
 * @file
 * Contains \Drupal\node\ConfigTranslation\NodeTypeMapper.
 */

namespace Drupal\node\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a configuration mapper for node types.
 */
class NodeTypeMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  public function setEntity(ConfigEntityInterface $entity) {
    parent::setEntity($entity);

    // Adds the title label to the translation form.
    $node_type = $entity->id();
    $this->addConfigName("core.base_field_override.node.$node_type.title");
  }

}
