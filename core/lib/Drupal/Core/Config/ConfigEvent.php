<?php

namespace Drupal\Core\Config;

use Symfony\Component\EventDispatcher\Event;

class ConfigEvent extends Event {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a configuration event object.
   *
   * @param \Drupal\Core\Config\Config
   *   Configuration object.
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * Get configuration object.
   */
  public function getConfig() {
    return $this->config;
  }
}

