<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for configurable CKEditor 5 plugins.
 *
 * This allows a CKEditor 5 plugin to define a settings form. These settings can
 * then be automatically passed on to the corresponding CKEditor 5 instance via
 * CKEditor5PluginInterface::getDynamicPluginConfig().
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait
 * @see \Drupal\ckeditor5\CKEditor5PluginInterface
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginBase
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 * @see plugin_api
 */
interface CKEditor5PluginConfigurableInterface extends CKEditor5PluginInterface, ConfigurableInterface, PluginFormInterface {

}
