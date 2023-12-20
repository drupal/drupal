<?php

namespace Drupal\Core\Field;

/**
 * Fallback plugin class for FieldTypeCategoryManager.
 */
class FallbackFieldTypeCategory extends FieldTypeCategory {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition) {
    $plugin_id = $configuration['unique_identifier'];
    $plugin_definition = [
      'label' => $configuration['label'],
      'description' => $configuration['description'] ?? '',
      'weight' => $configuration['weight'] ?? 0,
    ] + $plugin_definition;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
