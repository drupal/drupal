<?php

namespace Drupal\workspaces\EventSubscriber;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\workspaces\Event\WorkspaceSwitchEvent;
use Drupal\workspaces\WorkspaceInformationInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a event subscriber for reacting to workspace activation.
 *
 * @internal
 */
class WorkspaceSwitchSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly WorkspaceInformationInterface $workspaceInfo,
    #[Autowire(service: 'entity.memory_cache')]
    protected readonly MemoryCacheInterface $entityMemoryCache,
    protected readonly ?AliasManagerInterface $aliasManager = NULL,
  ) {}

  /**
   * Clears various static caches when a new workspace is activated.
   *
   * @param \Drupal\workspaces\Event\WorkspaceSwitchEvent $event
   *   An event object.
   */
  public function onWorkspaceSwitch(WorkspaceSwitchEvent $event): void {
    // Clear the static cache for supported entity types.
    $cache_tags_to_invalidate = [];
    foreach (array_keys($this->workspaceInfo->getSupportedEntityTypes()) as $entity_type_id) {
      $cache_tags_to_invalidate[] = 'entity.memory_cache:' . $entity_type_id;
    }
    $this->entityMemoryCache->invalidateTags($cache_tags_to_invalidate);

    // Clear the static cache for path aliases.
    $this->aliasManager?->cacheClear();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[WorkspaceSwitchEvent::class][] = 'onWorkspaceSwitch';

    return $events;
  }

}
