<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cron;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Cron;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Test Cron handling of suspended queues with a delay.
 *
 * @group Cron
 * @covers \Drupal\Core\Queue\SuspendQueueException
 * @coversDefaultClass \Drupal\Core\Cron
 */
final class CronSuspendQueueDelayTest extends UnitTestCase {

  /**
   * Constructor arguments for \Drupal\Core\Cron.
   *
   * @var object[]|\PHPUnit\Framework\MockObject\MockObject[]
   */
  protected $cronConstructorArguments;

  /**
   * A worker for testing.
   *
   * @var \Drupal\Core\Queue\QueueWorkerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $workerA;

  /**
   * A worker for testing.
   *
   * @var \Drupal\Core\Queue\QueueWorkerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $workerB;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $lock = $this->createMock(LockBackendInterface::class);
    $lock->expects($this->any())
      ->method('acquire')
      ->willReturn(TRUE);
    $this->cronConstructorArguments = [
      'module_handler' => $this->createMock(ModuleHandlerInterface::class),
      'lock' => $lock,
      'queue_factory' => $this->createMock(QueueFactory::class),
      'state' => $this->createMock(StateInterface::class),
      'account_switcher' => $this->createMock(AccountSwitcherInterface::class),
      'logger' => $this->createMock(LoggerInterface::class),
      'queue_manager' => $this->createMock(QueueWorkerManagerInterface::class),
      'time' => $this->createMock(TimeInterface::class),
      'queue_config' => [],
    ];

    // Capture error logs.
    $config = $this->createMock(ImmutableConfig::class);
    $config->expects($this->any())
      ->method('get')
      ->with('logging')
      ->willReturn(0);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->expects($this->any())
      ->method('get')
      ->with('system.cron')
      ->willReturn($config);
    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);

    $this->workerA = $this->createMock(QueueWorkerInterface::class);
    $this->workerA->expects($this->any())
      ->method('getPluginDefinition')
      ->willReturn([
        'cron' => [
          'time' => 300,
        ],
      ]);

    $this->workerB = $this->createMock(QueueWorkerInterface::class);
    $this->workerB->expects($this->any())
      ->method('getPluginDefinition')
      ->willReturn([
        'cron' => [
          'time' => 300,
        ],
      ]);
  }

  /**
   * Tests a queue is reprocessed again after other queues.
   *
   * Two queues are created:
   *  - test_worker_a.
   *  - test_worker_b.
   *
   * Queues and items are processed:
   *  - test_worker_a:
   *    - item throws SuspendQueueException with 2.0 delay.
   *  - test_worker_b:
   *    - item executes normally.
   *  - test_worker_a:
   *    - item throws SuspendQueueException with 3.0 delay.
   *  - test_worker_a:
   *    - no items remaining, quits.
   */
  public function testSuspendQueue(): void {
    [
      'queue_factory' => $queueFactory,
      'queue_manager' => $queueManager,
      'time' => $time,
    ] = $this->cronConstructorArguments;

    $cron = $this->getMockBuilder(Cron::class)
      ->onlyMethods(['usleep'])
      ->setConstructorArgs($this->cronConstructorArguments)
      ->getMock();

    $cron->expects($this->exactly(2))
      ->method('usleep')
      ->withConsecutive(
        [$this->equalTo(2000000)],
        [$this->equalTo(3000000)],
      );

    $queueManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'test_worker_a' => [
          'id' => 'test_worker_a',
          'cron' => ['time' => 300],
        ],
        'test_worker_b' => [
          'id' => 'test_worker_b',
          'cron' => ['time' => 300],
        ],
      ]);

    $queueA = $this->createMock(QueueInterface::class);
    $queueB = $this->createMock(QueueInterface::class);
    $queueFactory->expects($this->exactly(2))
      ->method('get')
      ->willReturnMap([
        ['test_worker_a', FALSE, $queueA],
        ['test_worker_b', FALSE, $queueB],
      ]);

    // Expect this queue to be processed twice.
    $queueA->expects($this->exactly(3))
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
      // First run will suspend for 2 seconds.
        (object) ['data' => 'test_data_a1'],
        // Second run will suspend for 3 seconds.
        (object) ['data' => 'test_data_a2'],
        // This will terminate the queue normally.
        FALSE,
      );
    // Expect this queue to be processed once.
    $queueB->expects($this->exactly(2))
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
        (object) ['data' => 'test_data_b1'],
        // This will terminate the queue normally.
        FALSE,
      );

    $queueManager->expects($this->any())
      ->method('createInstance')
      ->willReturnMap([
        ['test_worker_a', [], $this->workerA],
        ['test_worker_b', [], $this->workerB],
      ]);

    $this->workerA->expects($this->exactly(2))
      ->method('processItem')
      ->with($this->anything())
      ->willReturnOnConsecutiveCalls(
        $this->throwException(new SuspendQueueException('', 0, NULL, 2.0)),
        $this->throwException(new SuspendQueueException('', 0, NULL, 3.0))
      );
    $this->workerB->expects($this->once())
      ->method('processItem')
      ->with('test_data_b1');

    $time->expects($this->any())
      ->method('getCurrentTime')
      ->willReturn(60);

    $cron->run();
  }

  /**
   * Tests queues may be re-processed by whether delay exceeds threshold.
   *
   * Cron will pause and reprocess a queue after a delay if a worker throws
   * a SuspendQueueException with a delay time not exceeding the maximum wait
   * config.
   *
   * @param float $threshold
   *   The configured threshold.
   * @param float $suspendQueueDelay
   *   An interval in seconds a worker will suspend the queue.
   * @param bool $expectQueueDelay
   *   Whether to expect cron to sleep and re-process the queue.
   *
   * @dataProvider providerSuspendQueueThreshold
   */
  public function testSuspendQueueThreshold(float $threshold, float $suspendQueueDelay, bool $expectQueueDelay): void {
    $this->cronConstructorArguments['queue_config'] = [
      'suspendMaximumWait' => $threshold,
    ];
    [
      'queue_factory' => $queueFactory,
      'queue_manager' => $queueManager,
    ] = $this->cronConstructorArguments;

    $cron = $this->getMockBuilder(Cron::class)
      ->onlyMethods(['usleep'])
      ->setConstructorArgs($this->cronConstructorArguments)
      ->getMock();

    $cron->expects($expectQueueDelay ? $this->once() : $this->never())
      ->method('usleep');

    $queueManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'test_worker' => [
          'id' => 'test_worker',
          'cron' => 300,
        ],
      ]);

    $queue = $this->createMock(QueueInterface::class);
    $queueFactory->expects($this->once())
      ->method('get')
      ->willReturnMap([
        ['test_worker', FALSE, $queue],
      ]);
    $queue->expects($this->exactly($expectQueueDelay ? 2 : 1))
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
        (object) ['data' => 'test_data'],
        FALSE,
      );

    $queueManager->expects($this->exactly(1))
      ->method('createInstance')
      ->with('test_worker')
      ->willReturn($this->workerA);

    $this->workerA->expects($this->once())
      ->method('processItem')
      ->with($this->anything())
      ->willReturnOnConsecutiveCalls(
        $this->throwException(new SuspendQueueException('', 0, NULL, $suspendQueueDelay)),
      );

    $cron->run();
  }

  /**
   * Data for testing.
   *
   * @return array
   *   Scenarios for testing.
   */
  public function providerSuspendQueueThreshold(): array {
    $scenarios = [];
    $scenarios['cron will wait for the queue, and rerun'] = [
      15.0,
      10.0,
      TRUE,
    ];
    $scenarios['cron will not wait for the queue, and exit'] = [
      15.0,
      20.0,
      FALSE,
    ];
    return $scenarios;
  }

  /**
   * Tests queues are executed in order.
   *
   * If multiple queues are delayed, they must execute in order of time.
   */
  public function testSuspendQueueOrder(): void {
    [
      'queue_factory' => $queueFactory,
      'queue_manager' => $queueManager,
      'time' => $time,
    ] = $this->cronConstructorArguments;

    $cron = $this->getMockBuilder(Cron::class)
      ->onlyMethods(['usleep'])
      ->setConstructorArgs($this->cronConstructorArguments)
      ->getMock();

    $cron->expects($this->any())
      ->method('usleep');

    $queueManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'test_worker_a' => [
          'id' => 'test_worker_a',
          'cron' => ['time' => 300],
        ],
        'test_worker_b' => [
          'id' => 'test_worker_b',
          'cron' => ['time' => 300],
        ],
        'test_worker_c' => [
          'id' => 'test_worker_c',
          'cron' => ['time' => 300],
        ],
        'test_worker_d' => [
          'id' => 'test_worker_d',
          'cron' => ['time' => 300],
        ],
      ]);

    $queueA = $this->createMock(QueueInterface::class);
    $queueB = $this->createMock(QueueInterface::class);
    $queueC = $this->createMock(QueueInterface::class);
    $queueD = $this->createMock(QueueInterface::class);
    $queueFactory->expects($this->exactly(4))
      ->method('get')
      ->willReturnMap([
        ['test_worker_a', FALSE, $queueA],
        ['test_worker_b', FALSE, $queueB],
        ['test_worker_c', FALSE, $queueC],
        ['test_worker_d', FALSE, $queueD],
      ]);

    $queueA->expects($this->any())
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
        (object) ['data' => 'test_data_from_queue_a'],
        FALSE,
      );
    $queueB->expects($this->any())
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
        (object) ['data' => 'test_data_from_queue_b'],
        (object) ['data' => 'test_data_from_queue_b'],
        FALSE,
      );
    $queueC->expects($this->any())
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
        (object) ['data' => 'test_data_from_queue_c'],
        (object) ['data' => 'test_data_from_queue_c'],
        FALSE,
      );
    $queueD->expects($this->any())
      ->method('claimItem')
      ->willReturnOnConsecutiveCalls(
        (object) ['data' => 'test_data_from_queue_d'],
        FALSE,
      );

    // Recycle the same worker for all queues to test order sanely:
    $queueManager->expects($this->any())
      ->method('createInstance')
      ->willReturnMap([
        ['test_worker_a', [], $this->workerA],
        ['test_worker_b', [], $this->workerA],
        ['test_worker_c', [], $this->workerA],
        ['test_worker_d', [], $this->workerA],
      ]);

    $this->workerA->expects($this->exactly(6))
      ->method('processItem')
      ->withConsecutive(
        // All queues are executed in sequence of definition:
        [$this->equalTo('test_data_from_queue_a')],
        [$this->equalTo('test_data_from_queue_b')],
        [$this->equalTo('test_data_from_queue_c')],
        [$this->equalTo('test_data_from_queue_d')],
        // Queue C is executed again, and before queue B.
        [$this->equalTo('test_data_from_queue_c')],
        // Queue B is executed again, after queue C since its delay was longer.
        [$this->equalTo('test_data_from_queue_b')],
      )
      ->willReturnOnConsecutiveCalls(
        NULL,
        $this->throwException(new SuspendQueueException('', 0, NULL, 16.0)),
        $this->throwException(new SuspendQueueException('', 0, NULL, 8.0)),
        NULL,
        NULL,
        NULL,
      );

    $currentTime = 60;
    $time->expects($this->any())
      ->method('getCurrentTime')
      ->willReturnCallback(function () use (&$currentTime): int {
        return (int) $currentTime;
      });
    $time->expects($this->any())
      ->method('getCurrentMicroTime')
      ->willReturnCallback(function () use (&$currentTime): float {
        return (float) $currentTime;
      });

    $cron->expects($this->exactly(2))
      ->method('usleep')
      ->withConsecutive(
        // Expect to wait for 8 seconds.
        [
          $this->callback(function (int $microseconds) use (&$currentTime) {
            // Accelerate time by 4 seconds.
            $currentTime += 4;
            return $microseconds === 8000000;
          }),
        ],
        // SuspendQueueException requests to delay by 16 seconds, but 4 seconds
        // have passed above, so there are just 12 seconds remaining:
        [$this->equalTo(12000000)],
      );

    $cron->run();
  }

}
