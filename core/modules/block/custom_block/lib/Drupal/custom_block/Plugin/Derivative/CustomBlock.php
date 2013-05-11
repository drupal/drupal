<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Derivative\CustomBlock.
 */

namespace Drupal\custom_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Retrieves block plugin definitions for all custom blocks.
 */
class CustomBlock implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   *
   * Retrieves a specific custom block definition from storage.
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   *
   * Retrieves custom block definitions from storage.
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $custom_blocks = entity_load_multiple('custom_block');
    foreach ($custom_blocks as $custom_block) {
      $this->derivatives[$custom_block->uuid->value] = $base_plugin_definition;
      $this->derivatives[$custom_block->uuid->value]['admin_label'] = $custom_block->info->value;
    }
    return $this->derivatives;
  }
}
