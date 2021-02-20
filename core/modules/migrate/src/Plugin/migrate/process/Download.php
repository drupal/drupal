<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Downloads a file from a HTTP(S) remote location into the local file system.
 *
 * The source value is an array of two values:
 * - source URL, e.g. 'http://www.example.com/img/foo.img'
 * - destination URI, e.g. 'public://images/foo.img'
 *
 * Available configuration keys:
 * - file_exists: (optional) Replace behavior when the destination file already
 *   exists:
 *   - 'replace' - (default) Replace the existing file.
 *   - 'rename' - Append _{incrementing number} until the filename is
 *     unique.
 *   - 'use existing' - Do nothing and return FALSE.
 * - guzzle_options: (optional)
 *   @link http://docs.guzzlephp.org/en/latest/request-options.html Array of request options for Guzzle. @endlink
 *
 * Examples:
 *
 * @code
 * process:
 *   plugin: download
 *   source:
 *     - source_url
 *     - destination_uri
 * @endcode
 *
 * This will download source_url to destination_uri.
 *
 * @code
 * process:
 *   plugin: download
 *   source:
 *     - source_url
 *     - destination_uri
 *   file_exists: rename
 * @endcode
 *
 * This will download source_url to destination_uri and ensure that the
 * destination URI is unique. If a file with the same name exists at the
 * destination, a numbered suffix like '_0' will be appended to make it unique.
 *
 * @MigrateProcessPlugin(
 *   id = "download"
 * )
 */
class Download extends FileProcessBase implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a download process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system, Client $http_client) {
    $configuration += [
      'guzzle_options' => [],
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a uri of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    list($source, $destination) = $value;

    // Modify the destination filename if necessary.
    $final_destination = $this->fileSystem->getDestinationFilename($destination, $this->configuration['file_exists']);

    // Reuse if file exists.
    if (!$final_destination) {
      return $destination;
    }

    // Try opening the file first, to avoid calling prepareDirectory()
    // unnecessarily. We're suppressing fopen() errors because we want to try
    // to prepare the directory before we give up and fail.
    $destination_stream = @fopen($final_destination, 'w');
    if (!$destination_stream) {
      // If fopen didn't work, make sure there's a writable directory in place.
      $dir = $this->fileSystem->dirname($final_destination);
      if (!$this->fileSystem->prepareDirectory($dir, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        throw new MigrateException("Could not create or write to directory '$dir'");
      }
      // Let's try that fopen again.
      $destination_stream = @fopen($final_destination, 'w');
      if (!$destination_stream) {
        throw new MigrateException("Could not write to file '$final_destination'");
      }
    }

    // Stream the request body directly to the final destination stream.
    $this->configuration['guzzle_options']['sink'] = $destination_stream;

    try {
      // Make the request. Guzzle throws an exception for anything but 200.
      $this->httpClient->get($source, $this->configuration['guzzle_options']);
    }
    catch (\Exception $e) {
      throw new MigrateException("{$e->getMessage()} ($source)");
    }

    return $final_destination;
  }

}
