<?php

declare(strict_types=1);

namespace Drupal\package_manager\Event;

use Drupal\package_manager\SandboxManagerBase;
use Drupal\package_manager\PathLocator;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathListFactoryInterface;
use PhpTuf\ComposerStager\API\Path\Value\PathListInterface;

/**
 * Defines an event that collects paths to exclude.
 *
 * These paths are excluded by Composer Stager and are never copied into the
 * stage directory from the active directory, or vice versa.
 */
final class CollectPathsToExcludeEvent extends SandboxEvent implements PathListInterface {

  /**
   * Constructs a CollectPathsToExcludeEvent object.
   *
   * @param \Drupal\package_manager\SandboxManagerBase $sandboxManager
   *   The stage which fired this event.
   * @param \Drupal\package_manager\PathLocator $pathLocator
   *   The path locator service.
   * @param \PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface $pathFactory
   *   The path factory service.
   * @param \PhpTuf\ComposerStager\API\Path\Value\PathListInterface|null $pathList
   *   (optional) The list of paths to exclude.
   */
  public function __construct(
    SandboxManagerBase $sandboxManager,
    private readonly PathLocator $pathLocator,
    private readonly PathFactoryInterface $pathFactory,
    private ?PathListInterface $pathList = NULL,
  ) {
    parent::__construct($sandboxManager);

    $this->pathList ??= \Drupal::service(PathListFactoryInterface::class)
      ->create();
  }

  /**
   * {@inheritdoc}
   */
  public function add(string ...$paths): void {
    $this->pathList->add(...$paths);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll(): array {
    return array_unique($this->pathList->getAll());
  }

  /**
   * Flags paths to be ignored, relative to the web root.
   *
   * This should only be used for paths that, if they exist at all, are
   * *guaranteed* to exist within the web root.
   *
   * @param string[] $paths
   *   The paths to ignore. These should be relative to the web root. They will
   *   be made relative to the project root.
   */
  public function addPathsRelativeToWebRoot(array $paths): void {
    $web_root = $this->pathLocator->getWebRoot();
    if ($web_root) {
      $web_root .= '/';
    }

    foreach ($paths as $path) {
      // Make the path relative to the project root by prefixing the web root.
      $this->add($web_root . $path);
    }
  }

  /**
   * Flags paths to be ignored, relative to the project root.
   *
   * @param string[] $paths
   *   The paths to ignore. Absolute paths will be made relative to the project
   *   root; relative paths are assumed to be relative to the project root.
   *
   * @throws \LogicException
   *   If any of the given paths are absolute, but not inside the project root.
   */
  public function addPathsRelativeToProjectRoot(array $paths): void {
    $project_root = $this->pathLocator->getProjectRoot();

    foreach ($paths as $path) {
      if ($this->pathFactory->create($path)->isAbsolute()) {
        if (!str_starts_with($path, $project_root)) {
          throw new \LogicException("$path is not inside the project root: $project_root.");
        }
      }

      // Make absolute paths relative to the project root.
      $path = str_replace($project_root, '', $path);
      $path = ltrim($path, '/');
      $this->add($path);
    }
  }

  /**
   * Finds all directories in the project root matching the given name.
   *
   * @param string $directory_name
   *   A directory name.
   *
   * @return string[]
   *   All discovered absolute paths matching the given directory name.
   */
  public function scanForDirectoriesByName(string $directory_name): array {
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directories_tree = new \RecursiveDirectoryIterator($this->pathLocator->getProjectRoot(), $flags);
    $filtered_directories = new \RecursiveIteratorIterator($directories_tree, \RecursiveIteratorIterator::SELF_FIRST);
    $matched_directories = new \CallbackFilterIterator($filtered_directories,
      fn (\RecursiveDirectoryIterator $current) => $current->isDir() && $current->getFilename() === $directory_name
    );
    return array_keys(iterator_to_array($matched_directories));
  }

}
