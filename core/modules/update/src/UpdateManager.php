<?php
/**
 * @file
 * Contains \Drupal\update\UpdateManager.
 */

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\ProjectInfo;

/**
 * Default implementation of UpdateManagerInterface.
 */
class UpdateManager implements UpdateManagerInterface {
  use StringTranslationTrait;

  /**
   * The update settings
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
   * An array of installed and enabled projects.
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
   * Constructs a UpdateManager.
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
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, UpdateProcessorInterface $update_processor, TranslationInterface $translation, KeyValueFactoryInterface $key_value_expirable_factory) {
    $this->updateSettings = $config_factory->get('update.settings');
    $this->moduleHandler = $module_handler;
    $this->updateProcessor = $update_processor;
    $this->stringTranslation = $translation;
    $this->keyValueStore = $key_value_expirable_factory->get('update');
    $this->availableReleasesTempStore = $key_value_expirable_factory->get('update_available_releases');
    $this->projects = array();
  }

  /**
   * {@inheritdoc}
   */
  public function refreshUpdateData() {

    // Since we're fetching new available update data, we want to clear
    // of both the projects we care about, and the current update status of the
    // site. We do *not* want to clear the cache of available releases just yet,
    // since that data (even if it's stale) can be useful during
    // update_get_projects(); for example, to modules that implement
    // hook_system_info_alter() such as cvs_deploy.
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
        $module_data = system_rebuild_module_data();
        $theme_data = system_rebuild_theme_data();
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
    $projects = array();

    // On certain paths, we should clear the data and recompute the projects for
    // update status of the site to avoid presenting stale information.
    $paths = array(
      'admin/modules',
      'admin/modules/update',
      'admin/appearance',
      'admin/appearance/update',
      'admin/reports',
      'admin/reports/updates',
      'admin/reports/updates/update',
      'admin/reports/status',
      'admin/reports/updates/check',
    );
    if (in_array(current_path(), $paths)) {
      $this->keyValueStore->delete($key);
    }
    else {
      $projects = $this->keyValueStore->get($key, array());
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
          $context['message'] = $this->t('Checked available update data for %title.', array('%title' => $item->data['info']['name']));
        }
        else {
          $context['message'] = $this->t('Failed to check available update data for %title.', array('%title' => $item->data['info']['name']));
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
