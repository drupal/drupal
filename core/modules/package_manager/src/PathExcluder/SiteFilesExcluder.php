<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Excludes site files from stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class SiteFilesExcluder implements EventSubscriberInterface {

  public function __construct(
    private readonly StreamWrapperManagerInterface $streamWrapperManager,
    private readonly Filesystem $fileSystem,
    private readonly array $wrappers,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeSiteFiles',
    ];
  }

  /**
   * Excludes public and private files from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeSiteFiles(CollectPathsToExcludeEvent $event): void {
    // Exclude files handled by the stream wrappers listed in $this->wrappers.
    // These paths could be either absolute or relative, depending on site
    // settings. If they are absolute, treat them as relative to the project
    // root. Otherwise, treat them as relative to the web root.
    foreach ($this->wrappers as $scheme) {
      $wrapper = $this->streamWrapperManager->getViaScheme($scheme);
      if ($wrapper instanceof LocalStream) {
        $path = $wrapper->getDirectoryPath();

        if ($this->fileSystem->isAbsolutePath($path)) {
          if ($path = realpath($path)) {
            $event->addPathsRelativeToProjectRoot([$path]);
          }
        }
        else {
          $event->addPathsRelativeToWebRoot([$path]);
        }
      }
    }
  }

}
