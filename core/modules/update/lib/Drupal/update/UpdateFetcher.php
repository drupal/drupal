<?php

/**
 * @file
 * Contains \Drupal\update\UpdateFetcher.
 */

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactory;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;

/**
 * Fetches project information from remote locations.
 */
class UpdateFetcher {

  /**
   * URL to check for updates, if a given project doesn't define its own.
   */
  const UPDATE_DEFAULT_URL = 'http://updates.drupal.org/release-history';

  /**
   * The fetch url configured in the update settings.
   *
   * @var string
   */
  protected $fetchUrl;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a UpdateFetcher.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Guzzle\Http\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactory $config_factory, ClientInterface $http_client) {
    $this->fetchUrl = $config_factory->get('update.settings')->get('fetch.url');
    $this->httpClient = $http_client;
  }

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
  public function fetchProjectData(array $project, $site_key = '') {
    $url = $this->buildFetchUrl($project, $site_key);
    $data = '';
    try {
      $data = $this->httpClient
        ->get($url, array('Accept' => 'text/xml'))
        ->send()
        ->getBody(TRUE);
    }
    catch (RequestException $exception) {
      watchdog_exception('update', $exception);
    }
    return $data;
  }

  /**
   * Generates the URL to fetch information about project updates.
   *
   * This figures out the right URL to use, based on the project's .info.yml file
   * and the global defaults. Appends optional query arguments when the site is
   * configured to report usage stats.
   *
   * @param array $project
   *   The array of project information from update_get_projects().
   * @param string $site_key
   *   (optional) The anonymous site key hash. Defaults to an empty string.
   *
   * @return string
   *   The URL for fetching information about updates to the specified project.
   *
   * @see update_fetch_data()
   * @see _update_process_fetch_task()
   * @see update_get_projects()
   */
  public function buildFetchUrl(array $project, $site_key = '') {
    $name = $project['name'];
    $url = $this->getFetchBaseUrl($project);
    $url .= '/' . $name . '/' . \Drupal::CORE_COMPATIBILITY;

    // Only append usage information if we have a site key and the project is
    // enabled. We do not want to record usage statistics for disabled projects.
    if (!empty($site_key) && (strpos($project['project_type'], 'disabled') === FALSE)) {
      // Append the site key.
      $url .= (strpos($url, '?') !== FALSE) ? '&' : '?';
      $url .= 'site_key=';
      $url .= rawurlencode($site_key);

      // Append the version.
      if (!empty($project['info']['version'])) {
        $url .= '&version=';
        $url .= rawurlencode($project['info']['version']);
      }

      // Append the list of modules or themes enabled.
      $list = array_keys($project['includes']);
      $url .= '&list=';
      $url .= rawurlencode(implode(',', $list));
    }
    return $url;
  }

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
   *
   * @see \Drupal\update\UpdateFetcher::getFetchBaseUrl()
   */
  public function getFetchBaseUrl($project) {
    if (isset($project['info']['project status url'])) {
      $url = $project['info']['project status url'];
    }
    else {
      $url = $this->fetchUrl;
      if (empty($url)) {
        $url = static::UPDATE_DEFAULT_URL;
      }
    }
    return $url;
  }

}
