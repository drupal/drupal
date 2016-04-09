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
   */
  public function execute();

}
