<?php

namespace Drupal\Core\Config;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Config\Config;

class ConfigEvent extends Event {
  /**
   * Configuration object.
   *
   * @var Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructor.
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
