<?php

/**
 * @file
 * Contains \Drupal\Core\Executable\ExecutableManagerInterface.
 */

namespace Drupal\Core\Executable;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * An interface for managers of executable plugins.
 */
interface ExecutableManagerInterface extends PluginManagerInterface {

  /**
   * Executes an executable plugin.
   *
   * @param \Drupal\Core\Executable\ExecutableInterface $plugin
   *   An executable plugin instance managed by the implementing manager.
   *
   * @return mixed
   *   The returned data varies by plugin implementation, e.g. conditions return
   *   the boolean evaluation result.
   *
   * @throws \Drupal\Core\Executable\ExecutableException
   *   If the plugin could not be executed.
   */
  public function execute(ExecutableInterface $plugin);

}
