<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Unit;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolver;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the oEmbed URL resolver.
 */
#[CoversClass(UrlResolver::class)]
#[Group('media')]
class UrlResolverTest extends UnitTestCase {

  /**
   * Creates a UrlResolver with exposed protected methods for testing.
   *
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   *
   * @return \Drupal\media\OEmbed\UrlResolver
   *   A UrlResolver instance with a public discoverResourceUrl method.
   */
  protected function createTestableUrlResolver(Client $client): UrlResolver {
    return new class (
      $this->createMock(ProviderRepositoryInterface::class),
      $this->createMock(ResourceFetcherInterface::class),
      $client,
      $this->createMock(ModuleHandlerInterface::class),
      new NullBackend('default'),
    ) extends UrlResolver {

      /**
       * {@inheritdoc}
       */
      public function discoverResourceUrl($url): string|false {
        return parent::discoverResourceUrl($url);
      }

    };
  }

  /**
   * Tests that discoverResourceUrl parses HTML responses.
   */
  public function testDiscoverResourceUrlParsesHtml(): void {
    $html_with_oembed = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <link rel="alternate" href="https://example.com/oembed?url=test" type="application/json+oembed">
</head>
<body></body>
</html>
HTML;

    $response = new Response(200, ['Content-Type' => 'text/html'], $html_with_oembed);
    $mock_handler = new MockHandler([$response]);
    $client = new Client(['handler' => HandlerStack::create($mock_handler)]);

    $url_resolver = $this->createTestableUrlResolver($client);
    $result = $url_resolver->discoverResourceUrl('https://example.com/some-page');

    $this->assertSame('https://example.com/oembed?url=test', $result);
  }

  /**
   * Tests that discoverResourceUrl skips non-HTML responses.
   */
  public function testDiscoverResourceUrlSkipsNonHtml(): void {
    $response = new Response(200, ['Content-Type' => 'application/json'], '');
    $mock_handler = new MockHandler([$response]);
    $client = new Client(['handler' => HandlerStack::create($mock_handler)]);

    $url_resolver = $this->createTestableUrlResolver($client);
    $result = $url_resolver->discoverResourceUrl('https://example.com/some-page');

    $this->assertFalse($result);
  }

}
