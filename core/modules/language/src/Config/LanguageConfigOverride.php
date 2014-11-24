<?php

/**
 * @file
 * Contains \Drupal\language\Config\LanguageConfigOverride.
 */

namespace Drupal\language\Config;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines language configuration overrides.
 */
class LanguageConfigOverride extends StorableConfigBase {

  use LanguageConfigCollectionNameTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a language override object.
   *
   * @param string $name
   *   The name of the configuration object being overridden.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A storage controller object to use for reading and writing the
   *   configuration override.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct($name, StorageInterface $storage, TypedConfigManagerInterface $typed_config, EventDispatcherInterface $event_dispatcher) {
    $this->name = $name;
    $this->storage = $storage;
    $this->typedConfigManager = $typed_config;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // @todo Use configuration schema to validate.
    //   https://drupal.org/node/2270399
    // Perform basic data validation.
    foreach ($this->data as $key => $value) {
      $this->validateValue($key, $value);
    }
    $this->storage->write($this->name, $this->data);
    $this->isNew = FALSE;
    $this->eventDispatcher->dispatch(LanguageConfigOverrideEvents::SAVE_OVERRIDE, new LanguageConfigOverrideCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->data = array();
    $this->storage->delete($this->name);
    $this->isNew = TRUE;
    $this->eventDispatcher->dispatch(LanguageConfigOverrideEvents::DELETE_OVERRIDE, new LanguageConfigOverrideCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Returns the language code of this language override.
   *
   * @return string
   *   The language code.
   */
  public function getLangcode() {
    return $this->getLangcodeFromCollectionName($this->getStorage()->getCollectionName());
  }

}
