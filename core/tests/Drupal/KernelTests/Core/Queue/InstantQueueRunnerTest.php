<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Queue;

use Drupal\cron_queue_test\Plugin\QueueWorker\CronQueueTestDeriverQueue;
use Drupal\Core\Queue\InstantQueueRunnerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;

/**
 * Queues and unqueues a set of items to check the basic queue functionality.
 */
#[Group('Queue')]
#[RunTestsInSeparateProcesses]
class InstantQueueRunnerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cron_queue_test'];

  /**
   * Tests the instant queue runner.
   */
  public function testInstantQueueRunner(): void {
    $queue1_name = CronQueueTestDeriverQueue::PLUGIN_ID . ':foo';
    $queue1 = $this->container->get('queue')->get($queue1_name);
    $queue2_name = CronQueueTestDeriverQueue::PLUGIN_ID . ':bar';
    $queue2 = $this->container->get('queue')->get($queue2_name);
    $instant_queue_runner = $this->container->get(InstantQueueRunnerInterface::class);
    for ($i = 0; $i < 10; $i++) {
      $queue1->createItem('foo');
    }
    $queue2->createItem('bar');

    for ($i = 0; $i < 5; $i++) {
      $instant_queue_runner->registerQueue($queue1_name);
    }
    $instant_queue_runner->registerQueue($queue2_name);

    $this->assertSame(10, $queue1->numberOfItems());
    $this->assertSame(1, $queue2->numberOfItems());
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $response = new Response();

    $this->container->get('kernel')->terminate($request, $response);
    $this->assertSame(5, $queue1->numberOfItems());
    $this->assertSame(0, $queue2->numberOfItems());
  }

}
