<?php

/**
 * @file
 * Contains \Drupal\rest\LinkManager\ConfigurableLinkManagerInterface.
 */

namespace Drupal\rest\LinkManager;

/**
 * Defines an interface for a link manager with a configurable domain.
 */
interface ConfigurableLinkManagerInterface {

  /**
   * Sets the link domain used in constructing link URIs.
   *
   * @param string $domain
   *   The link domain to use for constructing link URIs.
   *
   * @return $this
   */
  public function setLinkDomain($domain);

}
