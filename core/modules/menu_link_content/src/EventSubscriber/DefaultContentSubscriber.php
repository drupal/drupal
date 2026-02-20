<?php

declare(strict_types=1);

namespace Drupal\menu_link_content\EventSubscriber;

use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to default content-related events.
 *
 * @internal
 *   Event subscribers are internal.
 */
final class DefaultContentSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
    #[AutowireServiceClosure('logger.channel.default_content')]
    private readonly \Closure $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreExportEvent::class => 'preExport',
    ];
  }

  /**
   * Reacts before an entity is exported.
   *
   * Adds an export callback to ensure parent menu links are marked as
   * dependencies, when exporting menu link content entities.
   *
   * @param \Drupal\Core\DefaultContent\PreExportEvent $event
   *   The event object.
   */
  public function preExport(PreExportEvent $event): void {
    if (!$event->entity instanceof MenuLinkContentInterface) {
      return;
    }

    $parentId = $event->entity->getParentId();
    if (!str_starts_with($parentId, 'menu_link_content:')) {
      return;
    }

    [, $uuid] = explode(':', $parentId);
    $parent = $this->entityRepository->loadEntityByUuid('menu_link_content', $uuid);
    if ($parent instanceof MenuLinkContentInterface) {
      $event->metadata->addDependency($parent);
      return;
    }

    $this->getLogger()->error("The parent (%parent) of menu link %uuid could not be loaded.", [
      '%parent' => $uuid,
      '%uuid' => $event->entity->uuid(),
    ]);
  }

  /**
   * Gets the logging service.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logging service.
   */
  private function getLogger(): LoggerInterface {
    return ($this->logger)();
  }

}
