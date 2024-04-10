<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Base class for Announce Kernel tests.
 */
class AnnounceTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'announcements_feed',
  ];

  /**
   * History of requests/responses.
   *
   * @var array
   */
  protected array $history = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');
    $this->installConfig(['user']);
  }

  /**
   * Sets the feed items to be returned for the test.
   *
   * @param mixed[][] $feed_items
   *   The feeds items to test. Every time the http_client makes a request the
   *   next item in this array will be returned. For each feed item 'title' and
   *   'url' are omitted because they do not need to vary between test cases.
   */
  protected function setFeedItems(array $feed_items): void {
    $responses = [];
    foreach ($feed_items as $feed_item) {
      $feed_item += [
        'title' => 'Drupal security update Test',
        'url' => 'https://www.drupal.org/project/announce',
      ];
      $responses[] = new Response(200, [], json_encode(['items' => [$feed_item]]));
    }
    $this->setTestFeedResponses($responses);
  }

  /**
   * Sets test feed responses.
   *
   * @param \GuzzleHttp\Psr7\Response[] $responses
   *   The responses for the http_client service to return.
   */
  protected function setTestFeedResponses(array $responses): void {
    // Create a mock and queue responses.
    $mock = new MockHandler($responses);
    $handler_stack = HandlerStack::create($mock);
    $history = Middleware::history($this->history);
    $handler_stack->push($history);
    // Rebuild the container because the 'system.sa_fetcher' service and other
    // services may already have an instantiated instance of the 'http_client'
    // service without these changes.
    $this->container->get('kernel')->rebuildContainer();
    $this->container = $this->container->get('kernel')->getContainer();
    $this->container->set('http_client', new Client(['handler' => $handler_stack]));
  }

}
