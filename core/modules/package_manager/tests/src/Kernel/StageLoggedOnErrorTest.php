<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\package_manager\Event\PostApplyEvent;
use Drupal\package_manager\Event\PostCreateEvent;
use Drupal\package_manager\Event\PostRequireEvent;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\PackageManagerUninstallValidator;
use Drupal\package_manager\SandboxManagerBase;
use Drupal\package_manager_test_validation\EventSubscriber\TestSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LogLevel;

/**
 * Tests Drupal\package_manager\SandboxManagerBase.
 *
 * @internal
 */
#[CoversClass(SandboxManagerBase::class)]
#[Group('package_manager')]
#[CoversClass(PackageManagerUninstallValidator::class)]
#[RunTestsInSeparateProcesses]
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
   * Tests logged on error.
   *
   * @param string $event_class
   *   The event class to throw an exception on.
   *
   * @legacy-covers \Drupal\package_manager\SandboxManagerBase::dispatch
   */
  #[DataProvider('providerLoggedOnError')]
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
    catch (SandboxEventException $e) {
      $this->assertInstanceOf($event_class, $e->event);

      $predicate = function (array $record) use ($e): bool {
        $context = $record['context'];
        return $context['@message'] === $e->getMessage() && str_contains($context['@backtrace_string'], 'testLoggedOnError');
      };
      $this->assertTrue($logger->hasRecordThatPasses($predicate, LogLevel::ERROR));
    }
  }

}
