<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Archiver\Zip.
 */

namespace Drupal\system\Plugin\Archiver;

use Drupal\Component\Archiver\Zip as BaseZip;

/**
 * Defines an archiver implementation for .zip files.
 *
 * @link http://php.net/zip
 *
 * @Archiver(
 *   id = "Zip",
 *   title = @Translation("Zip"),
 *   description = @Translation("Handles zip files."),
 *   extensions = {"zip"}
 * )
 */
class Zip extends BaseZip {
}
