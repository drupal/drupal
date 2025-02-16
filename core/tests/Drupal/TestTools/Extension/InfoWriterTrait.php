<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension;

use Drupal\Core\Serialization\Yaml;

/**
 * Writes the info file and ensures the mtime changes.
 *
 * @see \Drupal\Component\FileCache\FileCache
 * @see \Drupal\Core\Extension\InfoParser
 */
trait InfoWriterTrait {

  /**
   * Writes the info file and ensures the mtime changes.
   *
   * @param string $file_path
   *   The info file path.
   * @param array $info
   *   The info array.
   *
   * @return void
   *   No return value.
   */
  private function writeInfoFile(string $file_path, array $info): void {
    $mtime = file_exists($file_path) ? filemtime($file_path) : FALSE;

    file_put_contents($file_path, Yaml::encode($info));

    // Ensure mtime changes.
    if ($mtime === filemtime($file_path)) {
      touch($file_path, max($mtime + 1, time()));
    }
  }

}
