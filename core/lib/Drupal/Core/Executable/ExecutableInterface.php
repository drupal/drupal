<?php

namespace Drupal\Core\Executable;

/**
 * An interface for executable plugins.
 *
 * @ingroup plugin_api
 */
interface ExecutableInterface {

  /**
   * Executes the plugin.
   *
   * @param object|null $object
   *   (optional) An object to execute the plugin on/with.
   */
  public function execute(?object $object = NULL);

}
