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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
   * An array of installed projects.
   *
   * @var array
   */
  protected $projects;

  /**
   * The key/value store for the updates.
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

  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected ModuleHandlerInterface $moduleHandler,
    protected UpdateProcessorInterface $updateProcessor,
    TranslationInterface $translation,
    #[Autowire(service: 'keyvalue.expirable')]
    KeyValueFactoryInterface $key_value_expirable_factory,
    protected ThemeHandlerInterface $themeHandler,
    protected ModuleExtensionList $moduleExtensionList,
    protected ThemeExtensionList $themeExtensionList,
    protected ?UpdateCalculator $updateCalculator = NULL,
  ) {
    $this->updateSettings = $config_factory->get('update.settings');
    $this->stringTranslation = $translation;
    $this->keyValueStore = $key_value_expirable_factory->get('update');
    $this->availableReleasesTempStore = $key_value_expirable_factory->get('update_available_releases');
    $this->projects = [];
    if (!$updateCalculator) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $updateCalculator argument is deprecated in drupal:11.5.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3587768', E_USER_DEPRECATED);
      $this->updateCalculator = \Drupal::service(UpdateCalculator::class);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refreshUpdateData() {

    // Since we're fetching new available update data, we want to clear
    // of both the projects we care about, and the current update status of the
    // site. We do *not* want to clear the cache of available releases just yet,
    // since that data (even if it's stale) can be useful during
    // \Drupal\update\UpdateManagerInterface::getProjects(); for example, to
    // modules that implement hook_system_info_alter() such as cvs_deploy.
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
      'system.modules_list',
      'system.theme_install',
      'update.status',
      'update.settings',
      'system.status',
      'update.manual_status',
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

  /**
   * {@inheritdoc}
   */
  public function getAvailable(bool $refresh = FALSE): array {
    \Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.compare');
    $needs_refresh = FALSE;

    // Grab whatever data we currently have.
    $available = $this->availableReleasesTempStore->getAll();
    $projects = $this->getProjects();
    foreach ($projects as $key => $project) {
      // If there's no data at all, we clearly need to fetch some.
      if (empty($available[$key])) {
        $this->updateProcessor->createFetchTask($project);
        $needs_refresh = TRUE;
        continue;
      }

      // See if the .info.yml file is newer than the last time we checked for
      // data, and if so, mark this project's data as needing to be re-fetched.
      // Any time an admin upgrades their local installation, the .info.yml file
      // will be changed, so this is the only way we can be sure we're not
      // showing bogus information right after they upgrade.
      if ($project['info']['_info_file_ctime'] > $available[$key]['last_fetch']) {
        $available[$key]['fetch_status'] = UpdateFetcherInterface::FETCH_PENDING;
      }

      // If we have project data but no release data, we need to fetch. This
      // can be triggered when we fail to contact a release history server.
      if (empty($available[$key]['releases']) && !$available[$key]['last_fetch']) {
        $available[$key]['fetch_status'] = UpdateFetcherInterface::FETCH_PENDING;
      }

      // If we think this project needs to fetch, actually create the task now
      // and remember that we think we're missing some data.
      if (!empty($available[$key]['fetch_status']) && $available[$key]['fetch_status'] == UpdateFetcherInterface::FETCH_PENDING) {
        $this->updateProcessor->createFetchTask($project);
        $needs_refresh = TRUE;
      }
    }

    if ($needs_refresh && $refresh) {
      // Attempt to drain the queue of fetch tasks.
      $this->updateProcessor->fetchData();
      // After processing the queue, we've (hopefully) got better data, so pull
      // the latest data again and use that directly.
      $available = $this->availableReleasesTempStore->getAll();
    }

    return $available;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateProjectData(array $available): array {
    // Retrieve the projects from storage, if present.
    $projects = $this->projectStorage('update_project_data');
    // If $projects is empty, then the data must be rebuilt.
    // Otherwise, return the data and skip the rest of the method.
    if (!empty($projects)) {
      return $projects;
    }
    $projects = $this->getProjects();
    $this->updateCalculator->processProjectInfo($projects);
    if (isset($projects['drupal']) && !empty($available['drupal'])) {
      // Calculate core status first so that it is complete before
      // \Drupal\update\ProjectCoreCompatibility::setReleaseMessage() is called
      // for each module below.
      $checked_project = $this->updateCalculator->updateProjectStatus(UpdateProject::createFromArray($projects['drupal']), UpdateServerProjectInfo::createFromArray($available['drupal']));
      $projects['drupal'] = $checked_project->toArray();
      if (isset($available['drupal']['releases']) && !empty($available['drupal']['supported_branches'])) {
        $supported_branches = explode(',', $available['drupal']['supported_branches']);
        $project_core_compatibility = new ProjectCoreCompatibility($projects['drupal'], $available['drupal']['releases'], $supported_branches);
      }
    }

    foreach ($projects as $project => $project_info) {
      if (isset($available[$project])) {
        if ($project === 'drupal') {
          continue;
        }
        $checked_project = $this->updateCalculator->updateProjectStatus(UpdateProject::createFromArray($projects[$project]), UpdateServerProjectInfo::createFromArray($available[$project]));
        $projects[$project] = $checked_project->toArray();
        // Inject the list of compatible core versions to show administrator(s)
        // which versions of core a given available update can be installed
        // with.  Since individual releases of a project can be compatible with
        // different versions of core, and even multiple major versions of core
        // (for example, 8.9.x and 9.0.x), this list will hopefully help
        // administrator(s) know which available updates they can upgrade a
        // given project to.
        if (isset($project_core_compatibility)) {
          $project_core_compatibility->setReleaseMessage($projects[$project]);
        }
      }
      else {
        $projects[$project]['status'] = UpdateFetcherInterface::UNKNOWN;
        $projects[$project]['reason'] = $this->t('No available releases found');
      }
    }
    // Give other modules a chance to alter the status (for example, to allow a
    // contrib module to provide fine-grained settings to ignore specific
    // projects or releases).
    $this->moduleHandler->alter('update_status', $projects);

    // Store the site's update status for at most 1 hour.
    $this->keyValueStore->setWithExpire('update_project_data', $projects, 3600);
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void {
    $this->keyValueStore->deleteAll();
    $this->availableReleasesTempStore->deleteAll();
  }

}
