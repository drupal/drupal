<?php

namespace Drupal\Tests\update\Unit;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\update\UpdateFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Tests update functionality unrelated to the database.
 *
 * @coversDefaultClass \Drupal\update\UpdateFetcher
 *
 * @group update
 */
class UpdateFetcherTest extends UnitTestCase implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * The update fetcher to use.
   *
   * @var \Drupal\update\UpdateFetcher
   */
  protected $updateFetcher;

  /**
   * History of requests/responses.
   *
   * @var array
   */
  protected $history = [];

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $mockHttpClient;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $mockConfigFactory;

  /**
   * A test project to fetch with.
   *
   * @var array
   */
  protected $testProject;

  /**
   * @var array
   */
  protected $logMessages = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->mockConfigFactory = $this->getConfigFactoryStub(['update.settings' => ['fetch_url' => 'http://www.example.com']]);
    $this->mockHttpClient = $this->createMock('\GuzzleHttp\ClientInterface');
    $settings = new Settings([]);
    $this->updateFetcher = new UpdateFetcher($this->mockConfigFactory, $this->mockHttpClient, $settings);
    $this->testProject = [
      'name' => 'update_test',
      'project_type' => '',
      'info' => [
        'version' => '',
        'project status url' => 'https://www.example.com',
      ],
      'includes' => ['module1' => 'Module 1', 'module2' => 'Module 2'],
    ];

    // Set up logger factory so that watchdog_exception() does not break and
    // register this class as the logger so we can test messages.
    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $logger_factory = new LoggerChannelFactory();
    $logger_factory->addLogger($this);
    $container->expects($this->any())
      ->method('get')
      ->with('logger.factory')
      ->willReturn($logger_factory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests that buildFetchUrl() builds the URL correctly.
   *
   * @param array $project
   *   A keyed array of project information matching results from
   *   \Drupal\update\UpdateManager::getProjects().
   * @param string $site_key
   *   A string to mimic an anonymous site key hash.
   * @param string $expected
   *   The expected URL returned from UpdateFetcher::buildFetchUrl()
   *
   * @dataProvider providerTestUpdateBuildFetchUrl
   *
   * @see \Drupal\update\UpdateFetcher::buildFetchUrl()
   */
  public function testUpdateBuildFetchUrl(array $project, $site_key, $expected) {
    $url = $this->updateFetcher->buildFetchUrl($project, $site_key);
    $this->assertEquals($url, $expected);
    $this->assertSame([], $this->logMessages);
  }

  /**
   * Provide test data for self::testUpdateBuildFetchUrl().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'project' - An array matching a project's .info file structure.
   *   - 'site_key' - An arbitrary site key.
   *   - 'expected' - The expected URL from UpdateFetcher::buildFetchUrl().
   */
  public function providerTestUpdateBuildFetchUrl() {
    $data = [];

    // First test that we didn't break the trivial case.
    $project['name'] = 'update_test';
    $project['project_type'] = '';
    $project['info']['version'] = '';
    $project['info']['project status url'] = 'http://www.example.com';
    $project['includes'] = ['module1' => 'Module 1', 'module2' => 'Module 2'];
    $site_key = '';
    $expected = "http://www.example.com/{$project['name']}/current";

    $data[] = [$project, $site_key, $expected];

    // For disabled projects it shouldn't add the site key either.
    $site_key = 'site_key';
    $project['project_type'] = 'disabled';
    $expected = "http://www.example.com/{$project['name']}/current";

    $data[] = [$project, $site_key, $expected];

    // For enabled projects, test adding the site key.
    $project['project_type'] = '';
    $expected = "http://www.example.com/{$project['name']}/current";
    $expected .= '?site_key=site_key';
    $expected .= '&list=' . rawurlencode('module1,module2');

    $data[] = [$project, $site_key, $expected];

    // Test when the URL contains a question mark.
    $project['info']['project status url'] = 'http://www.example.com/?project=';
    $expected = "http://www.example.com/?project=/{$project['name']}/current";
    $expected .= '&site_key=site_key';
    $expected .= '&list=' . rawurlencode('module1,module2');

    $data[] = [$project, $site_key, $expected];

    return $data;
  }

  /**
   * Mocks the HTTP client.
   *
   * @param \GuzzleHttp\Psr7\Response ...
   *   Variable number of Response objects that the mocked client should return.
   */
  protected function mockClient(Response ...$responses) {
    // Create a mock and queue responses.
    $mock_handler = new MockHandler($responses);
    $handler_stack = HandlerStack::create($mock_handler);
    $history = Middleware::history($this->history);
    $handler_stack->push($history);
    $this->mockHttpClient = new Client(['handler' => $handler_stack]);
  }

  /**
   * @covers ::doRequest
   * @covers ::fetchProjectData
   */
  public function testUpdateFetcherNoFallback() {
    // First, try without the HTTP fallback setting, and HTTPS mocked to fail.
    $settings = new Settings([]);
    $this->mockClient(
      new Response('500', [], 'HTTPS failed'),
    );
    $update_fetcher = new UpdateFetcher($this->mockConfigFactory, $this->mockHttpClient, $settings);

    $data = $update_fetcher->fetchProjectData($this->testProject, '');
    // There should only be one request / response pair.
    $this->assertCount(1, $this->history);
    $request = $this->history[0]['request'];
    $this->assertNotEmpty($request);
    // It should have only been an HTTPS request.
    $this->assertEquals('https', $request->getUri()->getScheme());
    // And it should have failed.
    $response = $this->history[0]['response'];
    $this->assertEquals(500, $response->getStatusCode());
    $this->assertEmpty($data);
    $this->assertSame(["Server error: `GET https://www.example.com/update_test/current` resulted in a `500 Internal Server Error` response:\nHTTPS failed\n"], $this->logMessages);
  }

  /**
   * @covers ::doRequest
   * @covers ::fetchProjectData
   */
  public function testUpdateFetcherHttpFallback() {
    $settings = new Settings(['update_fetch_with_http_fallback' => TRUE]);
    $this->mockClient(
      new Response('500', [], 'HTTPS failed'),
      new Response('200', [], 'HTTP worked'),
    );
    $update_fetcher = new UpdateFetcher($this->mockConfigFactory, $this->mockHttpClient, $settings);

    $data = $update_fetcher->fetchProjectData($this->testProject, '');

    // There should be two request / response pairs.
    $this->assertCount(2, $this->history);

    // The first should have been HTTPS and should have failed.
    $first_try = $this->history[0];
    $this->assertNotEmpty($first_try);
    $this->assertEquals('https', $first_try['request']->getUri()->getScheme());
    $this->assertEquals(500, $first_try['response']->getStatusCode());

    // The second should have been the HTTP fallback and should have worked.
    $second_try = $this->history[1];
    $this->assertNotEmpty($second_try);
    $this->assertEquals('http', $second_try['request']->getUri()->getScheme());
    $this->assertEquals(200, $second_try['response']->getStatusCode());
    // Although this is a bogus mocked response, it's what fetchProjectData()
    // should return in this case.
    $this->assertEquals('HTTP worked', $data);
    $this->assertSame(["Server error: `GET https://www.example.com/update_test/current` resulted in a `500 Internal Server Error` response:\nHTTPS failed\n"], $this->logMessages);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    $this->logMessages[] = $context['@message'];
  }

}
