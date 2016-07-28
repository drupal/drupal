<?php

namespace Drupal\Core\Plugin;

/**
 * Provides an interface for retrieving form objects for plugins.
 *
 * This allows a plugin to define multiple forms, in addition to the plugin
 * itself providing a form. All forms, decoupled or self-contained, must
 * implement \Drupal\Core\Plugin\PluginFormInterface. Decoupled forms can
 * implement \Drupal\Component\Plugin\PluginAwareInterface in order to gain
 * access to the plugin.
 */
interface PluginFormFactoryInterface {

  /**
   * Creates a new form instance.
   *
   * @param \Drupal\Core\Plugin\PluginWithFormsInterface $plugin
   *   The plugin the form is for.
   * @param string $operation
   *   The name of the operation to use, e.g., 'add' or 'edit'.
   * @param string $fallback_operation
   *   (optional) The name of the fallback operation to use.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   A plugin form instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function createInstance(PluginWithFormsInterface $plugin, $operation, $fallback_operation = NULL);

}
