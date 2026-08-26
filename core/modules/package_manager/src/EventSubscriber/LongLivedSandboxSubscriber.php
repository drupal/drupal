<?php

declare(strict_types=1);

namespace Drupal\package_manager\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\SandboxManagerBase;
use PhpTuf\ComposerStager\API\Core\BeginnerInterface;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Maintains state for the long-lived sandbox directory.
 *
 * - Records the timestamp when a sandbox is created (used by cron to determine
 *   when a sandbox is stale and can be removed).
 * - Deletes the sandbox directory when the
 *   `include_unknown_files_in_project_root` setting is changed to FALSE,
 *   because the rsync flags used to sync changes won't delete files that are
 *   now excluded from the source directory.
 *
 * This subscriber intentionally injects only the container to avoid resolving
 * the heavy ComposerStager dependency tree (Beginner, Stager, Committer and
 * their precondition chains) at construction time. Those services are resolved
 * lazily inside onConfigSave() only when the setting actually changes.
 *
 * @internal
 *    This is an internal part of Package Manager and may be changed or removed
 *    at any time without warning. External code should not interact with this
 *    class.
 */
final class LongLivedSandboxSubscriber implements EventSubscriberInterface {

  /**
   * The state key which holds the last time a create event ran.
   *
   * @var string
   */
  public const STATE_KEY = 'package_manager.last_create_event';

  public function __construct(
    private readonly StateInterface $state,
    private readonly TimeInterface $time,
    private readonly ContainerInterface $container,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
      PostCreateEvent::class => 'updateLastCreatedTime',
    ];
  }

  /**
   * Updates state with the last time a create event ran.
   *
   * @param \Drupal\package_manager\Event\PostCreateEvent $event
   *   The event being handled.
   */
  public function updateLastCreatedTime(PostCreateEvent $event): void {
    $this->state->set(static::STATE_KEY, $this->time->getCurrentTime());
  }

  /**
   * Reacts when config is saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event object.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $config = $event->getConfig();
    if ($config->getName() !== 'package_manager.settings' || !$event->isChanged('include_unknown_files_in_project_root')) {
      return;
    }
    // Skip initial module install where the original value is NULL, no sandbox
    // directory exists yet to clean up. Removing this guard will cause test
    // failures (e.g., SymlinkValidatorTest) where the subscriber's side effects
    // during module install are unexpected.
    if ($config->getOriginal('include_unknown_files_in_project_root') === NULL) {
      return;
    }
    // Ensure the sandbox directory is deleted when unknown files setting is
    // changed to FALSE, as the rsync flags won't delete unknown files if they
    // still exist in the source directory.
    // @see \PhpTuf\ComposerStager\Internal\FileSyncer\Service\FileSyncer::buildCommand
    if ($config->get('include_unknown_files_in_project_root') === FALSE) {
      $sandbox_manager = new class(
        $this->container->get(PathLocator::class),
        $this->container->get(BeginnerInterface::class),
        $this->container->get(StagerInterface::class),
        $this->container->get(CommitterInterface::class),
        $this->container->get(QueueFactory::class),
        $this->container->get(EventDispatcherInterface::class),
        $this->container->get(SharedTempStoreFactory::class),
        $this->container->get(TimeInterface::class),
        $this->container->get(PathFactoryInterface::class),
        $this->container->get(FailureMarker::class),
      ) extends SandboxManagerBase {};

      // In direct-write mode, getSandboxDirectory() returns the project root.
      // There is no separate sandbox directory to delete.
      if ($sandbox_manager->isDirectWrite()) {
        return;
      }

      $is_available = $sandbox_manager->isAvailable();
      $sandbox_dir = $sandbox_manager->getSandboxDirectory();

      if (file_exists($sandbox_dir) && $is_available) {
        /** @var \Drupal\Core\File\FileSystemInterface $file_system */
        $file_system = $this->container->get(FileSystemInterface::class);
        $file_system->deleteRecursive($sandbox_dir, function (string $path) use ($file_system): void {
          $file_system->chmod($path, is_dir($path) ? 0700 : 0600);
        });
        $this->container->get(StateInterface::class)->delete(static::STATE_KEY);
      }
    }
  }

}
