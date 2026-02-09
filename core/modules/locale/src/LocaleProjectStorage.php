<?php

namespace Drupal\locale;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

/**
 * Provides the locale project storage system using a key value store.
 */
class LocaleProjectStorage implements LocaleProjectStorageInterface {

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * Static state cache.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * Memory cache for projects.
   *
   * @var object[]
   */
  protected $projectCache = [];

  /**
   * Cache status flag.
   *
   * @var bool
   */
  protected bool $all = FALSE;

  /**
   * Sorted status flag.
   *
   * @var bool
   */
  protected bool $sorted = FALSE;

  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
  ) {
    $this->keyValueStore = $key_value_factory->get('locale.project');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    $values = $this->getMultiple([$key]);
    return $values[$key] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    $values = [];
    $load = [];
    foreach ($keys as $key) {
      // Check if we have a value in the cache.
      if (isset($this->cache[$key])) {
        $values[$key] = $this->cache[$key];
      }
      // Load the value if we don't have an explicit NULL value.
      elseif (!array_key_exists($key, $this->cache)) {
        $load[] = $key;
      }
    }

    if ($load) {
      $loaded_values = $this->keyValueStore->getMultiple($load);
      foreach ($load as $key) {
        // If we find a value, even one that is NULL, add it to the cache and
        // return it.
        if (isset($loaded_values[$key])) {
          $values[$key] = $loaded_values[$key];
          $this->cache[$key] = $loaded_values[$key];
        }
        else {
          $this->cache[$key] = NULL;
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->setMultiple([$key => $value]);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $data) {
    foreach ($data as $key => $value) {
      $this->cache[$key] = $value;
    }
    $this->keyValueStore->setMultiple($data);
    $this->projectCache = [];
    $this->sorted = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->deleteMultiple([$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    foreach ($keys as $key) {
      $this->cache[$key] = NULL;
    }
    $this->keyValueStore->deleteMultiple($keys);
    $this->projectCache = [];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->cache = [];
    $this->projectCache = [];
    $this->sorted = $this->all = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->keyValueStore->deleteAll();
    $this->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  public function disableAll() {
    $projects = $this->keyValueStore->getAll();
    foreach (array_keys($projects) as $key) {
      $projects[$key]['status'] = 0;
      if (isset($this->cache[$key])) {
        $this->cache[$key] = $projects[$key];
      }
    }
    $this->keyValueStore->setMultiple($projects);

  }

  /**
   * {@inheritdoc}
   */
  public function countProjects() {
    return count($this->getAll());
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    if (!$this->all) {
      $this->cache = $this->keyValueStore->getAll();
      $this->all = TRUE;
    }
    if (!$this->sorted) {
      // Work around PHP 8.3.0 - 8.3.3 bug by assigning $this->cache to a local
      // variable, see https://github.com/php/php-src/pull/13285.
      $cache = $this->cache;
      uksort($this->cache, function ($a, $b) use ($cache) {
        // Sort by weight, if available, and then by key. This allows locale
        // projects to set a weight, if required, and keeps the order consistent
        // regardless of whether the list is built from code or retrieve from
        // the database.
        $sort = (int) ($cache[$a]['weight'] ?? 0) <=> (int) ($cache[$b]['weight'] ?? 0);
        return $sort ?: strcmp($a, $b);
      });
      $this->sorted = TRUE;
    }
    // Remove any NULL values as these are not valid projects.
    return array_filter($this->cache, fn ($value) => $value !== NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects(array $project_names = []): array {
    if (empty($this->projectCache)) {
      // Get project data from the database.
      $row_count = $this->countProjects();
      // https://www.drupal.org/node/1777106 is a follow-up issue to make the
      // check for possible out-of-date project information more robust.
      if ($row_count == 0) {
        \Drupal::moduleHandler()->loadInclude('locale', 'inc', 'locale.compare');
        // At least the core project should be in the database, so we build the
        // data if none are found.
        locale_translation_build_projects();
      }
      $this->projectCache = $this->getAll();
      array_walk($this->projectCache, function (&$project) {
        $project = (object) $project;
      });
    }

    // Return the requested project names or all projects.
    if ($project_names) {
      return array_intersect_key($this->projectCache, array_combine($project_names, $project_names));
    }
    return $this->projectCache;
  }

}
