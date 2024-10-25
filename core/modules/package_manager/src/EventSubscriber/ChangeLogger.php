<?php

declare(strict_types=1);

namespace Drupal\package_manager\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\PathLocator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to log changes that happen during the stage life cycle.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class ChangeLogger implements EventSubscriberInterface, LoggerAwareInterface {

  use LoggerAwareTrait;
  use StringTranslationTrait;

  /**
   * The key to store the list of packages installed when the stage is created.
   *
   * @var string
   *
   * @see ::recordInstalledPackages()
   */
  private const INSTALLED_PACKAGES_KEY = 'package_manager_installed_packages';

  /**
   * The metadata key under which to store the requested package versions.
   *
   * @var string
   *
   * @see ::recordRequestedPackageVersions()
   */
  private const REQUESTED_PACKAGES_KEY = 'package_manager_requested_packages';

  public function __construct(
    private readonly ComposerInspector $composerInspector,
    private readonly PathLocator $pathLocator,
  ) {}

  /**
   * Records packages installed in the project root.
   *
   * We need to do this before the staging environment has been created, so that
   * we have a complete picture of which requested packages are merely being
   * updated, and which are being newly added. Once the staging environment has
   * been created, the installed packages won't change -- if they do, a
   * validation error will be raised.
   *
   * @param \Drupal\package_manager\Event\PreCreateEvent $event
   *   The event being handled.
   *
   * @see \Drupal\package_manager\Validator\LockFileValidator
   */
  public function recordInstalledPackages(PreCreateEvent $event): void {
    $packages = $this->composerInspector->getInstalledPackagesList($this->pathLocator->getProjectRoot());
    $event->stage->setMetadata(static::INSTALLED_PACKAGES_KEY, $packages);
  }

  /**
   * Records requested packages.
   *
   * @param \Drupal\package_manager\Event\PostRequireEvent $event
   *   The event object.
   */
  public function recordRequestedPackageVersions(PostRequireEvent $event): void {
    // There could be multiple 'require' operations, so overlay the requested
    // packages from the current operation onto the requested packages from any
    // previous 'require' operation.
    $requested_packages = array_merge(
      $event->stage->getMetadata(static::REQUESTED_PACKAGES_KEY) ?? [],
      $event->getRuntimePackages(),
      $event->getDevPackages(),
    );
    $event->stage->setMetadata(static::REQUESTED_PACKAGES_KEY, $requested_packages);
  }

  /**
   * Logs changes made by Package Manager.
   *
   * @param \Drupal\package_manager\Event\PostApplyEvent $event
   *   The event being handled.
   */
  public function logChanges(PostApplyEvent $event): void {
    $installed_at_start = $event->stage->getMetadata(static::INSTALLED_PACKAGES_KEY);
    $installed_post_apply = $this->composerInspector->getInstalledPackagesList($this->pathLocator->getProjectRoot());

    // Compare the packages which were installed when the stage was created
    // against the package versions that were requested over all the stage's
    // require operations, and create a log entry listing all of it.
    $requested_log = [];

    $requested_packages = $event->stage->getMetadata(static::REQUESTED_PACKAGES_KEY) ?? [];
    // Sort the requested packages by name, to make it easier to review a large
    // change list.
    ksort($requested_packages, SORT_NATURAL);
    foreach ($requested_packages as $name => $constraint) {
      $installed_version = $installed_at_start[$name]?->version;
      if ($installed_version === NULL) {
        // For clarity, make the "any version" constraint human-readable.
        if ($constraint === '*') {
          $constraint = $this->t('* (any version)');
        }
        $requested_log[] = $this->t('- Install @name @constraint', [
          '@name' => $name,
          '@constraint' => $constraint,
        ]);
      }
      else {
        $requested_log[] = $this->t('- Update @name from @installed_version to @constraint', [
          '@name' => $name,
          '@installed_version' => $installed_version,
          '@constraint' => $constraint,
        ]);
      }
    }
    // It's possible that $requested_log will be empty: for example, a custom
    // stage that only does removals, or some other operation, and never
    // dispatches PostRequireEvent.
    if ($requested_log) {
      $message = $this->t("Requested changes:\n@change_list", [
        '@change_list' => implode("\n", array_map('strval', $requested_log)),
      ]);
      $this->logger?->info($message);
    }

    // Create a separate log entry listing everything that actually changed.
    $applied_log = [];

    $updated_packages = $installed_post_apply->getPackagesWithDifferentVersionsIn($installed_at_start);
    // Sort the packages by name to make it easier to review large change sets.
    $updated_packages->ksort(SORT_NATURAL);
    foreach ($updated_packages as $name => $package) {
      $applied_log[] = $this->t('- Updated @name from @installed_version to @updated_version', [
        '@name' => $name,
        '@installed_version' => $installed_at_start[$name]->version,
        '@updated_version' => $package->version,
      ]);
    }

    $added_packages = $installed_post_apply->getPackagesNotIn($installed_at_start);
    $added_packages->ksort(SORT_NATURAL);
    foreach ($added_packages as $name => $package) {
      $applied_log[] = $this->t('- Installed @name @version', [
        '@name' => $name,
        '@version' => $package->version,
      ]);
    }

    $removed_packages = $installed_at_start->getPackagesNotIn($installed_post_apply);
    $removed_packages->ksort(SORT_NATURAL);
    foreach ($installed_at_start->getPackagesNotIn($installed_post_apply) as $name => $package) {
      $applied_log[] = $this->t('- Uninstalled @name', ['@name' => $name]);
    }
    $message = $this->t("Applied changes:\n@change_list", [
      '@change_list' => implode("\n", array_map('strval', $applied_log)),
    ]);
    $this->logger?->info($message);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => ['recordInstalledPackages'],
      PostRequireEvent::class => ['recordRequestedPackageVersions'],
      PostApplyEvent::class => ['logChanges'],
    ];
  }

}
