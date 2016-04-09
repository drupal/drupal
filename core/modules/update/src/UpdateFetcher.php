<?php

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Fetches project information from remote locations.
 */
class UpdateFetcher implements UpdateFetcherInterface {

  use DependencySerializationTrait;

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
   * The update settings
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $updateSettings;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a UpdateFetcher.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->fetchUrl = $config_factory->get('update.settings')->get('fetch.url');
    $this->httpClient = $http_client;
    $this->updateSettings = $config_factory->get('update.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProjectData(array $project, $site_key = '') {
    $url = $this->buildFetchUrl($project, $site_key);
    $data = '';
    try {
      $data = (string) $this->httpClient
        ->get($url, array('headers' => array('Accept' => 'text/xml')))
        ->getBody();
    }
    catch (RequestException $exception) {
      watchdog_exception('update', $exception);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
