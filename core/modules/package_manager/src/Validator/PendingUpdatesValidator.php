<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Update\UpdateRegistry;
use Drupal\Core\Url;
use Drupal\package_manager\Event\StatusCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates that there are no pending database updates.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class PendingUpdatesValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly string $appRoot,
    private readonly UpdateRegistry $updateRegistry,
  ) {}

  /**
   * Validates that there are no pending database updates.
   */
  public function validate(PreOperationStageEvent $event): void {
    if ($this->updatesExist()) {
      $message = $this->t('Some modules have database updates pending. You should run the <a href=":update">database update script</a> immediately.', [
        ':update' => Url::fromRoute('system.db_update')->toString(),
      ]);
      $event->addError([$message]);
    }
  }

  /**
   * Checks if there are any pending update or post-update hooks.
   *
   * @return bool
   *   TRUE if there are any pending update or post-update hooks, FALSE
   *   otherwise.
   */
  public function updatesExist(): bool {
    require_once $this->appRoot . '/core/includes/install.inc';
    require_once $this->appRoot . '/core/includes/update.inc';

    drupal_load_updates();
    $hook_updates = update_get_update_list();
    $post_updates = $this->updateRegistry->getPendingUpdateFunctions();

    return $hook_updates || $post_updates;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => 'validate',
      StatusCheckEvent::class => 'validate',
      PreApplyEvent::class => 'validate',
    ];
  }

}
