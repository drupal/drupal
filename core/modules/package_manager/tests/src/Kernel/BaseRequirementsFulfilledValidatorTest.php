<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Event\StatusCheckEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator;
use Drupal\package_manager\Validator\BaseRequirementValidatorTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @covers \Drupal\package_manager\Validator\BaseRequirementsFulfilledValidator
 * @covers \Drupal\package_manager\Validator\BaseRequirementValidatorTrait
 *
 * @group package_manager
 */
class BaseRequirementsFulfilledValidatorTest extends PackageManagerKernelTestBase implements EventSubscriberInterface {

  use BaseRequirementValidatorTrait;

  /**
   * The event class to throw to an error for.
   *
   * @var string
   */
  private string $eventClass;

  /**
   * {@inheritdoc}
   */
  public function validate(PreOperationStageEvent $event): void {
    if (get_class($event) === $this->eventClass) {
      $event->addError([
        t('This will not stand!'),
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
   *
   * @dataProvider providerBaseRequirement
   */
  public function testBaseRequirement(string $event_class): void {
    $this->eventClass = $event_class;

    $validator = $this->container->get(BaseRequirementsFulfilledValidator::class);
    $this->assertEventPropagationStopped($event_class, [$validator, 'validate']);

    $result = ValidationResult::createError([
      t('This will not stand!'),
    ]);

    if ($event_class === StatusCheckEvent::class) {
      $this->assertStatusCheckResults([$result]);
    }
    else {
      $this->assertResults([$result], $event_class);
    }
  }

}
