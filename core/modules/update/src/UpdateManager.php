<?php

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\ProjectInfo;

/**
 * Default implementation of UpdateManagerInterface.
 */
class UpdateManager implements UpdateManagerInterface {
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The update settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $updateSettings;

  /**
   * Module Handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Update Processor Service.
   *
   * @var \Drupal\update\UpdateProcessorInterface
   */
  protected $updateProcessor;

  /**
   * An array of installed projects.
   *
   * @var array
   */
  protected $projects;

  /**
   * The key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueStore;

  /**
   * Update available releases key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $availableReleasesTempStore;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * Constructs an UpdateManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module Handler service
   * @param \Drupal\update\UpdateProcessorInterface $update_processor
   *   The Update Processor service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_expirable_factory
   *   The expirable key/value factory.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $extension_list_theme
   *   The theme extension list.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, UpdateProcessorInterface $update_processor, TranslationInterface $translation, KeyValueFactoryInterface $key_value_expirable_factory, ThemeHandlerInterface $theme_handler, ModuleExtensionList $extension_list_module, ThemeExtensionList $extension_list_theme) {
    $this->updateSettings = $config_factory->get('update.settings');
    $this->moduleHandler = $module_handler;
    $this->updateProcessor = $update_processor;
    $this->stringTranslation = $translation;
    $this->keyValueStore = $key_value_expirable_factory->get('update');
    $this->themeHandler = $theme_handler;
    $this->availableReleasesTempStore = $key_value_expirable_factory->get('update_available_releases');
    $this->projects = [];
    $this->moduleExtensionList = $extension_list_module;
    $this->themeExtensionList = $extension_list_theme;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshUpdateData() {

    // Since we're fetching new available update data, we want to clear
    // of both the projects we care about, and the current update status of the
    // site. We do *not* want to clear the cache of available releases just yet,
    // since that data (even if it's stale) can be useful during
    // \Drupal\update\UpdateManager::getProjects(); for example, to modules
    // that implement hook_system_info_alter() such as cvs_deploy.
    $this->keyValueStore->delete('update_project_projects');
    $this->keyValueStore->delete('update_project_data');

    $projects = $this->getProjects();

    // Now that we have the list of projects, we should also clear the available
    // release data, since even if we fail to fetch new data, we need to clear
    // out the stale data at this point.
    $this->availableReleasesTempStore->deleteAll();

    foreach ($projects as $project) {
      $this->updateProcessor->createFetchTask($project);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects() {
    if (empty($this->projects)) {
      // Retrieve the projects from storage, if present.
      $this->projects = $this->projectStorage('update_project_projects');
      if (empty($this->projects)) {
        // Still empty, so we have to rebuild.
        $module_data = $this->moduleExtensionList->reset()->getList();
        $theme_data = $this->themeExtensionList->reset()->getList();
        $project_info = new ProjectInfo();
        $project_info->processInfoList($this->projects, $module_data, 'module', TRUE);
        $project_info->processInfoList($this->projects, $theme_data, 'theme', TRUE);
        if ($this->updateSettings->get('check.disabled_extensions')) {
          $project_info->processInfoList($this->projects, $module_data, 'module', FALSE);
          $project_info->processInfoList($this->projects, $theme_data, 'theme', FALSE);
        }
        // Allow other modules to alter projects before fetching and comparing.
        $this->moduleHandler->alter('update_projects', $this->projects);
        // Store the site's project data for at most 1 hour.
        $this->keyValueStore->setWithExpire('update_project_projects', $this->projects, 3600);
      }
    }
    return $this->projects;
  }

  /**
   * {@inheritdoc}
   */
  public function projectStorage($key) {
    $projects = [];

    // On certain paths, we should clear the data and recompute the projects for
    // update status of the site to avoid presenting stale information.
    $route_names = [
      'update.theme_update',
      'system.modules_list',
      'system.theme_install',
      'update.module_update',
      'update.module_install',
      'update.status',
      'update.report_update',
      'update.report_install',
      'update.settings',
      'system.status',
      'update.manual_status',
      'update.confirmation_page',
      'system.themes_page',
    ];
    if (in_array(\Drupal::routeMatch()->getRouteName(), $route_names)) {
      $this->keyValueStore->delete($key);
    }
    else {
      $projects = $this->keyValueStore->get($key, []);
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDataBatch(&$context) {
    if (empty($context['sandbox']['max'])) {
      $context['finished'] = 0;
      $context['sandbox']['max'] = $this->updateProcessor->numberOfQueueItems();
      $context['sandbox']['progress'] = 0;
      $context['message'] = $this->t('Checking available update data ...');
      $context['results']['updated'] = 0;
      $context['results']['failures'] = 0;
      $context['results']['processed'] = 0;
    }

    // Grab another item from the fetch queue.
    for ($i = 0; $i < 5; $i++) {
      if ($item = $this->updateProcessor->claimQueueItem()) {
        if ($this->updateProcessor->processFetchTask($item->data)) {
          $context['results']['updated']++;
          $context['message'] = $this->t('Checked available update data for %title.', ['%title' => $item->data['info']['name']]);
        }
        else {
          $context['message'] = $this->t('Failed to check available update data for %title.', ['%title' => $item->data['info']['name']]);
          $context['results']['failures']++;
        }
        $context['sandbox']['progress']++;
        $context['results']['processed']++;
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        $this->updateProcessor->deleteQueueItem($item);
      }
      else {
        // If the queue is currently empty, we're done. It's possible that
        // another thread might have added new fetch tasks while we were
        // processing this batch. In that case, the usual 'finished' math could
        // get confused, since we'd end up processing more tasks that we thought
        // we had when we started and initialized 'max' with numberOfItems(). By
        // forcing 'finished' to be exactly 1 here, we ensure that batch
        // processing is terminated.
        $context['finished'] = 1;
        return;
      }
    }
  }

}
