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
    $results = db_query('SELECT * FROM {block_custom}');
    foreach ($results as $result) {
      $this->derivatives[$result->bid] = $base_plugin_definition;
      $this->derivatives[$result->bid]['settings'] = array(
        'info' => $result->info,
        'body' => $result->body,
        'format' => $result->format,
      ) + $base_plugin_definition['settings'];
      $this->derivatives[$result->bid]['subject'] = $result->info;
    }
    $this->derivatives['custom_block'] = $base_plugin_definition;
    return $this->derivatives;
  }
}
