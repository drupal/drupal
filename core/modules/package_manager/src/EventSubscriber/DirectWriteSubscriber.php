<?php

declare(strict_types=1);

namespace Drupal\package_manager\EventSubscriber;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles sandbox events when direct-write is enabled.
 *
 * @internal
 *    This is an internal part of Package Manager and may be changed or removed
 *    at any time without warning. External code should not interact with this
 *    class.
 */
final class DirectWriteSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The state key which holds the original status of maintenance mode.
   *
   * @var string
   */
  private const STATE_KEY = 'package_manager.maintenance_mode';

  public function __construct(private readonly StateInterface $state) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatusCheckEvent::class => 'warnAboutDirectWrite',
      // We want to go into maintenance mode after other subscribers, to give
      // them a chance to flag errors.
      PreRequireEvent::class => ['enterMaintenanceMode', -10000],
      // We want to exit maintenance mode as early as possible.
      PostRequireEvent::class => ['exitMaintenanceMode', 10000],
    ];
  }

  /**
   * Logs a warning about direct-write mode, if it is in use.
   *
   * @param \Drupal\package_manager\Event\StatusCheckEvent $event
   *   The event being handled.
   */
  public function warnAboutDirectWrite(StatusCheckEvent $event): void {
    if ($event->sandboxManager->isDirectWrite()) {
      $event->addWarning([
        $this->t('Direct-write mode is enabled, which means that changes will be made without sandboxing them first. This can be risky and is not recommended for production environments. For safety, your site will be put into maintenance mode while dependencies are updated.'),
      ]);
    }
  }

  /**
   * Enters maintenance mode before a direct-mode require operation.
   *
   * @param \Drupal\package_manager\Event\PreRequireEvent $event
   *   The event being handled.
   */
  public function enterMaintenanceMode(PreRequireEvent $event): void {
    $errors = $event->getResults(RequirementSeverity::Error->value);

    if (empty($errors) && $event->sandboxManager->isDirectWrite()) {
      $this->state->set(static::STATE_KEY, (bool) $this->state->get('system.maintenance_mode'));
      $this->state->set('system.maintenance_mode', TRUE);
    }
  }

  /**
   * Leaves maintenance mode after a direct-mode require operation.
   *
   * @param \Drupal\package_manager\Event\PreRequireEvent $event
   *   The event being handled.
   */
  public function exitMaintenanceMode(PostRequireEvent $event): void {
    if ($event->sandboxManager->isDirectWrite()) {
      $this->state->set('system.maintenance_mode', $this->state->get(static::STATE_KEY));
      $this->state->delete(static::STATE_KEY);
    }
  }

}
