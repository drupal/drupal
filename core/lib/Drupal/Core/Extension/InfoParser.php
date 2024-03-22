<?php

namespace Drupal\Core\Extension;

use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileCache\FileCacheInterface;

/**
 * Parses extension .info.yml files.
 */
class InfoParser extends InfoParserDynamic {

  /**
   * The file cache.
   *
   * @var \Drupal\Component\FileCache\FileCacheInterface
   */
  protected FileCacheInterface $fileCache;

  /**
   * InfoParser constructor.
   *
   * @param string $app_root
   *   The root directory of the Drupal installation.
   */
  public function __construct(string $app_root) {
    parent::__construct($app_root);
    if (FileCacheFactory::getPrefix() !== NULL) {
      $this->fileCache = FileCacheFactory::get('info_parser');
    }
    else {
      // Just use a static file cache when there is no prefix. This code path is
      // triggered when info is parsed prior to \Drupal\Core\DrupalKernel::boot()
      // running. This occurs during the very early installer and in some test
      // scenarios.
      $this->fileCache = new FileCache('info_parser', 'info_parser');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    $data = $this->fileCache->get($filename);
    if ($data === NULL) {
      $data = parent::parse($filename);
      $this->fileCache->set($filename, $data);
    }
    return $data;
  }

}
