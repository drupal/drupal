<?php

/**
 * @file
 * Contains \Drupal\config_translation\Plugin\Menu\LocalTask\ConfigTranslationLocalTask.
 */

namespace Drupal\config_translation\Plugin\Menu\LocalTask;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a local task plugin with a dynamic title.
 */
class ConfigTranslationLocalTask extends LocalTaskDefault {
  use StringTranslationTrait;

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // Take custom 'config_translation_plugin_id' plugin definition key to
    // retrieve title. We need to retrieve a runtime title (as opposed to
    // storing the title on the plugin definition for the link) because
    // it contains translated parts that we need in the runtime language.
    $type_name = Unicode::strtolower($this->mapperManager()->createInstance($this->pluginDefinition['config_translation_plugin_id'])->getTypeLabel());
    return $this->t('Translate @type_name', array('@type_name' => $type_name));
  }

  /**
   * Gets the mapper manager.
   *
   * @return \Drupal\config_translation\ConfigMapperManagerInterface
   *   The mapper manager.
   */
  protected function mapperManager() {
    if (!$this->mapperManager) {
      $this->mapperManager = \Drupal::service('plugin.manager.config_translation.mapper');
    }
    return $this->mapperManager;
  }

}
