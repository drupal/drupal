<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for plugins which have forms.
 *
 * Plugin forms are embeddable forms referenced by the plugin annotation.
 * Used by plugin types which have a larger number of plugin-specific forms.
 */
interface PluginWithFormsInterface extends PluginInspectionInterface {

  /**
   * Gets the form class for the given operation.
   *
   * @param string $operation
   *   The name of the operation.
   *
   * @return string|null
   *   The form class if defined, NULL otherwise.
   */
  public function getFormClass($operation);

  /**
   * Gets whether the plugin has a form class for the given operation.
   *
   * @param string $operation
   *   The name of the operation.
   *
   * @return bool
   *   TRUE if the plugin has a form class for the given operation.
   */
  public function hasFormClass($operation);

}
