<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\PathLocator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Path;

/**
 * Excludes unknown paths from stage operations.
 *
 * Any path in the root directory of the project that is NOT one of the
 * following are considered unknown paths:
 * 1. The vendor directory
 * 2. The web root
 * 3. composer.json
 * 4. composer.lock
 * 5. Scaffold files as determined by the drupal/core-composer-scaffold plugin
 *
 * If the web root and the project root are the same, nothing is excluded.
 *
 * This excluder can be disabled by changing the config setting
 * `package_manager.settings:include_unknown_files_in_project_root` to TRUE.
 * This may be needed for sites that have files outside the web root (besides
 * the vendor directory) which are nonetheless needed in order for Composer to
 * assemble the code base correctly; a classic example would be a directory of
 * patch files used by `cweagans/composer-patches`.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class UnknownPathExcluder implements EventSubscriberInterface, LoggerAwareInterface {

  use LoggerAwareTrait;
  use StringTranslationTrait;

  public function __construct(
    private readonly ComposerInspector $composerInspector,
    private readonly PathLocator $pathLocator,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeUnknownPaths',
      StatusCheckEvent::class => 'logExcludedPaths',
    ];
  }

  /**
   * Returns the paths to exclude from stage operations.
   *
   * @return string[]
   *   The paths that should be excluded from stage operations, relative to the
   *   project root.
   *
   * @throws \Exception
   *   See \Drupal\package_manager\ComposerInspector::validate().
   */
  private function getExcludedPaths(): array {
    // If this excluder is disabled, or the project root and web root are the
    // same, we are not excluding any paths.
    $is_disabled = $this->configFactory->get('package_manager.settings')
      ->get('include_unknown_files_in_project_root');
    $web_root = $this->pathLocator->getWebRoot();
    if ($is_disabled || empty($web_root)) {
      return [];
    }

    // To determine the files to include, the installed packages must be known,
    // and that requires Composer commands to be able to run. This intentionally
    // does not catch exceptions: failed Composer validation in the project root
    // implies that this excluder cannot function correctly. In such a case, the
    // call to ComposerInspector::getConfig() would also have triggered an
    // exception, but explicitness is preferred here.
    // @see \Drupal\package_manager\StatusCheckTrait::runStatusCheck()
    $project_root = $this->pathLocator->getProjectRoot();
    $this->composerInspector->validate($project_root);

    // The vendor directory and web root are always included in staging
    // operations, along with `composer.json`, `composer.lock`, and any scaffold
    // files provided by Drupal core.
    $always_include = [
      $this->composerInspector->getConfig('vendor-dir', $project_root),
      $web_root,
      'composer.json',
      'composer.lock',
    ];
    foreach ($this->getScaffoldFiles() as $scaffold_file_path) {
      // The web root is always included in staging operations, so we don't need
      // to do anything special for scaffold files that live in it.
      if (str_starts_with($scaffold_file_path, '[web-root]')) {
        continue;
      }
      $always_include[] = ltrim($scaffold_file_path, '/');
    }

    // Find any path repositories located inside the project root. These need
    // to be included or Composer will break in the staging area.
    $repositories = $this->composerInspector->getConfig('repositories', $project_root);
    $repositories = Json::decode($repositories);
    foreach ($repositories as $repository) {
      if ($repository['type'] !== 'path') {
        continue;
      }
      try {
        // Ensure $path is relative to the project root, even if it's written as
        // an absolute path in `composer.json`.
        $path = Path::makeRelative($repository['url'], $project_root);
        // Strip off everything except the top-level directory name. For
        // example, if the repository path is `custom/module/foo`, always
        // include `custom`.
        $always_include[] = dirname($path, substr_count($path, '/') ?: 1);
      }
      catch (InvalidArgumentException) {
        // The repository path is not relative to the project root, so we don't
        // need to worry about it.
      }
    }

    // Search for all files (including hidden ones) in the project root. We need
    // to use readdir() and friends here, rather than glob(), since certain
    // glob() flags aren't supported on all systems. We also can't use
    // \Drupal\Core\File\FileSystemInterface::scanDirectory(), because it
    // unconditionally ignores hidden files and directories.
    $files_in_project_root = [];
    $handle = opendir($project_root);
    if (empty($handle)) {
      throw new \RuntimeException("Could not scan for files in the project root.");
    }
    while ($entry = readdir($handle)) {
      $files_in_project_root[] = $entry;
    }
    closedir($handle);

    return array_diff($files_in_project_root, $always_include, ['.', '..']);
  }

  /**
   * Excludes unknown paths from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeUnknownPaths(CollectPathsToExcludeEvent $event): void {
    // We can exclude the paths as-is; they are already relative to the project
    // root.
    $event->add(...$this->getExcludedPaths());
  }

  /**
   * Logs the paths that will be excluded from stage operations.
   */
  public function logExcludedPaths(): void {
    $excluded_paths = $this->getExcludedPaths();
    if ($excluded_paths) {
      sort($excluded_paths);

      $message = $this->t("The following paths in @project_root aren't recognized as part of your Drupal site, so to be safe, Package Manager is excluding them from all stage operations. If these files are not needed for Composer to work properly in your site, no action is needed. Otherwise, you can disable this behavior by setting the <code>package_manager.settings:include_unknown_files_in_project_root</code> config setting to <code>TRUE</code>.\n\n@list", [
        '@project_root' => $this->pathLocator->getProjectRoot(),
        '@list' => implode("\n", $excluded_paths),
      ]);
      $this->logger?->info($message);
    }
  }

  /**
   * Gets the path of scaffold files, for example 'index.php' and 'robots.txt'.
   *
   * @return string[]
   *   The paths of scaffold files provided by `drupal/core`, relative to the
   *   project root.
   *
   * @todo Intelligently load scaffold files in https://drupal.org/i/3343802.
   */
  private function getScaffoldFiles(): array {
    $project_root = $this->pathLocator->getProjectRoot();
    $packages = $this->composerInspector->getInstalledPackagesList($project_root);
    $extra = Json::decode($this->composerInspector->getConfig('extra', $packages['drupal/core']->path . '/composer.json'));

    $scaffold_files = $extra['drupal-scaffold']['file-mapping'] ?? [];
    return str_replace('[project-root]', '', array_keys($scaffold_files));
  }

}
