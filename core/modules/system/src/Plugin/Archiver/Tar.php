<?php

namespace Drupal\system\Plugin\Archiver;

use Drupal\Core\Archiver\Attribute\Archiver;
use Drupal\Core\Archiver\Tar as BaseTar;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an archiver implementation for .tar files.
 */
#[Archiver(
  id: 'Tar',
  title: new TranslatableMarkup('Tar'),
  description: new TranslatableMarkup('Handles .tar files.'),
  extensions: ['tar', 'tgz', 'tar.gz', 'tar.bz2']
)]
class Tar extends BaseTar {
}
