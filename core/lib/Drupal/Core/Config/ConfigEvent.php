<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\Context\ContextInterface;
use Symfony\Component\EventDispatcher\Event;

class ConfigEvent extends Event {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Configuration context object.
   *
   * @var \Drupal\Core\Config\Context\ContextInterface
   */
  protected $context;

  /**
   * Constructs a configuration event object.
   *
   * @param \Drupal\Core\Config\Context\ContextInterface
   *   Configuration context object.
   * @param \Drupal\Core\Config\Config
   *   (optional) Configuration object.
   */
  public function __construct(ContextInterface $context, Config $config = NULL) {
    $this->config = $config;
    $this->context = $context;
  }

  /**
   * Get configuration object.
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Gets configuration context object.
   *
   * @return \Drupal\Core\Config\Context\ContextInterface
   *   Configuration context.
   */
  public function getContext() {
    return $this->context;
  }
}
