<?php

namespace Drupal\Core\Config;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Factory class to create config importer objects.
 *
 * This class is declared as final because the ConfigImporter class is not
 * intended to be swappable.
 */
final class ConfigImporterFactory {

  /**
   * Creates a ConfigImporterFactory instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend to prevent multiple imports occurring at the same time.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   * @param \Drupal\Core\StringTranslation\TranslationManager $stringTranslation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   */
  public function __construct(
    protected EventDispatcherInterface $eventDispatcher,
    protected ConfigManagerInterface $configManager,
    #[Autowire(service: 'lock.persistent')]
    protected LockBackendInterface $lock,
    protected TypedConfigManagerInterface $typedConfigManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleInstallerInterface $moduleInstaller,
    protected ThemeHandlerInterface $themeHandler,
    protected TranslationInterface $stringTranslation,
    protected ModuleExtensionList $moduleExtensionList,
    protected ThemeExtensionList $themeExtensionList,
  ) {}

  /**
   * Creates a ConfigImporter instance.
   *
   * @param \Drupal\Core\Config\StorageComparer $storage_comparer
   *   The storage comparer object. The type is the class and not
   *   StorageComparerInterface because that is due to be removed: see
   *   https://www.drupal.org/project/drupal/issues/3410037.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   A config importer instance.
   */
  public function get(StorageComparer $storage_comparer): ConfigImporter {
    return new ConfigImporter(
      $storage_comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->typedConfigManager,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation,
      $this->moduleExtensionList,
      $this->themeExtensionList,
    );
  }

}
