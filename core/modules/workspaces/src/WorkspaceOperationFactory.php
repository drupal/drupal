<?php

namespace Drupal\workspaces;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a factory class for workspace operations.
 *
 * @see \Drupal\workspaces\WorkspaceOperationInterface
 * @see \Drupal\workspaces\WorkspacePublisherInterface
 *
 * @internal
 */
class WorkspaceOperationFactory {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceTrackerInterface $workspaceTracker,
    protected EventDispatcherInterface $eventDispatcher,
    #[Autowire(service: 'logger.channel.workspaces')]
    protected LoggerInterface $logger,
    protected ?TimeInterface $time = NULL,
  ) {
    if ($time === NULL) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $time argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3531037', E_USER_DEPRECATED);
      $this->time = \Drupal::time();
    }
  }

  /**
   * Gets the workspace publisher.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $source
   *   A workspace entity.
   *
   * @return \Drupal\workspaces\WorkspacePublisherInterface
   *   A workspace publisher object.
   */
  public function getPublisher(WorkspaceInterface $source) {
    return new WorkspacePublisher($this->entityTypeManager, $this->database, $this->workspaceManager, $this->workspaceTracker, $this->eventDispatcher, $source, $this->logger, $this->time);
  }

  /**
   * Gets the workspace merger.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $source
   *   The source workspace entity.
   * @param \Drupal\workspaces\WorkspaceInterface $target
   *   The target workspace entity.
   *
   * @return \Drupal\workspaces\WorkspaceMergerInterface
   *   A workspace merger object.
   */
  public function getMerger(WorkspaceInterface $source, WorkspaceInterface $target) {
    return new WorkspaceMerger($this->entityTypeManager, $this->database, $this->workspaceTracker, $source, $target, $this->logger);
  }

}
