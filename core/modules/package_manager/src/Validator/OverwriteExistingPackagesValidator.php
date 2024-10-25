<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\PathLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates that newly installed packages don't overwrite existing directories.
 *
 * Whether a new package in the stage directory would overwrite an existing
 * directory in the active directory when the operation is applied is determined
 * by inspecting the `path` property of the staged package.
 *
 * Certain packages, such as those with the `metapackage` type, don't have a
 * `path` property and are ignored by this validator. The Composer facade at
 * https://packages.drupal.org/8 currently uses the `metapackage` type for
 * submodules of Drupal projects.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 *
 * @see https://getcomposer.org/doc/04-schema.md#type
 */
final class OverwriteExistingPackagesValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly ComposerInspector $composerInspector,
  ) {}

  /**
   * Validates that new installed packages don't overwrite existing directories.
   *
   * @param \Drupal\package_manager\Event\PreApplyEvent $event
   *   The event being handled.
   */
  public function validate(PreApplyEvent $event): void {
    $active_dir = $this->pathLocator->getProjectRoot();
    $stage_dir = $event->stage->getStageDirectory();
    $active_packages = $this->composerInspector->getInstalledPackagesList($active_dir);
    $new_packages = $this->composerInspector->getInstalledPackagesList($stage_dir)
      ->getPackagesNotIn($active_packages);

    foreach ($new_packages as $package) {
      if (empty($package->path)) {
        // Packages without a `path` cannot overwrite existing directories.
        continue;
      }
      $relative_path = str_replace($stage_dir, '', $package->path);
      if (is_dir($active_dir . DIRECTORY_SEPARATOR . $relative_path)) {
        $event->addError([
          $this->t('The new package @package will be installed in the directory @path, which already exists but is not managed by Composer.', [
            '@package' => $package->name,
            '@path' => $relative_path,
          ]),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreApplyEvent::class => 'validate',
    ];
  }

}
