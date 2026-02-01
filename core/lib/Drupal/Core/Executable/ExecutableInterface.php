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
   * phpcs:ignore Drupal.Commenting.FunctionComment.ParamNameNoMatch
   * @param object|null $object
   *   (optional) An object to execute the plugin on/with.
   *
   * @see https://www.drupal.org/project/drupal/issues/3354672
   *
   * @todo Uncomment the new $object method parameter before drupal:12.0.0.
   */
  public function execute(/* ?object $object = NULL */);

}
