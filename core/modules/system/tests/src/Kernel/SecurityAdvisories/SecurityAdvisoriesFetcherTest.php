<?php

namespace Drupal\Tests\system\Kernel\SecurityAdvisories;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\system\SecurityAdvisories\SecurityAdvisoriesFetcher
 *
 * @group system
 */
class SecurityAdvisoriesFetcherTest extends KernelTestBase implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The error messages.
   *
   * @var string[]
   */
  protected $errorMessages = [];

  /**
   * The log error log messages.
   *
   * @var string[]
   */
  protected $logErrorMessages = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'advisory_feed_test',
  ];

  /**
   * History of requests/responses.
   *
   * @var array
   */
  protected $history = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');
    $this->container->get('logger.factory')->addLogger($this);
  }

  /**
   * Tests contrib advisories that should be displayed.
   *
   * @param mixed[] $feed_item
   *   The feed item to test. 'title' and 'link' are omitted from this array
   *   because they do not need to vary between test cases.
   * @param string|null $existing_version
   *   The existing version of the module.
   *
   * @dataProvider providerShowAdvisories
   */
  public function testShowAdvisories(array $feed_item, string $existing_version = NULL): void {
    $this->setFeedItems([$feed_item]);
    if ($existing_version !== NULL) {
      $this->setExistingProjectVersion($existing_version);
    }
    $links = $this->getAdvisories();
    $this->assertCount(1, $links);
    $this->assertSame('http://example.com', $links[0]->getUrl());
    $this->assertSame('SA title', $links[0]->getTitle());
    $this->assertCount(1, $this->history);
  }

  /**
   * Data provider for testShowAdvisories().
   */
  public function providerShowAdvisories(): array {
    return [
      'contrib:exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:not-exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '1.0',

      ],
      'contrib:non-matching:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-2.0',
      ],
      'contrib:no-insecure:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => [],
        ],
        'existing_version' => '8.x-2.0',
      ],
      'contrib:no-existing-version:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-2.0'],
        ],
        'existing_version' => '',
      ],
      'contrib:dev:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => [],
        ],
        'existing_version' => '8.x-2.x-dev',
      ],
      'contrib:existing-dev-match-minor:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.x-dev',
      ],
      'contrib:existing-dev-match-major-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.1.1'],
        ],
        'existing_version' => '8.x-dev',
      ],
      'contrib:existing-dev-match-minor-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.2.1'],
        ],
        'existing_version' => '8.2.x-dev',
      ],
      'core:exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [\Drupal::VERSION],
        ],
      ],
      'core:exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [\Drupal::VERSION],
        ],
      ],
      'core:not-exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.1'],
        ],
      ],
      'core:non-matching:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.0.0'],
        ],
      ],
      'core:no-insecure:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [],
        ],
      ],
    ];
  }

  /**
   * Tests advisories that should be ignored.
   *
   * @param mixed[] $feed_item
   *   The feed item to test. 'title' and 'link' are omitted from this array
   *   because they do not need to vary between test cases.
   * @param string|null $existing_version
   *   The existing version of the module.
   *
   * @dataProvider providerIgnoreAdvisories
   */
  public function testIgnoreAdvisories(array $feed_item, string $existing_version = NULL): void {
    $this->setFeedItems([$feed_item]);
    if ($existing_version !== NULL) {
      $this->setExistingProjectVersion($existing_version);
    }
    $this->assertCount(0, $this->getAdvisories());
    $this->assertCount(1, $this->history);
  }

  /**
   * Data provider for testIgnoreAdvisories().
   */
  public function providerIgnoreAdvisories(): array {
    return [
      'contrib:not-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:non-matching:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.1'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:not-exact:non-psa-reversed' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '1.0',
      ],
      'contrib:semver-non-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:semver-major-match-not-minor:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.1.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:semver-major-minor-match-not-patch:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.1.1'],
        ],
        'existing_version' => '1.1.0',
      ],
      'contrib:non-matching-not-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.1'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:both-extra:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0-extraStringNotSpecial'],
        ],
        'existing_version' => '8.x-1.0-alsoNotSpecialNotMatching',
      ],
      'contrib:semver-7major-match:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['7.x-1.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:different-majors:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['7.x-1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:semver-different-majors:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '2.0.0',
      ],
      'contrib:no-version:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.1'],
        ],
        'existing_version' => '',
      ],
      'contrib:insecure-extra:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0-extraStringNotSpecial'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:existing-dev-different-minor:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-2.x-dev',
      ],
      'contrib:existing-dev-different-major:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['7.x-1.0'],
        ],
        'existing_version' => '8.x-1.x-dev',
      ],
      'contrib:existing-dev-different-major-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.0.0'],
        ],
        'existing_version' => '9.0.x-dev',
      ],
      'contrib:existing-dev-different-major-no-minor-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.0.0'],
        ],
        'existing_version' => '9.x-dev',
      ],
      'contrib:existing-dev-different-minor-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '1.1.0-dev',
      ],
      'contrib:existing-dev-different-minor-x-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '1.1.x-dev',
      ],
      'contrib:existing-dev-different-8major-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-dev',
      ],
      'contrib:non-existing-project:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'non_existing_project',
          'insecure' => ['8.x-1.0'],
        ],
      ],
      'contrib:non-existing-project:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'non_existing_project',
          'insecure' => ['8.x-1.0'],
        ],
      ],
      'core:non-matching:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.0.0'],
        ],
      ],
      'core:non-matching-not-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.1'],
        ],
      ],
      'core:no-insecure:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [],
        ],
      ],
      'contrib:existing-extra:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.0-extraStringNotSpecial',

      ],
    ];
  }

  /**
   * Sets the feed items to be returned for the test.
   *
   * @param mixed[][] $feed_items
   *   The feeds items to test. Every time the http_client makes a request the
   *   next item in this array will be returned. For each feed item 'title' and
   *   'link' are omitted because they do not need to vary between test cases.
   */
  protected function setFeedItems(array $feed_items): void {
    $responses = [];
    foreach ($feed_items as $feed_item) {
      $feed_item += [
        'title' => 'SA title',
        'link' => 'http://example.com',
      ];
      $responses[] = new Response('200', [], json_encode([$feed_item]));
    }
    $this->setTestFeedResponses($responses);
  }

  /**
   * Sets the existing version of the project.
   *
   * @param string $existing_version
   *   The existing version of the project.
   */
  protected function setExistingProjectVersion(string $existing_version): void {
    $module_list = $this->prophesize(ModuleExtensionList::class);
    $extension = $this->prophesize(Extension::class)->reveal();
    $extension->info = [
      'project' => 'the_project',
    ];
    if (!empty($existing_version)) {
      $extension->info['version'] = $existing_version;
    }
    $module_list->getList()->willReturn([$extension]);
    $this->container->set('extension.list.module', $module_list->reveal());
  }

  /**
   * Tests that the stored advisories response is deleted on interval decrease.
   */
  public function testIntervalConfigUpdate(): void {
    $feed_item_1 = [
      'is_psa' => 1,
      'type' => 'core',
      'title' => 'Oh no🙀! Advisory 1',
      'project' => 'drupal',
      'insecure' => [\Drupal::VERSION],
    ];
    $feed_item_2 = [
      'is_psa' => 1,
      'type' => 'core',
      'title' => 'Oh no😱! Advisory 2',
      'project' => 'drupal',
      'insecure' => [\Drupal::VERSION],
    ];
    $this->setFeedItems([$feed_item_1, $feed_item_2]);
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_1['title'], $advisories[0]->getTitle());
    $this->assertCount(1, $this->history);

    // Ensure that new feed item is not retrieved because the stored response
    // has not expired.
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $this->history);
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_1['title'], $advisories[0]->getTitle());

    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('system.advisories');
    $interval = $config->get('interval_hours');
    $config->set('interval_hours', $interval + 1)->save();

    // Ensure that new feed item is not retrieved when the interval is
    // increased.
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $this->history);
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_1['title'], $advisories[0]->getTitle());

    // Ensure that new feed item is retrieved when the interval is decreased.
    $config->set('interval_hours', $interval - 1)->save();
    $advisories = $this->getAdvisories();
    $this->assertCount(2, $this->history);
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_2['title'], $advisories[0]->getTitle());
  }

  /**
   * Tests that invalid JSON feed responses are not stored.
   */
  public function testInvalidJsonResponse(): void {
    $non_json_response = new Response(200, [], '1');
    $json_response = new Response(200, [], '[]');
    // Set 2 non-JSON responses and 1 JSON response.
    $this->setTestFeedResponses([
      $non_json_response,
      $non_json_response,
      $json_response,
    ]);
    $this->assertNull($this->getAdvisories());
    $this->assertCount(1, $this->history);
    $this->assertServiceAdvisoryLoggedErrors(['The security advisory JSON feed from Drupal.org could not be decoded.']);

    // Confirm that previous non-JSON response was not stored.
    $this->assertNull($this->getAdvisories());
    $this->assertCount(2, $this->history);
    $this->assertServiceAdvisoryLoggedErrors(['The security advisory JSON feed from Drupal.org could not be decoded.']);

    // Confirm that if $allow_http_request is set to FALSE a new request will
    // not be attempted.
    $this->assertNull($this->getAdvisories(FALSE));
    $this->assertCount(2, $this->history);

    // Make a 3rd request that will return a valid JSON response.
    $this->assertCount(0, $this->getAdvisories());
    $this->assertCount(3, $this->history);

    // Confirm that getting the advisories after a valid JSON response will use
    // the stored response and not make another 'http_client' request.
    $this->assertCount(0, $this->getAdvisories());
    $this->assertCount(3, $this->history);
    $this->assertServiceAdvisoryLoggedErrors([]);
  }

  /**
   * @covers ::doRequest
   * @covers ::getSecurityAdvisories
   */
  public function testHttpFallback(): void {
    $this->setSetting('update_fetch_with_http_fallback', TRUE);
    $feed_item = [
      'is_psa' => 1,
      'type' => 'core',
      'project' => 'drupal',
      'insecure' => [\Drupal::VERSION],
      'title' => 'SA title',
      'link' => 'http://example.com',
    ];
    $this->setTestFeedResponses([
      new Response('500', [], 'HTTPS failed'),
      new Response('200', [], json_encode([$feed_item])),
    ]);
    $advisories = $this->getAdvisories();

    // There should be two request / response pairs.
    $this->assertCount(2, $this->history);

    // The first request should have been HTTPS and should have failed.
    $first_try = $this->history[0];
    $this->assertNotEmpty($first_try);
    $this->assertEquals('https', $first_try['request']->getUri()->getScheme());
    $this->assertEquals(500, $first_try['response']->getStatusCode());

    // The second request should have been the HTTP fallback and should have
    // worked.
    $second_try = $this->history[1];
    $this->assertNotEmpty($second_try);
    $this->assertEquals('http', $second_try['request']->getUri()->getScheme());
    $this->assertEquals(200, $second_try['response']->getStatusCode());

    $this->assertCount(1, $advisories);
    $this->assertSame('http://example.com', $advisories[0]->getUrl());
    $this->assertSame('SA title', $advisories[0]->getTitle());
    $this->assertSame(["Server error: `GET https://updates.drupal.org/psa.json` resulted in a `500 Internal Server Error` response:\nHTTPS failed\n"], $this->errorMessages);
  }

  /**
   * @covers ::doRequest
   * @covers ::getSecurityAdvisories
   */
  public function testNoHttpFallback(): void {
    $this->setTestFeedResponses([
      new Response('500', [], 'HTTPS failed'),
    ]);

    $exception_thrown = FALSE;
    try {
      $this->getAdvisories();
    }
    catch (TransferException $exception) {
      $this->assertSame("Server error: `GET https://updates.drupal.org/psa.json` resulted in a `500 Internal Server Error` response:\nHTTPS failed\n", $exception->getMessage());
      $exception_thrown = TRUE;
    }
    $this->assertTrue($exception_thrown);
    // There should only be one request / response pair.
    $this->assertCount(1, $this->history);
    $request = $this->history[0]['request'];
    $this->assertNotEmpty($request);
    // It should have only been an HTTPS request.
    $this->assertEquals('https', $request->getUri()->getScheme());
    // And it should have failed.
    $response = $this->history[0]['response'];
    $this->assertEquals(500, $response->getStatusCode());
  }

  /**
   * Gets the advisories from the 'system.sa_fetcher' service.
   *
   * @param bool $allow_http_request
   *   Argument to pass on to
   *   SecurityAdvisoriesFetcher::getSecurityAdvisories().
   *
   * @return \Drupal\system\SecurityAdvisories\SecurityAdvisory[]|null
   *   The return value of SecurityAdvisoriesFetcher::getSecurityAdvisories().
   */
  protected function getAdvisories(bool $allow_http_request = TRUE): ?array {
    $fetcher = $this->container->get('system.sa_fetcher');
    return $fetcher->getSecurityAdvisories($allow_http_request);
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
    $this->container->get('logger.factory')->addLogger($this);
    $this->container->set('http_client', new Client(['handler' => $handler_stack]));
    $this->container->setAlias(ClientInterface::class, 'http_client');
  }

  /**
   * Asserts the expected error messages were logged.
   *
   * @param string[] $expected_messages
   *   The expected error messages.
   *
   * @internal
   */
  protected function assertServiceAdvisoryLoggedErrors(array $expected_messages): void {
    $this->assertSame($expected_messages, $this->logErrorMessages);
    $this->logErrorMessages = [];
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    if (isset($context['@message'])) {
      $this->errorMessages[] = $context['@message'];
    }
    if ($level === RfcLogLevel::ERROR) {
      $this->logErrorMessages[] = $message;
    }
  }

}
