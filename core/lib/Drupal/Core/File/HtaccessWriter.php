<?php

namespace Drupal\Core\File;

use Drupal\Component\PhpStorage\FileStorage;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Provides functions to manage Apache .htaccess files.
 */
class HtaccessWriter implements HtaccessWriterInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Htaccess constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(FileSystemInterface $file_system, LoggerInterface $logger) {
    $this->fileSystem = $file_system;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function ensure() {
    try {
      foreach ($this->getHtaccessFiles() as $htaccessFile => $info) {
        $this->save($info['directory'], $info['private']);
      }

      // If a staging directory exists then it should contain a .htaccess file.
      // @todo https://www.drupal.org/node/2696103 catch a more specific
      //   exception and simplify this code.
      try {
        $staging = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
      }
      catch (\Exception $e) {
        $staging = FALSE;
      }
      if ($staging) {
        // Note that we log an error here if we can't write the .htaccess file.
        // This can occur if the staging directory is read-only. If it is then
        // it is the user's responsibility to create the .htaccess file.
        $this->save($staging, TRUE);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save($directory, $private = TRUE, $forceOverwrite = FALSE) {
    if ($this->fileSystem->uriScheme($directory)) {
      $htaccessPath = $this->normalizeUri($directory . '/.htaccess');
    }
    else {
      $directory = rtrim($directory, '/\\');
      $htaccessPath = $directory . '/.htaccess';
    }

    if (file_exists($htaccessPath) && !$forceOverwrite) {
      // Short circuit if the .htaccess file already exists.
      return TRUE;
    }
    $htaccessLines = FileStorage::htaccessLines($private);

    // Write the .htaccess file.
    if (file_exists($directory) && is_writable($directory) && file_put_contents($htaccessPath, $htaccessLines)) {
      return $this->fileSystem->chmod($htaccessPath, 0444);
    }
    else {
      $this->logger->error("Security warning: Couldn't write .htaccess file. Please create a .htaccess file in your %directory directory which contains the following lines: <pre><code>@htaccess</code></pre>", [
        '%directory' => $directory,
        '@htaccess' => $htaccessLines,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHtaccessFiles() {
    $htaccessFiles = [];
    $htaccessFiles['public://.htaccess'] = [
      'title' => new TranslatableMarkup('Public files directory'),
      'directory' => $this->fileSystem->realpath('public://'),
      'private' => FALSE,
    ];
    if (PrivateStream::basePath()) {
      $htaccessFiles['private://.htaccess'] = [
        'title' => new TranslatableMarkup('Private files directory'),
        'directory' => $this->fileSystem->realpath('private://'),
        'private' => TRUE,
      ];
    }
    $htaccessFiles['temporary://.htaccess'] = [
      'title' => new TranslatableMarkup('Temporary files directory'),
      'directory' => $this->fileSystem->realpath('temporary://'),
      'private' => TRUE,
    ];
    return $htaccessFiles;
  }

  /**
   * Wraps file_stream_wrapper_uri_normalize().
   *
   * @param string $uri
   *   A URI to normalize.
   *
   * @return string
   *   The normalized URI.
   *
   * @see file_stream_wrapper_uri_normalize()
   */
  private function normalizeUri($uri) {
    return file_stream_wrapper_uri_normalize($uri);
  }

}
