<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CKEditor4To5Upgrade annotation object.
 *
 * Plugin Namespace: Plugin\CKEditor4To5Upgrade.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class CKEditor4To5Upgrade extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The CKEditor 4 buttons for which an upgrade path is provided.
   *
   * @var string[]
   */
  public $cke4_buttons;

  /**
   * The CKEditor 4 plugins for whose settings an upgrade path is provided.
   *
   * @var string[]
   */
  public $cke4_plugin_settings;

  /**
   * The CKEditor 5 plugins with configurable subset with upgrade path provided.
   *
   * @var string[]
   */
  public $cke5_plugin_elements_subset_configuration;

}
