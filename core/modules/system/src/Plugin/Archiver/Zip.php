<?php

namespace Drupal\system\Plugin\Archiver;

use Drupal\Core\Archiver\Zip as BaseZip;

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
