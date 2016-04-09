<?php

namespace Drupal\Core\Operations;

/**
 * Defines an interface for providing operations links.
 */
interface OperationsProviderInterface {

  /**
   * Returns a list of operation links available for this block.
   *
   * @return array
   *   Array of operation links.
   */
  public function getOperationLinks();

}
