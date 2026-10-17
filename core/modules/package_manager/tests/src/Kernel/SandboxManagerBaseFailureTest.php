<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\package_manager\Exception\ApplyFailedException;
use Drupal\package_manager\Exception\FailureMarkerExistsException;
use Drupal\package_manager\Exception\SandboxException;
use Drupal\package_manager\FailureMarker;
use Drupal\package_manager\PathLocator;
use Drupal\package_manager\SandboxManagerBase;
use Drupal\package_manager_bypass\LoggingBeginner;
use Drupal\package_manager_bypass\LoggingCommitter;
use Drupal\package_manager_bypass\NoOpStager;
use PhpTuf\ComposerStager\API\Core\CommitterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\package_manager\SandboxManagerBase.
 *
 * @internal
 */
#[CoversClass(SandboxManagerBase::class)]
#[Group('package_manager')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class SandboxManagerBaseFailureTest extends PackageManagerKernelTestBase {

  /**
   * Tests that if a stage fails to apply, another stage cannot be created.
   */
  public function testFailureMarkerPreventsCreate(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);

    // Make the committer throw an exception, which should cause the failure
    // marker to be present.
    $thrown_message = 'Thrown by the committer.';
    LoggingCommitter::setException(\Exception::class, $thrown_message);
    try {
      $stage->apply();
      $this->fail('Expected an exception.');
    }
    catch (ApplyFailedException $e) {
      $this->assertStringContainsString($thrown_message, $e->getMessage());
      $this->assertFalse($stage->isApplying());
    }
    $stage->destroy();

    // Even through the previous stage was destroyed, we cannot create a new one
    // because the failure marker is still there.
    $stage = $this->createStage();
    try {
      $stage->create();
      $this->fail('Expected an exception.');
    }
    catch (FailureMarkerExistsException $e) {
      $this->assertMatchesRegularExpression('/^Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup. Caused by Exception, with this message: ' . $thrown_message . "\nBacktrace:\n#0 .*/", $e->getMessage());
      $this->assertFalse($stage->isApplying());
    }

    // If the failure marker is cleared, we should be able to create the stage
    // without issue.
    $this->container->get(FailureMarker::class)->clear();
    $stage->create();
  }

  /**
   * Tests that the failure marker file doesn't exist if apply succeeds.
   *
   * @see \Drupal\Tests\package_manager\Kernel\StageCommitExceptionTest::testCommitException
   */
  public function testNoFailureFileOnSuccess(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);
    $stage->apply();

    $this->container->get(FailureMarker::class)
      ->assertNotExists();
  }

  /**
   * Data provider for ::testFailureDuringComposerStagerOperations().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerFailureDuringComposerStagerOperations(): array {
    return [
      [LoggingBeginner::class],
      [NoOpStager::class],
      [LoggingCommitter::class],
    ];
  }

  /**
   * Tests when Composer Stager throws an exception during an operation.
   *
   * @param class-string $throwing_class
   *   The fully qualified name of the Composer Stager class that should throw
   *   an exception. It is expected to have a static ::setException() method,
   *   provided by \Drupal\package_manager_bypass\ComposerStagerExceptionTrait.
   */
  #[DataProvider('providerFailureDuringComposerStagerOperations')]
  public function testFailureDuringComposerStagerOperations(string $throwing_class): void {
    $exception_message = "$throwing_class is angry!";
    $throwing_class::setException(\Exception::class, $exception_message, 1024);

    $expected_message = preg_quote($exception_message);
    if ($throwing_class === LoggingCommitter::class) {
      $expected_message = "/^Staged changes failed to apply, and the site is in an indeterminate state. It is strongly recommended to restore the code and database from a backup. Caused by Exception, with this message: $expected_message\nBacktrace:\n#0 .*/";
    }
    else {
      $expected_message = "/^$expected_message$/";
    }

    $stage = $this->createStage();
    try {
      $stage->create();
      $stage->require(['ext-json:*']);
      $stage->apply();
      $this->fail('Expected an exception to be thrown, but it was not.');
    }
    catch (SandboxException $e) {
      $this->assertMatchesRegularExpression($expected_message, $e->getMessage());
      $this->assertSame(1024, $e->getCode());
      $this->assertInstanceOf(\Exception::class, $e->getPrevious());
    }
  }

  /**
   * Tests that the failure marker file is excluded using a relative path.
   */
  public function testFailureMarkerFileExcluded(): void {
    $this->assertResults([]);
    /** @var \Drupal\package_manager_bypass\LoggingCommitter $committer */
    $committer = $this->container->get(CommitterInterface::class);
    $committer_args = $committer->getInvocationArguments();
    $this->assertCount(1, $committer_args);
    $this->assertContains('PACKAGE_MANAGER_FAILURE.yml', $committer_args[0][2]);
  }

  /**
   * Tests that if a stage fails to get paths to exclude, throws a stage exception.
   */
  public function testFailureCollectPathsToExclude(): void {
    $project_root = $this->container->get(PathLocator::class)->getProjectRoot();
    unlink($project_root . '/composer.json');
    $this->expectException(SandboxException::class);
    $this->expectExceptionMessageIsOrContains("composer.json not found.");
    $this->createStage()->create();
  }

  /**
   * Tests that if apply fails to get paths to exclude, throws a stage exception.
   */
  public function testFailureCollectPathsToExcludeOnApply(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/random']);
    $this->expectException(SandboxException::class);
    $this->expectExceptionMessageIsOrContains("composer.json not found.");
    unlink($stage->getSandboxDirectory() . '/composer.json');
    $stage->apply();
  }

}
