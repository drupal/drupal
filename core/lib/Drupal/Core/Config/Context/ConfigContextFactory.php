<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Context\ConfigContextFactory.
 */

namespace Drupal\Core\Config\Context;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Defines configuration context factory.
 *
 * The configuration context factory creates configuration context objects.
 *
 * @see \Drupal\Core\Config\Context\ContextInterface
 */
class ConfigContextFactory {

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs the configuration context.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   */
  public function __construct(EventDispatcher $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Returns a configuration context object.
   *
   * @param string $class
   *   (Optional) The name of the configuration class to use. Defaults to
   *   Drupal\Core\Config\Context\ConfigContext
   *
   * @return \Drupal\Core\Config\Context\ContextInterface $context
   *   (Optional) The configuration context to use.
   */
  public function get($class = NULL) {
    if (!$class) {
      $class = 'Drupal\Core\Config\Context\ConfigContext';
    }
    if (class_exists($class)) {
      $context = new $class($this->eventDispatcher);
    }
    else {
      throw new ConfigException(sprintf('Unknown config context class: %s', $class));
    }
    return $context;
  }

}
