<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\package_manager\Exception\ApplyFailedException;
use Drupal\package_manager\Exception\StageException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager_bypass\LoggingCommitter;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Exception\InvalidArgumentException;
use PhpTuf\ComposerStager\API\Exception\PreconditionException;
use PhpTuf\ComposerStager\API\Precondition\Service\PreconditionInterface;

/**
 * @coversDefaultClass \Drupal\package_manager\StageBase
 * @covers \Drupal\package_manager\PackageManagerUninstallValidator
 * @group package_manager
 * @internal
 */
class StageCommitExceptionTest extends PackageManagerKernelTestBase {

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
   * Data provider for testCommitException().
   *
   * @return \string[][]
   *   The test cases.
   */
  public static function providerCommitException(): array {
    return [
      'RuntimeException to ApplyFailedException' => [
        'RuntimeException',
        ApplyFailedException::class,
      ],
      'InvalidArgumentException' => [
        InvalidArgumentException::class,
        StageException::class,
      ],
      'PreconditionException' => [
        PreconditionException::class,
        StageException::class,
      ],
      'Exception' => [
        'Exception',
        ApplyFailedException::class,
      ],
    ];
  }

  /**
   * Tests exception handling during calls to Composer Stager commit.
   *
   * @param string $thrown_class
   *   The throwable class that should be thrown by Composer Stager.
   * @param string $expected_class
   *   The expected exception class, if different from $thrown_class.
   *
   * @dataProvider providerCommitException
   */
  public function testCommitException(string $thrown_class, string $expected_class): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/core:9.8.1']);

    $throwable_arguments = [
      'A very bad thing happened',
      123,
    ];
    // Composer Stager's exception messages are usually translatable, so they
    // need to be wrapped by a TranslatableMessage object.
    if (is_subclass_of($thrown_class, ExceptionInterface::class)) {
      $throwable_arguments[0] = $this->createComposeStagerMessage($throwable_arguments[0]);
    }
    // PreconditionException requires a preconditions object.
    if ($thrown_class === PreconditionException::class) {
      array_unshift($throwable_arguments, $this->createMock(PreconditionInterface::class));
    }
    LoggingCommitter::setException($thrown_class, ...$throwable_arguments);

    try {
      $stage->apply();
      $this->fail('Expected an exception.');
    }
    catch (\Throwable $exception) {
      $this->assertInstanceOf($expected_class, $exception);
      $this->assertSame(123, $exception->getCode());

      // This needs to be done because we always use the message from
      // \Drupal\package_manager\Stage::getFailureMarkerMessage() when throwing
      // ApplyFailedException.
      if ($expected_class == ApplyFailedException::class) {
        $this->assertMatchesRegularExpression("/^Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup. Caused by $thrown_class, with this message: A very bad thing happened\nBacktrace:\n#0 .*/", $exception->getMessage());
      }
      else {
        $this->assertSame('A very bad thing happened', $exception->getMessage());
      }

      $failure_marker = $this->container->get(FailureMarker::class);
      if ($exception instanceof ApplyFailedException) {
        $this->assertFileExists($failure_marker->getPath());
        $this->assertFalse($stage->isApplying());
      }
      else {
        $failure_marker->assertNotExists();
      }
    }
  }

}
