<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
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

  use DeprecatedServicePropertyTrait;

  /**
   * Defines deprecated injected properties.
   *
   * @var array
   */
  protected array $deprecatedProperties = [
    'cacheTagInvalidator' => 'cache_tags.invalidator',
  ];

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
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\Drupal\Core\Cache\CacheTagsInvalidatorInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, WorkspaceManagerInterface $workspace_manager, WorkspaceAssociationInterface $workspace_association, EventDispatcherInterface|CacheTagsInvalidatorInterface $event_dispatcher, LoggerInterface|EventDispatcherInterface|null $logger = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceAssociation = $workspace_association;

    if ($event_dispatcher instanceof CacheTagsInvalidatorInterface) {
      $event_dispatcher = \Drupal::service('event_dispatcher');
      @trigger_error('Calling ' . __METHOD__ . '() with the $cache_tags_invalidator argument is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3440755', E_USER_DEPRECATED);
    }
    elseif (!$event_dispatcher instanceof EventDispatcherInterface) {
      $event_dispatcher = \Drupal::service('event_dispatcher');
      @trigger_error('Calling ' . __METHOD__ . '() without the $event_dispatcher argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3242573', E_USER_DEPRECATED);
    }
    $this->eventDispatcher = $event_dispatcher;

    $logger = func_get_arg(5);
    if (!$logger instanceof LoggerInterface) {
      $logger = \Drupal::service('logger.channel.workspaces');
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520', E_USER_DEPRECATED);
    }
    $this->logger = $logger;
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
    return new WorkspaceMerger($this->entityTypeManager, $this->database, $this->workspaceAssociation, $source, $target, $this->logger);
  }

}
