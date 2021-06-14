<?php

namespace Drupal\language\Config;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines language configuration overrides.
 */
class LanguageConfigOverride extends StorableConfigBase {

  use LanguageConfigCollectionNameTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
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
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
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
  public function save($has_trusted_data = FALSE) {
    if (!$has_trusted_data) {
      // @todo Use configuration schema to validate.
      //   https://www.drupal.org/node/2270399
      // Perform basic data validation.
      foreach ($this->data as $key => $value) {
        $this->validateValue($key, $value);
      }
    }

    $this->storage->write($this->name, $this->data);
    // Invalidate the cache tags not only when updating, but also when creating,
    // because a language config override object uses the same cache tag as the
    // default configuration object. Hence creating a language override is like
    // an update of configuration, but only for a specific language.
    Cache::invalidateTags($this->getCacheTags());
    $this->isNew = FALSE;
    $this->eventDispatcher->dispatch(new LanguageConfigOverrideCrudEvent($this), LanguageConfigOverrideEvents::SAVE_OVERRIDE);
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->data = [];
    $this->storage->delete($this->name);
    Cache::invalidateTags($this->getCacheTags());
    $this->isNew = TRUE;
    $this->eventDispatcher->dispatch(new LanguageConfigOverrideCrudEvent($this), LanguageConfigOverrideEvents::DELETE_OVERRIDE);
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
