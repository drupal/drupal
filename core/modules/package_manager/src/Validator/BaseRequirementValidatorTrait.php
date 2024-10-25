<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StatusCheckEvent;

/**
 * Provides methods for base requirement validators.
 *
 * This trait should only be used by validators that check base requirements,
 * which means they run before
 * \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator.
 *
 * Validators which use this trait should NOT stop event propagation.
 *
 * @see \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator
 */
trait BaseRequirementValidatorTrait {

  /**
   * Validates base requirements.
   *
   * @param \Drupal\package_manager\Event\PreOperationStageEvent $event
   *   The event being handled.
   */
  abstract public function validate(PreOperationStageEvent $event): void;

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents(): array {
    // Always run before the BaseRequirementsFulfilledValidator.
    // @see \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator
    $priority = BaseRequirementsFulfilledValidator::PRIORITY + 10;

    return [
      PreCreateEvent::class => ['validate', $priority],
      PreRequireEvent::class => ['validate', $priority],
      PreApplyEvent::class => ['validate', $priority],
      StatusCheckEvent::class => ['validate', $priority],
    ];
  }

}
