<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\editor\EditorInterface;

/**
 * Defines an interface for CKEditor5 plugins.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginBase
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 * @see plugin_api
 */
interface CKEditor5PluginInterface extends PluginInspectionInterface {

  /**
   * Allows a plugin to modify its static configuration.
   *
   * @param array $static_plugin_config
   *   The ckeditor5.config entry from the YAML or annotation, if any. If none
   *   is specified in the YAML or annotation, then the empty array.
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return array
   *   Returns the received $static_plugin_config plus dynamic additions or
   *   alterations.
   *
   * @see \Drupal\ckeditor5\Annotation\CKEditor5AspectsOfCKEditor5Plugin::$config
   * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition::getCKEditor5Config()
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array;

}
