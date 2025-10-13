<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\SandboxValidationEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator;
use Drupal\package_manager\Validator\BaseRequirementValidatorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests Base Requirements Fulfilled Validator.
 */
#[Group('package_manager')]
#[CoversClass(BaseRequirementsFulfilledValidator::class)]
#[CoversTrait(BaseRequirementValidatorTrait::class)]
#[RunTestsInSeparateProcesses]
class BaseRequirementsFulfilledValidatorTest extends PackageManagerKernelTestBase implements EventSubscriberInterface {

  use BaseRequirementValidatorTrait;
  use StringTranslationTrait;

  /**
   * The event class to throw to an error for.
   *
   * @var string
   */
  private string $eventClass;

  /**
   * {@inheritdoc}
   */
  public function validate(SandboxValidationEvent $event): void {
    if (get_class($event) === $this->eventClass) {
      $event->addError([
        $this->t('This will not stand!'),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('event_dispatcher')->addSubscriber($this);
  }

  /**
   * Data provider for ::testBaseRequirement().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerBaseRequirement(): array {
    return [
      [PreCreateEvent::class],
      [PreRequireEvent::class],
      [PreApplyEvent::class],
      [StatusCheckEvent::class],
    ];
  }

  /**
   * Tests that base requirement failures stop event propagation.
   *
   * @param string $event_class
   *   The event which should raise a base requirement error, and thus stop
   *   event propagation.
   */
  #[DataProvider('providerBaseRequirement')]
  public function testBaseRequirement(string $event_class): void {
    $this->eventClass = $event_class;

    $validator = $this->container->get(BaseRequirementsFulfilledValidator::class);
    $this->assertEventPropagationStopped($event_class, [$validator, 'validate']);

    $result = ValidationResult::createError([
      $this->t('This will not stand!'),
    ]);

    if ($event_class === StatusCheckEvent::class) {
      $this->assertStatusCheckResults([$result]);
    }
    else {
      $this->assertResults([$result], $event_class);
    }
  }

}
