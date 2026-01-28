<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Test;

use Drupal\Core\Database\Connection;
use Drupal\Core\Test\PhpUnitTestRunner;
use Drupal\Core\Test\SimpletestTestRunResultsStorage;
use Drupal\Core\Test\TestRun;
use Drupal\Core\Test\TestStatus;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;

/**
 * Tests Drupal\Core\Test\PhpUnitTestRunner.
 *
 * @see Drupal\Tests\simpletest\Unit\SimpletestPhpunitRunCommandTest
 */
#[CoversClass(PhpUnitTestRunner::class)]
#[Group('Test')]
class PhpUnitTestRunnerTest extends UnitTestCase {

  /**
   * Tests an error in the test running phase.
   */
  public function testRunTestsError(): void {
    $test_id = 23;
    $log_path = 'test_log_path';

    // Create a mock test run storage.
    $storage = $this->getMockBuilder(SimpletestTestRunResultsStorage::class)
      ->setConstructorArgs([$this->createStub(Connection::class)])
      ->onlyMethods(['createNew'])
      ->getMock();
    $storage->expects($this->once())
      ->method('createNew')
      ->willReturn($test_id);

    // Create a mock runner.
    $runner = $this->getMockBuilder(PhpUnitTestRunner::class)
      ->setConstructorArgs(['', ''])
      ->onlyMethods(['xmlLogFilepath', 'processPhpUnitResults'])
      ->getMock();
    $runner->expects($this->once())
      ->method('xmlLogFilepath')
      ->willReturn($log_path);
    $runner->expects($this->once())
      ->method('processPhpUnitResults');

    // Create a mock process.
    $process = $this->createMock(Process::class);
    $process->expects($this->once())
      ->method('isTerminated')
      ->willReturn(TRUE);
    $process->expects($this->once())
      ->method('getOutput')
      ->willReturn('A most serious error occurred.');
    $process->expects($this->once())
      ->method('getExitCode')
      ->willReturn(TestStatus::SYSTEM);

    // The execute() method expects $status by reference, so we initialize it
    // to some value we don't expect back.
    $test_run = TestRun::createNew($storage);
    $test_run->start(microtime(TRUE));
    $test_run->end(microtime(TRUE));
    $process_outcome = $runner->processPhpUnitOnSingleTestClassOutcome($process, $test_run, 'SomeTest');

    // Make sure our status code made the round trip.
    $this->assertEquals(TestStatus::SYSTEM, $process_outcome['status']);

    // A serious error in runCommand() should give us a fixed set of results.
    $row = reset($process_outcome['phpunit_results']);
    unset($row['time']);
    $fail_row = [
      'test_id' => $test_id,
      'test_class' => 'SomeTest',
      'status' => TestStatus::label(TestStatus::SYSTEM),
      'message' => 'A most serious error occurred.',
      'message_group' => 'Other',
      'function' => '*** Process execution output ***',
      'line' => '0',
      'file' => $log_path,
      'exit_code' => 3,
    ];
    $this->assertEquals($fail_row, $row);
  }

  /**
   * Tests php unit command.
   */
  public function testPhpUnitCommand(): void {
    $runner = new PhpUnitTestRunner($this->root, sys_get_temp_dir());
    $this->assertMatchesRegularExpression('/phpunit/', $runner->phpUnitCommand());
  }

  /**
   * Tests xml log file path.
   */
  public function testXmlLogFilePath(): void {
    $runner = new PhpUnitTestRunner($this->root, sys_get_temp_dir());
    $this->assertStringEndsWith('phpunit-23.xml', $runner->xmlLogFilePath(23));
  }

  public static function providerTestSummarizeResults(): array {
    return [
      [
        [
          [
            'test_class' => static::class,
            'status' => 'pass',
            'time' => 0.010001,
          ],
        ],
        '#pass',
      ],
      [
        [
          [
            'test_class' => static::class,
            'status' => 'fail',
            'time' => 0.010002,
          ],
        ],
        '#fail',
      ],
      [
        [
          [
            'test_class' => static::class,
            'status' => 'exception',
            'time' => 0.010003,
          ],
        ],
        '#exception',
      ],
      [
        [
          [
            'test_class' => static::class,
            'status' => 'debug',
            'time' => 0.010004,
          ],
        ],
        '#debug',
      ],
    ];
  }

  /**
   * Tests summarize results.
   */
  #[DataProvider('providerTestSummarizeResults')]
  public function testSummarizeResults($results, $has_status): void {
    $runner = new PhpUnitTestRunner($this->root, sys_get_temp_dir());
    $summary = $runner->summarizeResults($results);

    $this->assertArrayHasKey(static::class, $summary);
    $this->assertEquals(1, $summary[static::class][$has_status]);
    foreach (array_diff(['#pass', '#fail', '#exception', '#debug'], [$has_status]) as $should_be_zero) {
      $this->assertSame(0, $summary[static::class][$should_be_zero]);
    }
  }

}
