<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
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

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The workspace association service.
   *
   * @var \Drupal\workspaces\WorkspaceAssociationInterface
   */
  protected $workspaceAssociation;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new WorkspaceOperationFactory.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspace_association
   *   The workspace association service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, WorkspaceManagerInterface $workspace_manager, WorkspaceAssociationInterface $workspace_association, CacheTagsInvalidatorInterface $cache_tags_invalidator, EventDispatcherInterface $event_dispatcher = NULL, protected ?LoggerInterface $logger = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceAssociation = $workspace_association;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    if (!$event_dispatcher) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $event_dispatcher argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3242573', E_USER_DEPRECATED);
      $event_dispatcher = \Drupal::service('event_dispatcher');
    }
    $this->eventDispatcher = $event_dispatcher;
    if ($this->logger === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520', E_USER_DEPRECATED);
      $this->logger = \Drupal::service('logger.channel.workspaces');
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
    return new WorkspacePublisher($this->entityTypeManager, $this->database, $this->workspaceManager, $this->workspaceAssociation, $this->eventDispatcher, $source, $this->logger);
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
    return new WorkspaceMerger($this->entityTypeManager, $this->database, $this->workspaceAssociation, $this->cacheTagsInvalidator, $source, $target, $this->logger);
  }

}
