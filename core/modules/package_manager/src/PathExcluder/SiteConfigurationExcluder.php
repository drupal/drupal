<?php

declare(strict_types=1);

namespace Drupal\package_manager\PathExcluder;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Excludes site configuration files from stage directories.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
class SiteConfigurationExcluder implements EventSubscriberInterface {

  public function __construct(
    protected string $sitePath,
    private readonly PathLocator $pathLocator,
    private readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * Excludes site configuration files from stage operations.
   *
   * @param \Drupal\package_manager\Event\CollectPathsToExcludeEvent $event
   *   The event object.
   */
  public function excludeSiteConfiguration(CollectPathsToExcludeEvent $event): void {
    // These two files are never relevant to existing sites.
    $paths = [
      'sites/default/default.settings.php',
      'sites/default/default.services.yml',
    ];

    // Exclude site-specific settings files, which are always in the web root.
    // By default, Drupal core will always try to write-protect these files.
    // @see system_requirements()
    $settings_files = [
      'settings.php',
      'settings.local.php',
      'services.yml',
    ];
    foreach ($settings_files as $settings_file) {
      $paths[] = $this->sitePath . '/' . $settings_file;
      $paths[] = 'sites/default/' . $settings_file;
    }
    // Site configuration files are always excluded relative to the web root.
    $event->addPathsRelativeToWebRoot($paths);
  }

  /**
   * Makes the staged `sites/default` directory owner-writable.
   *
   * This allows the core scaffold plugin to make changes in `sites/default`,
   * if needed. Otherwise, it would break if `sites/default` is not writable.
   * This can happen because rsync preserves directory permissions (and Drupal
   * tries to write-protect the site directory).
   *
   * We specifically exclude `default.settings.php` and `default.services.yml`
   * from Package Manager operations. This allows the scaffold plugin to change
   * those files in the stage directory.
   *
   * @param \Drupal\package_manager\Event\PostCreateEvent $event
   *   The event being handled.
   *
   * @see ::excludeSiteConfiguration()
   */
  public function makeDefaultSiteDirectoryWritable(PostCreateEvent $event): void {
    $dir = $this->getDefaultSiteDirectoryPath($event->sandboxManager->getSandboxDirectory());
    // If the directory doesn't even exist, there's nothing to do here.
    if (!is_dir($dir)) {
      return;
    }
    if (!$this->fileSystem->chmod($dir, 0700)) {
      throw new FileException("Could not change permissions on '$dir'.");
    }
  }

  /**
   * Makes `sites/default` permissions the same in live and stage directories.
   *
   * @param \Drupal\package_manager\Event\PreApplyEvent $event
   *   The event being handled.
   *
   * @throws \Drupal\Core\File\Exception\FileException
   *   If the permissions of the live `sites/default` cannot be determined, or
   *   cannot be changed on the staged `sites/default`.
   */
  public function syncDefaultSiteDirectoryPermissions(PreApplyEvent $event): void {
    $staged_dir = $this->getDefaultSiteDirectoryPath($event->sandboxManager->getSandboxDirectory());
    // If the directory doesn't even exist, there's nothing to do here.
    if (!is_dir($staged_dir)) {
      return;
    }
    $live_dir = $this->getDefaultSiteDirectoryPath($this->pathLocator->getProjectRoot());

    $permissions = fileperms($live_dir);
    if ($permissions === FALSE) {
      throw new FileException("Could not determine permissions for '$live_dir'.");
    }

    if (!$this->fileSystem->chmod($staged_dir, $permissions)) {
      throw new FileException("Could not change permissions on '$staged_dir'.");
    }
  }

  /**
   * Returns the full path to `sites/default`, relative to a root directory.
   *
   * @param string $root_dir
   *   The root directory.
   *
   * @return string
   *   The full path to `sites/default` within the given root directory.
   */
  private function getDefaultSiteDirectoryPath(string $root_dir): string {
    $dir = [$root_dir];
    $web_root = $this->pathLocator->getWebRoot();
    if ($web_root) {
      $dir[] = $web_root;
    }
    return implode(DIRECTORY_SEPARATOR, [...$dir, 'sites', 'default']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CollectPathsToExcludeEvent::class => 'excludeSiteConfiguration',
      PostCreateEvent::class => 'makeDefaultSiteDirectoryWritable',
      PreApplyEvent::class => 'syncDefaultSiteDirectoryPermissions',
    ];
  }

}
