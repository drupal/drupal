<?php

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;

/**
 * Fetches project information from remote locations.
 */
class UpdateFetcher implements UpdateFetcherInterface {

  use DependencySerializationTrait;

  /**
   * URL to check for updates, if a given project doesn't define its own.
   */
  const UPDATE_DEFAULT_URL = 'https://updates.drupal.org/release-history';

  /**
   * The fetch url configured in the update settings.
   *
   * @var string
   */
  protected $fetchUrl;

  /**
   * The update settings.
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
   * Whether to use HTTP fallback if HTTPS fails.
   *
   * @var bool
   */
  protected $withHttpFallback;

  /**
   * Constructs an UpdateFetcher.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\Site\Settings|null $settings
   *   The settings instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, Settings $settings = NULL) {
    $this->fetchUrl = $config_factory->get('update.settings')->get('fetch.url');
    $this->httpClient = $http_client;
    $this->updateSettings = $config_factory->get('update.settings');
    if (is_null($settings)) {
      @trigger_error('The settings service should be passed to UpdateFetcher::__construct() since 9.1.0. This will be required in Drupal 10.0.0. See https://www.drupal.org/node/3179315', E_USER_DEPRECATED);
      $settings = \Drupal::service('settings');
    }
    $this->withHttpFallback = $settings->get('update_fetch_with_http_fallback', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProjectData(array $project, $site_key = '') {
    $url = $this->buildFetchUrl($project, $site_key);
    return $this->doRequest($url, ['headers' => ['Accept' => 'text/xml']], $this->withHttpFallback);
  }

  /**
   * Applies a GET request with a possible HTTP fallback.
   *
   * This method falls back to HTTP in case there was some certificate
   * problem.
   *
   * @param string $url
   *   The URL.
   * @param array $options
   *   The guzzle client options.
   * @param bool $with_http_fallback
   *   Should the function fall back to HTTP.
   *
   * @return string
   *   The body of the HTTP(S) request, or an empty string on failure.
   */
  protected function doRequest(string $url, array $options, bool $with_http_fallback): string {
    $data = '';
    try {
      $data = (string) $this->httpClient
        ->get($url, ['headers' => ['Accept' => 'text/xml']])
        ->getBody();
    }
    catch (TransferException $exception) {
      watchdog_exception('update', $exception);
      if ($with_http_fallback && strpos($url, "http://") === FALSE) {
        $url = str_replace('https://', 'http://', $url);
        return $this->doRequest($url, $options, FALSE);
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFetchUrl(array $project, $site_key = '') {
    $name = $project['name'];
    $url = $this->getFetchBaseUrl($project);
    $url .= '/' . $name . '/current';

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
