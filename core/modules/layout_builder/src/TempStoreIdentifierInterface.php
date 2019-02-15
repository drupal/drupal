<?php

namespace Drupal\layout_builder;

/**
 * Provides an interface that allows an object to provide its own tempstore key.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @todo Move to \Drupal\Core\TempStore in https://www.drupal.org/node/3026957.
 */
interface TempStoreIdentifierInterface {

  /**
   * Gets a string suitable for use as a tempstore key.
   *
   * @return string
   *   A string to be used as the key for a tempstore item.
   */
  public function getTempstoreKey();

}
