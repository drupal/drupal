<?php
/**
 * @file
 * Contains \Drupal\update\UpdateFetcherInterface.
 */

namespace Drupal\update;

/**
 * Fetches project information from remote locations.
 */
interface UpdateFetcherInterface {

  /**
   * Returns the base of the URL to fetch available update data for a project.
   *
   * @param array $project
   *   The array of project information from update_get_projects().
   *
   * @return string
   *   The base of the URL used for fetching available update data. This does
   *   not include the path elements to specify a particular project, version,
   *   site_key, etc.
   */
  public function getFetchBaseUrl($project);

  /**
   * Retrieves the project information.
   *
   * @param array $project
   *   The array of project information from update_get_projects().
   * @param string $site_key
   *   (optional) The anonymous site key hash. Defaults to an empty string.
   *
   * @return string
   *   The project information fetched as string. Empty string upon failure.
   */
  public function fetchProjectData(array $project, $site_key = '');

  /**
   * Generates the URL to fetch information about project updates.
   *
   * This figures out the right URL to use, based on the project's .info.yml
   * file and the global defaults. Appends optional query arguments when the
   * site is configured to report usage stats.
   *
   * @param array $project
   *   The array of project information from update_get_projects().
   * @param string $site_key
   *   (optional) The anonymous site key hash. Defaults to an empty string.
   *
   * @return string
   *   The URL for fetching information about updates to the specified project.
   *
   * @see \Drupal\update\UpdateProcessor::fetchData()
   * @see \Drupal\update\UpdateProcessor::processFetchTask()
   * @see \Drupal\update\UpdateManager::getProjects()
   */
  public function buildFetchUrl(array $project, $site_key = '');
}
