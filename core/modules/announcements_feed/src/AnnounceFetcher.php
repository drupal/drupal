<?php

declare(strict_types=1);

namespace Drupal\announcements_feed;

use Composer\Semver\Semver;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Utility\Error;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to fetch announcements from the external feed.
 *
 * @internal
 */
final class AnnounceFetcher {

  /**
   * The configuration settings of this module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactory
   */
  protected KeyValueStoreInterface $tempStore;

  /**
   * Construct an AnnounceFetcher service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $temp_store
   *   The tempstore factory service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param string $feedUrl
   *   The feed url path.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    ConfigFactoryInterface $config,
    KeyValueExpirableFactoryInterface $temp_store,
    protected LoggerInterface $logger,
    protected string $feedUrl,
  ) {
    $this->config = $config->get('announcements_feed.settings');
    $this->tempStore = $temp_store->get('announcements_feed');
  }

  /**
   * Fetch ids of announcements.
   *
   * @return array
   *   An array with ids of all announcements in the feed.
   */
  public function fetchIds(): array {
    return array_column($this->fetch(), 'id');
  }

  /**
   * Check whether the version given is relevant to the Drupal version used.
   *
   * @param string $version
   *   Version to check.
   *
   * @return bool
   *   Return True if the version matches Drupal version.
   */
  protected static function isRelevantItem(string $version): bool {
    return !empty($version) && Semver::satisfies(\Drupal::VERSION, $version);
  }

  /**
   * Check whether a link is controlled by D.O.
   *
   * @param string $url
   *   URL to check.
   *
   * @return bool
   *   Return True if the URL is controlled by the D.O.
   */
  public static function validateUrl(string $url): bool {
    if (empty($url)) {
      return FALSE;
    }
    $host = parse_url($url, PHP_URL_HOST);

    // First character can only be a letter or a digit.
    // @see https://www.rfc-editor.org/rfc/rfc1123#page-13
    return $host && preg_match('/^([a-zA-Z0-9][a-zA-Z0-9\-_]*\.)?drupal\.org$/', $host);
  }

  /**
   * Fetches the feed either from a local cache or fresh remotely.
   *
   * The feed follows the "JSON Feed" format:
   * - https://www.jsonfeed.org/version/1.1/
   *
   * The structure of an announcement item in the feed is:
   *   - id: Id.
   *   - title: Title of the announcement.
   *   - content_html: Announcement teaser.
   *   - url: URL
   *   - date_modified: Last updated timestamp.
   *   - date_published: Created timestamp.
   *   - _drupalorg.featured: 1 if featured, 0 if not featured.
   *   - _drupalorg.version: Target version of Drupal, as a Composer version.
   *
   * @param bool $force
   *   (optional) Whether to always fetch new items or not. Defaults to FALSE.
   *
   * @return \Drupal\announcements_feed\Announcement[]
   *   An array of announcements from the feed relevant to the Drupal version.
   *   The array is empty if there were no matching announcements. If an error
   *   occurred while fetching/decoding the feed, it is thrown as an exception.
   *
   * @throws \Exception
   */
  public function fetch(bool $force = FALSE): array {
    $announcements = $this->tempStore->get('announcements');
    if ($force || $announcements === NULL) {
      try {
        $feed_content = (string) $this->httpClient->get($this->feedUrl)->getBody();
      }
      catch (\Exception $e) {
        $this->logger->error(Error::DEFAULT_ERROR_MESSAGE, Error::decodeException($e));
        throw $e;
      }

      $announcements = Json::decode($feed_content);
      if (!isset($announcements['items'])) {
        $this->logger->error('The feed format is not valid.');
        throw new \Exception('Invalid format');
      }

      $announcements = $announcements['items'] ?? [];
      // Ensure that announcements reference drupal.org and are applicable to
      // the current Drupal version.
      $announcements = array_filter($announcements, function (array $announcement) {
        return static::validateUrl($announcement['url'] ?? '') && static::isRelevantItem($announcement['_drupalorg']['version'] ?? '');
      });

      // Save the raw decoded and filtered array to temp store.
      $this->tempStore->setWithExpire('announcements', $announcements,
        $this->config->get('max_age'));
    }

    // The drupal.org endpoint is sorted by created date in descending order.
    // We will limit the announcements based on the configuration limit.
    $announcements = array_slice($announcements, 0, $this->config->get('limit') ?? 10);

    // For the remaining announcements, put all the featured announcements
    // before the rest.
    uasort($announcements, function ($a, $b) {
      $a_value = (int) $a['_drupalorg']['featured'];
      $b_value = (int) $b['_drupalorg']['featured'];
      if ($a_value == $b_value) {
        return 0;
      }
      return ($a_value < $b_value) ? -1 : 1;
    });

    // Map the multidimensional array into an array of Announcement objects.
    $announcements = array_map(function ($announcement) {
      return new Announcement(
        $announcement['id'],
        $announcement['title'],
        $announcement['url'],
        $announcement['date_modified'],
        $announcement['date_published'],
        $announcement['content_html'],
        $announcement['_drupalorg']['version'],
        (bool) $announcement['_drupalorg']['featured'],
      );
    }, $announcements);

    return $announcements;
  }

}
