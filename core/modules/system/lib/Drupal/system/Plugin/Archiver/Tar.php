<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Archiver\Tar.
 */

namespace Drupal\system\Plugin\Archiver;

use Drupal\Component\Archiver\Tar as BaseTar;

/**
 * Defines an archiver implementation for .tar files.
 *
 * @Archiver(
 *   id = "Tar",
 *   title = @Translation("Tar"),
 *   description = @Translation("Handles .tar files."),
 *   extensions = {"tar", "tgz", "tar.gz", "tar.bz2"}
 * )
 */
class Tar extends BaseTar {
}
