<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Core\Archiver\Zip.
 */

namespace Drupal\system\Plugin\Archiver;

use Drupal\Component\Archiver\Zip as BaseZip;

/**
 * Defines a archiver implementation for .zip files.
 *
 * @link http://php.net/zip
 *
 * @Plugin(
 *   id = "Zip",
 *   title = @Translation("Zip"),
 *   description = @Translation("Handles zip files."),
 *   extensions = {"zip"}
 * )
 */
class Zip extends BaseZip {
}
