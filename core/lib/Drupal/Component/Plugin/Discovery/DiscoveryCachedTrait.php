<?php

namespace Drupal\Component\Plugin\Discovery;

trait DiscoveryCachedTrait {

  use DiscoveryTrait;

  /**
   * Cached definitions array.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    // Fetch definitions if they're not loaded yet.
    if (!isset($this->definitions)) {
      $this->getDefinitions();
    }

    return $this->doGetDefinition($this->definitions, $plugin_id, $exception_on_invalid);
  }

}
