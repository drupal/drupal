<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks that Drupal's settings are valid for Package Manager.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class SettingsValidator implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Checks that Drupal's settings are valid for Package Manager.
   */
  public function validate(SandboxValidationEvent $event): void {
    if (Settings::get('update_fetch_with_http_fallback')) {
      $event->addError([
        $this->t('The <code>update_fetch_with_http_fallback</code> setting must be disabled.'),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => 'validate',
      PreApplyEvent::class => 'validate',
      StatusCheckEvent::class => 'validate',
    ];
  }

}
