<?php

namespace Drupal\update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Utility\Error;
use GuzzleHttp\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
   * The fetch URL configured in the update settings.
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

  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected ClientInterface $httpClient,
    #[Autowire(service: 'logger.channel.update')]
    protected LoggerInterface $logger,
  ) {
    $this->fetchUrl = $config_factory->get('update.settings')->get('fetch.url');
    $this->updateSettings = $config_factory->get('update.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProjectData(array $project, $site_key = '') {
    $url = $this->buildFetchUrl($project, $site_key);
    return $this->doRequest($url, ['headers' => ['Accept' => 'text/xml']]);
  }

  /**
   * Applies a GET request.
   *
   * @param string $url
   *   The URL.
   * @param array $options
   *   The guzzle client options.
   *
   * @return string
   *   The body of the request, or an empty string on failure.
   */
  protected function doRequest(string $url, array $options): string {
    $data = '';
    try {
      $data = (string) $this->httpClient
        ->get($url, ['headers' => ['Accept' => 'text/xml']])
        ->getBody();
    }
    catch (ClientExceptionInterface $exception) {
      Error::logException($this->logger, $exception);
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
    // installed. We do not want to record usage statistics for uninstalled
    // projects.
    if (!empty($site_key) && !str_contains($project['project_type'], 'disabled')) {
      // Append the site key.
      $url .= str_contains($url, '?') ? '&' : '?';
      $url .= 'site_key=';
      $url .= rawurlencode($site_key);

      // Append the version.
      if (!empty($project['info']['version'])) {
        $url .= '&version=';
        $url .= rawurlencode($project['info']['version']);
      }

      // Append the list of modules or themes installed.
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
