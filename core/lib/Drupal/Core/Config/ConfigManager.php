<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigManager.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\Yaml\Dumper;

/**
 * The ConfigManager provides helper functions for the configuration system.
 */
class ConfigManager implements ConfigManagerInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManager
   */
  protected $typedConfigManager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * Creates ConfigManager objects.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManager $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation service.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, TypedConfigManager $typed_config_manager, TranslationManager $string_translation) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeIdByName($name) {
    $entities = array_filter($this->entityManager->getDefinitions(), function (EntityTypeInterface $entity_type) use ($name) {
      return ($config_prefix = $entity_type->getConfigPrefix()) && strpos($name, $config_prefix . '.') === 0;
    });
    return key($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function diff(StorageInterface $source_storage, StorageInterface $target_storage, $name) {
    // @todo Replace with code that can be autoloaded.
    //   https://drupal.org/node/1848266
    require_once __DIR__ . '/../../Component/Diff/DiffEngine.php';

    // The output should show configuration object differences formatted as YAML.
    // But the configuration is not necessarily stored in files. Therefore, they
    // need to be read and parsed, and lastly, dumped into YAML strings.
    $dumper = new Dumper();
    $dumper->setIndentation(2);

    $source_data = explode("\n", $dumper->dump($source_storage->read($name), PHP_INT_MAX));
    $target_data = explode("\n", $dumper->dump($target_storage->read($name), PHP_INT_MAX));

    // Check for new or removed files.
    if ($source_data === array('false')) {
      // Added file.
      $source_data = array($this->stringTranslation->translate('File added'));
    }
    if ($target_data === array('false')) {
      // Deleted file.
      $target_data = array($this->stringTranslation->translate('File removed'));
    }

    return new \Diff($source_data, $target_data);
  }

  /**
   * {@inheritdoc}
   */
  public function createSnapshot(StorageInterface $source_storage, StorageInterface $snapshot_storage) {
    $snapshot_storage->deleteAll();
    foreach ($source_storage->listAll() as $name) {
      $snapshot_storage->write($name, $source_storage->read($name));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall($type, $name) {
    $config_names = $this->configFactory->listAll($name . '.');
    foreach ($config_names as $config_name) {
      $this->configFactory->get($config_name)->delete();
    }
    $schema_dir = drupal_get_path($type, $name) . '/config/schema';
    if (is_dir($schema_dir)) {
      // Refresh the schema cache if uninstalling an extension that provides
      // configuration schema.
      $this->typedConfigManager->clearCachedDefinitions();
    }
  }

}
