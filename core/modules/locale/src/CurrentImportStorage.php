<?php

namespace Drupal\locale;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Statement\FetchAs;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides the locale current import state service.
 *
 * Handles the current import state for language imports per project and
 * language.
 *
 * @see \Drupal\locale\CurrentImport
 *
 * @internal
 */
class CurrentImportStorage {

  /**
   * The cache tag for current import info.
   */
  protected const string LOCALE_CURRENT_IMPORT_CACHE_TAG = 'locale_current_import';

  public function __construct(
    protected readonly Connection $database,
    #[Autowire(service: 'cache.memory')]
    protected CacheBackendInterface $memoryCache,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Get current import information for a given project and language.
   *
   * @param string $project
   *   Project to get.
   * @param string $langcode
   *   Langcode to get.
   *
   * @return \Drupal\locale\CurrentImport|null
   *   A CurrentImport object if one exists for this project/language.
   */
  public function get(string $project, string $langcode): ?CurrentImport {
    $currentImport = NULL;
    $cid = $this->getCacheId($project, $langcode);
    if ($cached = $this->memoryCache->get($cid)) {
      $currentImport = $cached->data;
    }
    else {
      $result = $this->database->select('locale_file')
        ->fields(
          'locale_file',
          [
            'project',
            'langcode',
            'version',
            'timestamp',
            'hash',
            'last_checked',
          ]
        )
        ->condition('project', $project)
        ->condition('langcode', $langcode)
        ->execute()
        ->fetch(FetchAs::Associative);

      if ($result) {
        $currentImport = CurrentImport::createFromArray($result);
        $this->memoryCache->set($this->getCacheId($currentImport->project, $currentImport->langcode), $currentImport, Cache::PERMANENT, [self::LOCALE_CURRENT_IMPORT_CACHE_TAG]);
      }
    }

    return $currentImport;
  }

  /**
   * Saves the current import information to persistent storage.
   *
   * @param \Drupal\locale\CurrentImport $currentImport
   *   CurrentImport representing the project just imported.
   */
  public function save(CurrentImport $currentImport): void {
    $this->database->upsert('locale_file')
      ->key(['project', 'langcode'])
      ->fields([
        'project' => $currentImport->project,
        'langcode' => $currentImport->langcode,
        'version' => $currentImport->version,
        'timestamp' => $currentImport->timestamp,
        'hash' => $currentImport->hash ?? '',
        'last_checked' => $currentImport->last_checked,
      ])
      ->execute();

    $this->memoryCache->set($this->getCacheId($currentImport->project, $currentImport->langcode), $currentImport, Cache::PERMANENT, [self::LOCALE_CURRENT_IMPORT_CACHE_TAG]);
  }

  /**
   * Deletes the information for the given projects and languages.
   *
   * @param array $projects
   *   Project name(s) to be deleted from the import state storage. If both
   *   project(s) and language code(s) are specified the conditions will be
   *   ANDed.
   * @param array $langcodes
   *   Language code(s) to be deleted from the import state storage.
   */
  public function delete(array $projects, array $langcodes = []): void {
    $query = $this->database->delete('locale_file');
    if (!empty($projects)) {
      $query->condition('project', $projects, 'IN');
    }
    if (!empty($langcodes)) {
      $query->condition('langcode', $langcodes, 'IN');
    }
    $query->execute();

    $this->cacheTagsInvalidator->invalidateTags([self::LOCALE_CURRENT_IMPORT_CACHE_TAG]);
  }

  /**
   * Get projects due for an import check.
   *
   * @param array $projects
   *   Projects to get.
   * @param int $requestTime
   *   The timestamp to check since.
   * @param int $checkTime
   *   The timestamp the check was queued.
   *
   * @return array
   *   Array of projects and their languages.
   */
  public function getOutdatedImports(array $projects, int $requestTime, int $checkTime): ?array {
    $outdatedImports = [];
    $results = $this->database->select('locale_file', 'f')
      ->condition('f.project', $projects, 'IN')
      ->condition('f.last_checked', $checkTime, '<')
      ->fields('f', ['project', 'langcode'])
      ->execute()
      ->fetchAll();

    foreach ($results as $result) {
      $outdatedImports[$result->project][] = $result->langcode;
      $this->updateLastChecked($result->project, $result->langcode, $requestTime);
    }

    return $outdatedImports;
  }

  /**
   * Updates the last checked time for an import.
   *
   * @param string $project
   *   Project to update.
   * @param string $langcode
   *   Langcode to update.
   * @param int $requestTime
   *   The timestamp to check since.
   */
  protected function updateLastChecked(string $project, string $langcode, int $requestTime): void {
    $this->database->update('locale_file')
      ->fields(['last_checked' => $requestTime])
      ->condition('project', $project)
      ->condition('langcode', $langcode)
      ->execute();
    $this->memoryCache->delete($this->getCacheId($project, $langcode));
  }

  /**
   * Get CacheId for a given project and language.
   *
   * @param string $project
   *   Project to get CacheId for.
   * @param string $langcode
   *   Langcode to get CacheId for.
   *
   * @return string
   *   The CacheId.
   */
  protected function getCacheId(string $project, string $langcode) {
    return "locale_current_import:$project:$langcode";
  }

}
