<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager_test_validation\EventSubscriber\TestSubscriber;
use Psr\Log\LogLevel;
use ColinODell\PsrTestLogger\TestLogger;

/**
 * @coversDefaultClass \Drupal\package_manager\StageBase
 * @covers \Drupal\package_manager\PackageManagerUninstallValidator
 * @group package_manager
 * @internal
 */
class StageLoggedOnErrorTest extends PackageManagerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['package_manager_test_validation'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Since this test adds arbitrary event listeners that aren't services, we
    // need to ensure they will persist even if the container is rebuilt when
    // staged changes are applied.
    $container->getDefinition('event_dispatcher')->addTag('persist');
  }

  /**
   * Data provider for testLoggedOnError().
   *
   * @return string[][]
   *   The test cases.
   */
  public static function providerLoggedOnError(): array {
    return [
      [PreCreateEvent::class],
      [PostCreateEvent::class],
      [PreRequireEvent::class],
      [PostRequireEvent::class],
      [PreApplyEvent::class],
      [PostApplyEvent::class],
    ];
  }

  /**
   * @covers \Drupal\package_manager\StageBase::dispatch
   *
   * @dataProvider providerLoggedOnError
   *
   * @param string $event_class
   *   The event class to throw an exception on.
   */
  public function testLoggedOnError(string $event_class): void {
    $exception = new \Exception("This should be logged!");
    TestSubscriber::setException($exception, $event_class);

    $stage = $this->createStage();
    $logger = new TestLogger();
    $stage->setLogger($logger);

    try {
      $stage->create();
      $stage->require(['drupal/core:9.8.1']);
      $stage->apply();
      $stage->postApply();
      $this->fail('Expected an exception to be thrown, but none was.');
    }
    catch (StageEventException $e) {
      $this->assertInstanceOf($event_class, $e->event);

      $predicate = function (array $record) use ($e): bool {
        $context = $record['context'];
        return $context['@message'] === $e->getMessage() && str_contains($context['@backtrace_string'], 'testLoggedOnError');
      };
      $this->assertTrue($logger->hasRecordThatPasses($predicate, LogLevel::ERROR));
    }
  }

}
