<?php

declare(strict_types=1);

namespace Drupal\Tests\Core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Cron;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\Memory;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\State\State;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Argument\ArgumentsWildcard;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the Cron class.
 *
 * @group Cron
 * @coversDefaultClass \Drupal\Core\Cron
 */
class CronTest extends UnitTestCase {

  const REQUEUE_COUNT = 3;

  /**
   * Define the duration of each item claim for this test.
   *
   * @var int
   */
  protected $claimTime = 300;

  /**
   * An instance of the Cron class for testing.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * The queue used to store test work items.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The current state of the test in memory.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Construct a state object used for testing logger assertions.
    $this->state = new State(new KeyValueMemoryFactory());

    // Create a mock logger to set a flag in the resulting state.
    $logger = $this->prophesize('Drupal\Core\Logger\LoggerChannelInterface');
    // Safely ignore the cron success message.
    $logger->info('Cron run completed.')->shouldBeCalled();
    // Set a flag to track when a message is logged by adding a callback
    // function for each logging method.
    foreach (get_class_methods(LoggerInterface::class) as $logger_method) {
      $logger->{$logger_method}(Argument::cetera())->will(function () {
        \Drupal::state()->set('cron_test.message_logged', TRUE);
      });
    }

    // Create a logger factory to produce the resulting logger.
    $logger_factory = $this->prophesize('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $logger_factory->get(Argument::exact('cron'))->willReturn($logger->reveal());

    // Create a mock time service.
    $time = $this->prophesize('Drupal\Component\Datetime\TimeInterface');

    // Create a mock config factory and config object.
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('logging')->willReturn(FALSE);
    $config_factory->get('system.cron')->willReturn($config->reveal());

    // Build the container using the resulting mock objects.
    \Drupal::setContainer(new ContainerBuilder());
    \Drupal::getContainer()->set('logger.factory', $logger_factory->reveal());
    \Drupal::getContainer()->set('datetime.time', $time->reveal());
    \Drupal::getContainer()->set('state', $this->state);
    \Drupal::getContainer()->set('config.factory', $config_factory->reveal());

    // Create mock objects for constructing the Cron class.
    $module_handler = $this->prophesize('Drupal\Core\Extension\ModuleHandlerInterface');
    $queue_factory = $this->prophesize('Drupal\Core\Queue\QueueFactory');
    $queue_worker_manager = $this->prophesize('Drupal\Core\Queue\QueueWorkerManagerInterface');
    $state = $this->prophesize('Drupal\Core\State\StateInterface');
    $account_switcher = $this->prophesize('Drupal\Core\Session\AccountSwitcherInterface');
    $queueConfig = [
      'suspendMaximumWait' => 30.0,
    ];

    // Create a lock that will always fail when attempting to acquire; we're
    // only interested in testing ::processQueues(), not the other stuff.
    $lock_backend = $this->prophesize('Drupal\Core\Lock\LockBackendInterface');
    $lock_backend->acquire('cron', Argument::cetera())->willReturn(TRUE);
    $lock_backend->release('cron')->shouldBeCalled();

    // Create a queue worker definition for testing purposes.
    $queue_worker = $this->randomMachineName();
    $queue_worker_definition = [
      'id' => $queue_worker,
      'cron' => [
        'time' => &$this->claimTime,
      ],
    ];

    // Create a queue instance for this queue worker.
    $this->queue = new Memory($queue_worker);
    $queue_factory->get($queue_worker)->willReturn($this->queue);

    // Create a mock queue worker plugin instance based on above definition.
    $queue_worker_plugin = $this->prophesize('Drupal\Core\Queue\QueueWorkerInterface');
    $queue_worker_plugin->getPluginId()->willReturn($queue_worker);
    $queue_worker_plugin->getPluginDefinition()->willReturn($queue_worker_definition);
    $queue_worker_plugin->processItem('Complete')->willReturn();
    $queue_worker_plugin->processItem('Exception')->willThrow(\Exception::class);
    $queue_worker_plugin->processItem('DelayedRequeueException')->willThrow(DelayedRequeueException::class);
    $queue_worker_plugin->processItem('SuspendQueueException')->willThrow(SuspendQueueException::class);
    // 'RequeueException' would normally result in an infinite loop.
    //
    // This is avoided by throwing RequeueException for the first few calls to
    // ::processItem() and then returning void. ::testRequeueException()
    // establishes sanity assertions for this case.
    $queue_worker_plugin->processItem('RequeueException')->will(function ($args, $mock, $method) {
      // Fetch the number of calls to this prophesied method. This value will
      // start at zero during the first call.
      $method_calls = count($mock->findProphecyMethodCalls($method->getMethodName(), new ArgumentsWildcard($args)));

      // Throw the expected exception on the first few calls.
      if ($method_calls < self::REQUEUE_COUNT) {
        \Drupal::state()->set('cron_test.requeue_count', $method_calls + 1);
        throw new RequeueException();
      }
    });

    // Set the mock queue worker manager to return the definition/plugin.
    $queue_worker_manager->getDefinitions()->willReturn([$queue_worker => $queue_worker_definition]);
    $queue_worker_manager->createInstance($queue_worker)->willReturn($queue_worker_plugin->reveal());

    // Construct the Cron class to test.
    $this->cron = new Cron($module_handler->reveal(), $lock_backend->reveal(), $queue_factory->reveal(), $state->reveal(), $account_switcher->reveal(), $logger->reveal(), $queue_worker_manager->reveal(), $time->reveal(), $queueConfig);
  }

  /**
   * Resets the testing state.
   */
  protected function resetTestingState() {
    $this->queue->deleteQueue();
    $this->state->set('cron_test.message_logged', FALSE);
    $this->state->set('cron_test.requeue_count', NULL);
  }

  /**
   * Data provider for ::testProcessQueues() method.
   */
  public function processQueuesTestData() {
    return [
      ['Complete', 'assertFalse', 0],
      ['Exception', 'assertTrue', 1],
      ['DelayedRequeueException', 'assertFalse', 1],
      ['SuspendQueueException', 'assertTrue', 1],
      ['RequeueException', 'assertFalse', 0],
    ];
  }

  /**
   * Tests the ::processQueues() method.
   *
   * @covers ::processQueues
   * @dataProvider processQueuesTestData
   */
  public function testProcessQueues($item, $message_logged_assertion, $count_post_run) {
    $this->resetTestingState();
    $this->queue->createItem($item);
    $this->assertFalse($this->state->get('cron_test.message_logged'));
    $this->assertEquals(1, $this->queue->numberOfItems());
    $this->cron->run();
    $this->{$message_logged_assertion}($this->state->get('cron_test.message_logged'));
    $this->assertEquals($count_post_run, $this->queue->numberOfItems());
  }

  /**
   * Verify that RequeueException causes an item to be processed multiple times.
   */
  public function testRequeueException() {
    $this->resetTestingState();
    $this->queue->createItem('RequeueException');
    $this->cron->run();

    // Fetch the number of times this item was requeued.
    $actual_requeue_count = $this->state->get('cron_test.requeue_count');
    // Make sure the item was requeued at least once.
    $this->assertIsInt($actual_requeue_count);
    // Ensure that the actual requeue count matches the expected value.
    $this->assertEquals(self::REQUEUE_COUNT, $actual_requeue_count);
  }

}
