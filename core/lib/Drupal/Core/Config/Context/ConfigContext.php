<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Context\ConfigContext.
 */

namespace Drupal\Core\Config\Context;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvent;
use Drupal\Component\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Defines the base configuration context object.
 *
 * A configuration context object provides a data array that can be used:
 *   - as a parameter to get customized configuration objects.
 *   - as a store of config data used to override values.
 */
class ConfigContext implements ContextInterface {

  /**
   * Predefined key, values to override specific configuration objects.
   */
  const OVERRIDE = 'config.override';

  /**
   * The actual storage of key-value pairs.
   *
   * @var array
   */
  protected $data = array();

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * A unique identifier for the context.
   *
   * @var string
   */
  protected $uuid;

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
   * Implements Drupal\Core\Config\Context\ContextInterface::init().
   */
  public function init($context_key, $data) {
    if ($data) {
      $this->set($context_key, $data);
    }
    $this->setUuid();
    // Notify event listeners that a configuration context has been created.
    $this->notify('context', NULL);
    return $this;
  }

  /**
   * Implements Drupal\Core\Config\Context\ContextInterface::get().
   */
  public function get($key) {
    return array_key_exists($key, $this->data) ? $this->data[$key] : NULL;
  }

  /**
   * Implements Drupal\Core\Config\Context\ContextInterface::set().
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * Sets override data.
   *
   * @param mixed $data
   *   Override data to store.
   *
   * @return \Drupal\Core\Config\Context\ConfigContext
   *   The config context object.
   */
  public function setOverride($data) {
    $this->init(self::OVERRIDE, $data);
    return $this;
  }

  /**
   * Implements Drupal\Core\Config\Context\ContextInterface::setUuid().
   */
  public function setUuid() {
    $uuid = new Uuid();
    $this->uuid = $uuid->generate();
  }

  /**
   * Implements Drupal\Core\Config\Context\ContextInterface::getUuid().
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Implements Drupal\Core\Config\Context\ContextInterface::notify().
   */
  public function notify($config_event_name, Config $config = NULL) {
    $this->eventDispatcher->dispatch('config.' . $config_event_name, new ConfigEvent($this, $config));
  }

}
