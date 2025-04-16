<?php

declare(strict_types=1);

namespace Drupal\Core\File\MimeType;

use Drupal\Core\File\Event\MimeTypeMapLoadedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Factory for creating the MIME type map.
 */
class MimeTypeMapFactory {

  public function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Creates an instance of the MIME type map.
   *
   * @return \Drupal\Core\File\MimeType\MimeTypeMapInterface
   *   The MIME type map.
   */
  public function create(): MimeTypeMapInterface {
    $map = $this->doCreateMap();
    $this->eventDispatcher->dispatch(new MimeTypeMapLoadedEvent($map));
    return $map;
  }

  protected function doCreateMap(): MimeTypeMapInterface {
    return new MimeTypeMap();
  }

}
