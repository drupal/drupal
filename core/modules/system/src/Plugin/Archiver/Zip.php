<?php

namespace Drupal\system\Plugin\Archiver;

use Drupal\Core\Archiver\Attribute\Archiver;
use Drupal\Core\Archiver\Zip as BaseZip;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an archiver implementation for .zip files.
 *
 * @link http://php.net/zip
 */
#[Archiver(
  id: 'Zip',
  title: new TranslatableMarkup('Zip'),
  description: new TranslatableMarkup('Handles zip files.'),
  extensions: ['zip']
)]
class Zip extends BaseZip {
}
