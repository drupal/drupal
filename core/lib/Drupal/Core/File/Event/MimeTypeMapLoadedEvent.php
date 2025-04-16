<?php

declare(strict_types=1);

namespace Drupal\Core\File\Event;

use Drupal\Core\File\MimeType\MimeTypeMapInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when the MIME type map is loaded.
 */
final class MimeTypeMapLoadedEvent extends Event {

  public function __construct(
    public readonly MimeTypeMapInterface $map,
  ) {}

}
