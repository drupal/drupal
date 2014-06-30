<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Derivative\CustomBlock.
 */

namespace Drupal\custom_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;

/**
 * Retrieves block plugin definitions for all custom blocks.
 */
class CustomBlock extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $custom_blocks = entity_load_multiple('custom_block');
    foreach ($custom_blocks as $custom_block) {
      $this->derivatives[$custom_block->uuid()] = $base_plugin_definition;
      $this->derivatives[$custom_block->uuid()]['admin_label'] = $custom_block->label();
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
