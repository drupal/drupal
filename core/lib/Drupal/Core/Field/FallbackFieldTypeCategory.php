<?php

namespace Drupal\Core\Field;

/**
 * Fallback plugin class for FieldTypeCategoryManager.
 */
class FallbackFieldTypeCategory extends FieldTypeCategory {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration) {
    $plugin_definition = [
      'label' => $configuration['label'] ?? '',
      'description' => $configuration['description'] ?? '',
      'weight' => $configuration['weight'] ?? 0,
    ];
    parent::__construct($configuration, $configuration['unique_identifier'], $plugin_definition);
  }

}
