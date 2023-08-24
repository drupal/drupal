<?php

namespace Drupal\Core\Field;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Default object used for field_type_categories plugins.
 *
 * @see \Drupal\Core\Field\FieldTypeCategoryManager
 */
class FieldTypeCategory extends PluginBase implements FieldTypeCategoryInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(): array {
    return $this->pluginDefinition['libraries'] ?? [];
  }

}
