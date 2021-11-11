<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\EditorInterface;

/**
 * Defines the default CKEditor 5 plugin implementation.
 *
 * When a CKEditor 5 plugin is not configurable nor has dynamic plugin
 * configuration, no custom code needs to be written: this default
 * implementation will be used under the hood.
 *
 * @see @see \Drupal\ckeditor5\Annotation\DrupalAspectsOfCKEditor5Plugin::$class
 */
class CKEditor5PluginDefault extends PluginBase implements CKEditor5PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Ensure the configuration is set as expected for configurable plugins.
    if ($this instanceof CKEditor5PluginConfigurableInterface) {
      $this->setConfiguration($configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    return $static_plugin_config;
  }

}
