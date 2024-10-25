<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\Core\Database\Connection;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Excludes SQLite database files from stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class SqliteDatabaseExcluder implements EventSubscriberInterface {

  public function __construct(
    private readonly PathFactoryInterface $pathFactory,
    private readonly Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeDatabaseFiles',
    ];
  }

  /**
   * Excludes SQLite database files from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeDatabaseFiles(CollectPathsToExcludeEvent $event): void {
    // If the database is SQLite, it might be located in the project directory,
    // and should be excluded.
    if ($this->database->driver() === 'sqlite') {
      // @todo Support database connections other than the default in
      //   https://www.drupal.org/i/3441919.
      $db_path = $this->database->getConnectionOptions()['database'];
      // Exclude the database file and auxiliary files created by SQLite.
      $paths = [$db_path, "$db_path-shm", "$db_path-wal"];

      // If the database path is absolute, it might be outside the project root,
      // in which case we don't need to do anything.
      if ($this->pathFactory->create($db_path)->isAbsolute()) {
        try {
          $event->addPathsRelativeToProjectRoot($paths);
        }
        catch (\LogicException) {
          // The database is outside the project root, so we're done.
        }
      }
      else {
        // The database is in the web root, and must be excluded relative to it.
        $event->addPathsRelativeToWebRoot($paths);
      }
    }
  }

}
