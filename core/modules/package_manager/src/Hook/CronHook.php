<?php

declare(strict_types=1);

namespace Drupal\package_manager\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\package_manager\EventSubscriber\LongLivedSandboxSubscriber;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\SandboxManagerBase;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Implements hook_cron().
 *
 * @internal
 *    This is an internal part of Package Manager and may be changed or removed
 *    at any time without warning. External code should not interact with this
 *    class.
 */
#[Hook('cron')]
final class CronHook {

  public function __construct(
    private readonly PathLocator $pathLocator,
    private readonly BeginnerInterface $beginner,
    private readonly StagerInterface $stager,
    private readonly CommitterInterface $committer,
    private readonly QueueFactory $queueFactory,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly SharedTempStoreFactory $sharedTempStoreFactory,
    private readonly TimeInterface $time,
    private readonly PathFactoryInterface $pathFactory,
    private readonly FailureMarker $failureMarker,
    private readonly StateInterface $state,
    private readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * Implements hook_cron().
   */
  public function __invoke(): void {
    $sandbox_manager = new class(
      $this->pathLocator,
      $this->beginner,
      $this->stager,
      $this->committer,
      $this->queueFactory,
      $this->eventDispatcher,
      $this->sharedTempStoreFactory,
      $this->time,
      $this->pathFactory,
      $this->failureMarker,
    ) extends SandboxManagerBase {};

    // In direct-write mode, getSandboxDirectory() returns the project root.
    // There is no separate sandbox directory to delete.
    if ($sandbox_manager->isDirectWrite()) {
      return;
    }

    $is_available = $sandbox_manager->isAvailable();
    $sandbox_dir = $sandbox_manager->getSandboxDirectory();
    $max_sandbox_age = $this->time->getCurrentTime() - (Settings::get('package_manager_max_sandbox_age', 14) * 24 * 60 * 60);
    $last_create_event = $this->state->get(LongLivedSandboxSubscriber::STATE_KEY);

    // Only delete the sandbox directory if it is older than the max configured
    // age `package_manager_max_sandbox_age` setting, or defaults to 14 days.
    if (file_exists($sandbox_dir) && $last_create_event) {
      if ($last_create_event < $max_sandbox_age && $is_available) {
        $this->fileSystem->deleteRecursive($sandbox_dir, function (string $path): void {
          $this->fileSystem->chmod($path, is_dir($path) ? 0700 : 0600);
        });
        $this->state->delete(LongLivedSandboxSubscriber::STATE_KEY);
      }
    }
  }

}
