<?php

declare(strict_types=1);

namespace Drupal\locale;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Utility\ProjectInfo;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides storage and rebuilding of locale project information.
 */
readonly class LocaleProjectRepository {

  public function __construct(
    #[Autowire(service: 'cache.memory')]
    protected CacheBackendInterface $memoryCache,
    protected KeyValueFactoryInterface $keyValueFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleExtensionList $moduleExtensionList,
    protected ThemeExtensionList $themeExtensionList,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the stored value for a given key.
   *
   * @param array $keys
   *   The key of the data to retrieve.
   *
   * @return array
   *   An array of the projects
   */
  public function getMultiple(array $keys): array {
    $projects = $this->getAll();
    // Match entity storage getMultiple.
    if (empty($keys)) {
      return [];
    }
    // Return the requested project names.
    return array_intersect_key($projects, array_combine($keys, $keys));
  }

  /**
   * Returns all the project records.
   *
   * @return array
   *   An associative array of items successfully returned, indexed by key.
   */
  public function getAll(): array {
    $cid = 'locale_get_projects';
    if ($cached = $this->memoryCache->get($cid)) {
      $projects = $cached->data;
    }
    else {
      $projects = $this->keyValueFactory->get('locale.project')->getAll();
      if (count($projects) === 0) {
        // At least the core project should be in the database, so we build the
        // data if none are found.
        $projects = $this->buildProjects();
      }
      else {
        $projects = array_map(
          function (array $project) {
            return LocaleTranslatableProject::createFromArray($project);
          },
          $projects,
        );
      }

      uksort($projects, function ($a, $b) use (&$projects) {
        // Sort by weight, if available, and then by key. This allows locale
        // projects to set a weight, if required, and keeps the order consistent
        // regardless of whether the list is built from code or retrieved from
        // the database.
        $sort = (int) $projects[$a]?->getWeight() <=> (int) $projects[$b]?->getWeight();
        return $sort ?: strcmp($a, $b);
      });

      $projects = array_filter($projects, fn($value) => $value !== NULL);
      $this->memoryCache->set($cid, $projects, Cache::PERMANENT);
    }

    return $projects;
  }

  /**
   * Creates or updates the project record.
   *
   * @param LocaleTranslatableProject $project
   *   The key of the data to store.
   */
  public function set(LocaleTranslatableProject $project): void {
    $this->keyValueFactory->get('locale.project')->set($project->name, $project->toArray());
    $this->memoryCache->delete('locale_get_projects');
  }

  /**
   * Deletes multiple project records.
   *
   * @param array $keys
   *   A list of item names to delete.
   */
  public function deleteMultiple(array $keys): void {
    $this->keyValueFactory->get('locale.project')->deleteMultiple($keys);
    $this->memoryCache->delete('locale_get_projects');
  }

  /**
   * Deletes all projects records.
   *
   * @return void
   *   An associative array of items successfully returned, indexed by key.
   */
  public function deleteAll(): void {
    $this->keyValueFactory->get('locale.project')->deleteAll();
    $this->memoryCache->delete('locale_get_projects');
  }

  /**
   * Builds a list of projects and stores the result in the database.
   *
   * Only the properties required by Locale module are included, and
   * additional (custom) modules and translation server data is added.
   *
   * @return array<string, \Drupal\locale\LocaleTranslatableProject>
   *   Array of project instances.
   */
  public function buildProjects(): array {
    // Get the project list based on .info.yml files.
    $projects = $this->getProjectList();
    $existing_projects = $this->keyValueFactory->get('locale.project')->getAll();
    $pattern = $this->configFactory->get('locale.settings')->get('translation.default_server_pattern') ?: \Drupal::TRANSLATION_DEFAULT_SERVER_PATTERN;

    $refreshed_projects = [];
    foreach ($projects as $name => $data) {
      unset($existing_projects[$name]);
      $data['info']['version'] ??= '';

      // For legacy dev releases, remove the '-dev' part and trust the
      // translation server to fall back to the latest stable release for that
      // branch. For semantic releases for core and contrib, the translation
      // server supports redirects for all possible version strings.
      if (str_ends_with($data['info']['version'], '-dev')) {
        if (preg_match("/^(\d+\.x-\d+\.).*$/", $data['info']['version'], $matches)) {
          // Example matches: "8.x-1.x-dev", "8.x-1.0-alpha1+5-dev => 8.x-1.x".
          $data['info']['version'] = $matches[1] . 'x';
        }
      }

      $refreshed_projects[$name] = new LocaleTranslatableProject(
        name: $name,
        type: $data['project_type'],
        core: $data['core'] ?? 'all',
        version: $data['info']['version'],
        // A project can provide the path and filename pattern to download the
        // gettext file. Use the default if not.
        server_pattern: !empty($data['info']['interface translation server pattern']) ? $data['info']['interface translation server pattern'] : $pattern,
        info: $data['info'] ?? [],
        status: TRUE,
      );

      $this->set($refreshed_projects[$name]);
    }

    if (count($existing_projects)) {
      // Mark all remaining projects as disabled and store new project data.
      foreach ($existing_projects as $name => $data) {
        $existing_projects[$name] = LocaleTranslatableProject::createFromArray($data)->setStatus(FALSE);
        $this->set($existing_projects[$name]);
      }
    }

    return $refreshed_projects;
  }

  /**
   * Fetch an array of projects for translation update.
   *
   * @return array
   *   Array of project data including .info.yml file data.
   */
  protected function getProjectList(): array {
    $projects = [];

    $additional_allow_list = [
      'interface translation project',
      'interface translation server pattern',
    ];
    $module_data = $this->prepareProjectList($this->moduleExtensionList->getList());
    $theme_data = $this->prepareProjectList($this->themeExtensionList->getList());
    $project_info = new ProjectInfo();
    $project_info->processInfoList($projects, $module_data, 'module', TRUE, $additional_allow_list);
    $project_info->processInfoList($projects, $theme_data, 'theme', TRUE, $additional_allow_list);

    // Allow other modules to alter projects before fetching and comparing.
    $this->moduleHandler->alter('locale_translation_projects', $projects);

    return $projects;
  }

  /**
   * Prepare module and theme data.
   *
   * Modify .info.yml file data before it is processed by
   * \Drupal\Core\Utility\ProjectInfo->processInfoList(). In order for
   * \Drupal\Core\Utility\ProjectInfo->processInfoList() to recognize a project,
   * it requires the 'project' parameter in the .info.yml file data.
   *
   * Custom modules or themes can bring their own gettext translation file. To
   * enable import of this file, the module or theme defines "interface
   * translation project = my_project" in its .info.yml file. This method will
   * add a project "my_project" to the info data.
   *
   * @param \Drupal\Core\Extension\Extension[] $data
   *   Array of .info.yml file data.
   *
   * @return array
   *   Array of .info.yml file data.
   */
  protected function prepareProjectList(array $data): array {
    foreach ($data as $name => $file) {
      // Include interface translation projects. To allow
      // \Drupal\Core\Utility\ProjectInfo->processInfoList() to identify this
      // as a project, the 'project' property is filled with the
      // 'interface translation project' value.
      if (isset($file->info['interface translation project'])) {
        $data[$name]->info['project'] = $file->info['interface translation project'];
      }
    }
    return $data;
  }

}
