<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolver;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;

/**
 * @coversDefaultClass \Drupal\media\OEmbed\UrlResolver
 *
 * @group media
 */
class UrlResolverTest extends UnitTestCase {

  /**
   * The mocked request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The URL resolver under test.
   *
   * @var \Drupal\media\OEmbed\UrlResolver
   */
  protected $urlResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->requestContext = $this->prophesize(RequestContext::class);
    $this->urlResolver = new UrlResolver(
      $this->createMock(ProviderRepositoryInterface::class),
      $this->createMock(ResourceFetcherInterface::class),
      $this->createMock(Client::class),
      $this->createMock(ModuleHandlerInterface::class),
      $this->requestContext->reveal()
    );
  }

  /**
   * Data provider for testIsSecure().
   *
   * @see ::testIsSecure()
   *
   * @return array
   */
  public function providerIsSecure() {
    return [
      'no domain' => [
        '/path/to/media.php',
        'http://www.example.com/',
        FALSE,
      ],
      'no base URL domain' => [
        'http://www.example.com/media.php',
        '/invalid/base/url',
        FALSE,
      ],
      'same domain' => [
        'http://www.example.com/media.php',
        'http://www.example.com/',
        FALSE,
      ],
      'different domain' => [
        'http://www.example.com/media.php',
        'http://www.example-assets.com/',
        TRUE,
      ],
      'same subdomain' => [
        'http://foo.example.com/media.php',
        'http://foo.example.com/',
        FALSE,
      ],
      'different subdomain' => [
        'http://assets.example.com/media.php',
        'http://foo.example.com/',
        TRUE,
      ],
      'subdomain and top-level domain' => [
        'http://assets.example.com/media.php',
        'http://example.com/',
        TRUE,
      ],
    ];
  }

  /**
   * Tests that isSecure() behaves properly.
   *
   * @param string $url
   *   The URL to test for security.
   * @param string $base_url
   *   The base URL to compare $url against.
   * @param bool $secure
   *   The expected result of isSecure().
   *
   * @covers ::isSecure
   *
   * @dataProvider providerIsSecure
   */
  public function testIsSecure($url, $base_url, $secure) {
    $this->requestContext->getCompleteBaseUrl()->willReturn($base_url);
    $this->assertSame($secure, $this->urlResolver->isSecure($url));
  }

}
