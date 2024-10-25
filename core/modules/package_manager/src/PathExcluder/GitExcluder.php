<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Excludes .git directories from stage operations.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class GitExcluder implements EventSubscriberInterface {

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly ComposerInspector $composerInspector,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeGitDirectories',
    ];
  }

  /**
   * Excludes .git directories from stage operations.
   *
   * Any .git directories that are a part of an installed package -- for
   * example, a module that Composer installed from source -- are included.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   *
   * @throws \Exception
   *   See \Drupal\package_manager\ComposerInspector::validate().
   */
  public function excludeGitDirectories(CollectPathsToExcludeEvent $event): void {
    $project_root = $this->pathLocator->getProjectRoot();

    // To determine which .git directories to exclude, the installed packages
    // must be known, and that requires Composer commands to be able to run.
    // This intentionally does not catch exceptions: failed Composer validation
    // in the project root implies that this excluder cannot function correctly.
    // Note: the call to ComposerInspector::getInstalledPackagesList() would
    // also have triggered this, but explicitness is preferred here.
    // @see \Drupal\package_manager\StatusCheckTrait::runStatusCheck()
    $this->composerInspector->validate($project_root);

    $paths_to_exclude = [];

    $installed_paths = [];
    // Collect the paths of every installed package.
    $installed_packages = $this->composerInspector->getInstalledPackagesList($project_root);
    foreach ($installed_packages as $package) {
      if (!empty($package->path)) {
        $installed_paths[] = $package->path;
      }
    }
    $paths = $event->scanForDirectoriesByName('.git');
    foreach ($paths as $git_directory) {
      // Don't exclude any `.git` directory that is directly under an installed
      // package's path, since it means Composer probably installed that package
      // from source and therefore needs the `.git` directory in order to update
      // the package.
      if (!in_array(dirname($git_directory), $installed_paths, TRUE)) {
        $paths_to_exclude[] = $git_directory;
      }
    }
    $event->addPathsRelativeToProjectRoot($paths_to_exclude);
  }

}
