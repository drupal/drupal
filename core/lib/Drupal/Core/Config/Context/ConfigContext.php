<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Context\ConfigContext.
 */

namespace Drupal\Core\Config\Context;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvent;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Defines the base configuration context object.
 *
 * A configuration context object provides a data array that can be used as
 * parameters to get customized configuration objects.
 */
class ConfigContext implements ContextInterface {

  /**
   * The actual storage of key-value pairs.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Any config overrides of key-value pairs.
   *
   * @var array
   */
  protected $overrides = array();

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
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs the configuration context.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Component\Uuid\UuidInterface
   *   The UUID service.
   */
  public function __construct(EventDispatcher $event_dispatcher, UuidInterface $uuid) {
    $this->eventDispatcher = $event_dispatcher;
    $this->uuidService = $uuid;
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::init().
   */
  public function init() {
    // Reset existing overrides and get a UUID for this context.
    $this->overrides = array();
    $this->setUuid();
    // Notify event listeners that a configuration context has been created.
    $this->notify('context', NULL);
    return $this;
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::get().
   */
  public function get($key) {
    return array_key_exists($key, $this->data) ? $this->data[$key] : NULL;
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::set().
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::setUuid().
   */
  public function setUuid() {
    $this->uuid = $this->uuidService->generate();
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::getUuid().
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::notify().
   */
  public function notify($config_event_name, Config $config = NULL) {
    $this->eventDispatcher->dispatch('config.' . $config_event_name, new ConfigEvent($this, $config));
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::setOverride().
   */
  public function setOverrides($config_name, $data) {
    if (!isset($this->overrides[$config_name])) {
      $this->overrides[$config_name] = $data;
    }
    else {
      $this->overrides[$config_name] = NestedArray::mergeDeepArray(array($this->overrides[$config_name], $data), TRUE);
    }
  }

  /**
   * Implements \Drupal\Core\Config\Context\ContextInterface::getOverrides().
   */
  public function getOverrides($config_name) {
    if (isset($this->overrides[$config_name])) {
      return $this->overrides[$config_name];
    }
    return FALSE;
  }

}
