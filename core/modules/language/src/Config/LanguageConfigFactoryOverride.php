<?php

/**
 * @file
 * Contains \Drupal\language\Config\LanguageConfigFactoryOverride.
 */

namespace Drupal\language\Config;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides language overrides for the configuration factory.
 */
class LanguageConfigFactoryOverride implements LanguageConfigFactoryOverrideInterface, EventSubscriberInterface {

  /**
   * The configuration storage.
   *
   * Do not access this directly. Should be accessed through self::getStorage()
   * so that the cache of storages per langcode is used.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $baseStorage;

  /**
   * An array of configuration storages keyed by langcode.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $storages;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The language object used to override configuration data.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * Constructs the LanguageConfigFactoryOverride object.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config) {
    $this->baseStorage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if ($this->language) {
      $storage = $this->getStorage($this->language->getId());
      return $storage->readMultiple($names);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverride($langcode, $name) {
    $storage = $this->getStorage($langcode);
    $data = $storage->read($name);
    $override = new LanguageConfigOverride($name, $storage, $this->typedConfigManager);
    if (!empty($data)) {
      $override->initWithData($data);
    }
    return $override;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($langcode) {
    if (!isset($this->storages[$langcode])) {
      $this->storages[$langcode] = $this->baseStorage->createCollection($this->createConfigCollectionName($langcode));
    }
    return $this->storages[$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return $this->language ? $this->language->id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage(LanguageInterface $language = NULL) {
    $this->language = $language;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguageFromDefault(LanguageDefault $language_default = NULL) {
    $this->language = $language_default ? $language_default->get() : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function installLanguageOverrides($langcode) {
    /** @var \Drupal\Core\Config\ConfigInstallerInterface $config_installer */
    $config_installer = \Drupal::service('config.installer');
    $config_installer->installCollectionDefaultConfig($this->createConfigCollectionName($langcode));
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $langcode = $this->getLangcodeFromCollectionName($collection);
    return $this->getOverride($langcode, $name);
  }

  /**
   * Creates a configuration collection name based on a langcode.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return string
   *   The configuration collection name for a langcode.
   */
  protected function createConfigCollectionName($langcode) {
    return 'language.' . $langcode;
  }

  /**
   * Converts a configuration collection name to a langcode.
   *
   * @param string $collection
   *   The configuration collection name.
   *
   * @return string
   *   The langcode of the collection.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown if the provided collection name is not in the format
   *   "language.LANGCODE".
   *
   * @see self::createConfigCollectionName()
   */
  protected function getLangcodeFromCollectionName($collection) {
    preg_match('/^language\.(.*)$/', $collection, $matches);
    if (!isset($matches[1])) {
      throw new \InvalidArgumentException(String::format('!collection is not a valid language override collection', array('!collection' => $collection)));
    }
    return $matches[1];
  }

  /**
   * Reacts to the ConfigEvents::COLLECTION_INFO event.
   *
   * @param \Drupal\Core\Config\ConfigCollectionInfo $collection_info
   *   The configuration collection names event.
   */
  public function addCollections(ConfigCollectionInfo $collection_info) {
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      $collection_info->addCollection($this->createConfigCollectionName($language->getId()), $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::COLLECTION_INFO][] = array('addCollections');
    return $events;
  }

}
