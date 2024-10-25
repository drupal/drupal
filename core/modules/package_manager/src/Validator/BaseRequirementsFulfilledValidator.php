<?php

namespace Drupal\package_manager\Validator;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\system\SystemManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates that base requirements do not have any errors.
 *
 * Base requirements are the sorts of things that must be in a good state for
 * Package Manager to be usable. For example, Composer must be available and
 * usable; certain paths of the file system must be writable; the current site
 * cannot be part of a multisite, and so on.
 *
 * This validator simply stops event propagation if any of the validators before
 * it have added error results. Validators that check base requirements should
 * run before this validator (they can use
 * \Drupal\package_manager\Validator\BaseRequirementValidatorTrait to make this
 * easier). To ensure that all base requirement errors are shown to the user, no
 * base requirement validator should stop event propagation itself.
 *
 * Base requirement validators should not depend on each other or assume that
 * Composer is usable in the current environment.
 *
 * @see \Drupal\package_manager\Validator\BaseRequirementValidatorTrait
 */
final class BaseRequirementsFulfilledValidator implements EventSubscriberInterface {

  /**
   * The priority of this validator.
   *
   * @see ::getSubscribedEvents()
   *
   * @var int
   */
  public const PRIORITY = 200;

  /**
   * Validates that base requirements are fulfilled.
   *
   * @param \Drupal\package_manager\Event\PreOperationStageEvent $event
   *   The event.
   */
  public function validate(PreOperationStageEvent $event): void {
    // If there are any errors from the validators which ran before this one,
    // base requirements are not fulfilled. Stop any further validators from
    // running.
    if ($event->getResults(SystemManager::REQUIREMENT_ERROR)) {
      $event->stopPropagation();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreCreateEvent::class => ['validate', self::PRIORITY],
      PreRequireEvent::class => ['validate', self::PRIORITY],
      PreApplyEvent::class => ['validate', self::PRIORITY],
      StatusCheckEvent::class => ['validate', self::PRIORITY],
    ];
  }

}
