<?php

declare(strict_types=1);

namespace Drupal\package_manager\Validator;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Checks that the environment has support for Package Manager.
 *
 * @internal
 *   This is an internal part of Package Manager and may be changed or removed
 *   at any time without warning. External code should not interact with this
 *   class.
 */
final class EnvironmentSupportValidator implements EventSubscriberInterface {

  use BaseRequirementValidatorTrait {
    getSubscribedEvents as private getSubscribedEventsFromTrait;
  }
  use StringTranslationTrait;

  /**
   * The name of the environment variable to check.
   *
   * This environment variable, if defined, should be parseable by
   * \Drupal\Core\Url::fromUri() and link to an explanation of why Package
   * Manager is not supported in the current environment.
   *
   * @var string
   */
  public const VARIABLE_NAME = 'DRUPAL_PACKAGE_MANAGER_NOT_SUPPORTED_HELP_URL';

  /**
   * Checks that this environment supports Package Manager.
   */
  public function validate(SandboxValidationEvent $event): void {
    $message = $this->t('Package Manager is not supported by your environment.');

    $help_url = getenv(static::VARIABLE_NAME);
    if (empty($help_url)) {
      return;
    }
    // If the URL is not parseable, catch the exception that Url::fromUri()
    // would generate.
    try {
      $message = $this->t('<a href=":url">@message</a>', [
        ':url' => Url::fromUri($help_url)->toString(),
        '@message' => $message,
      ]);
    }
    catch (\InvalidArgumentException) {
      // No need to do anything here. The message just won't be a link.
    }
    $event->addError([$message]);
    // If Package Manager is unsupported, there's no point in doing any more
    // validation.
    $event->stopPropagation();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Set priority to run before BaseRequirementsFulfilledValidator, and even
    // before other base requirement validators.
    // @see \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator
    return array_map(fn () => ['validate', BaseRequirementsFulfilledValidator::PRIORITY + 1000], static::getSubscribedEventsFromTrait());
  }

}
