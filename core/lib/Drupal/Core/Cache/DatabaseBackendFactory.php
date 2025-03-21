<?php

namespace Drupal\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\ObjectAwareSerializationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Site\Settings;

/**
 * Defines a default cache backend factory.
 */
class DatabaseBackendFactory implements CacheFactoryInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs the DatabaseBackendFactory object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum provider.
   * @param \Drupal\Core\Site\Settings $settings
   *   (optional) The site settings.
   * @param \Drupal\Component\Serialization\ObjectAwareSerializationInterface|null $serializer
   *   (optional) The serializer to use.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   *
   * @throws \BadMethodCallException
   */
  public function __construct(
    Connection $connection,
    CacheTagsChecksumInterface $checksum_provider,
    protected Settings $settings,
    protected ObjectAwareSerializationInterface $serializer,
    protected TimeInterface $time,
  ) {
    $this->connection = $connection;
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * Gets DatabaseBackend for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\Core\Cache\DatabaseBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    $max_rows = $this->getMaxRowsForBin($bin);
    return new DatabaseBackend($this->connection, $this->checksumProvider, $bin, $this->serializer, $this->time, $max_rows);
  }

  /**
   * Gets the max rows for the specified cache bin.
   *
   * @param string $bin
   *   The cache bin for which the object is created.
   *
   * @return int
   *   The maximum number of rows for the given bin. Defaults to
   *   DatabaseBackend::DEFAULT_MAX_ROWS.
   */
  protected function getMaxRowsForBin($bin) {
    $max_rows_settings = $this->settings->get('database_cache_max_rows');
    // First, look for a cache bin specific setting.
    if (isset($max_rows_settings['bins'][$bin])) {
      $max_rows = $max_rows_settings['bins'][$bin];
    }
    // Second, use configured default backend.
    elseif (isset($max_rows_settings['default'])) {
      $max_rows = $max_rows_settings['default'];
    }
    else {
      // Fall back to the default max rows if nothing else is configured.
      $max_rows = DatabaseBackend::DEFAULT_MAX_ROWS;
    }
    return $max_rows;
  }

}
