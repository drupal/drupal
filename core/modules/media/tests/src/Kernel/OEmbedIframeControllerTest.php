<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Render\HtmlResponse;
use Drupal\media\Controller\OEmbedIframeController;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\TestTools\Random;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Drupal\media\Controller\OEmbedIframeController.
 */
#[CoversClass(OEmbedIframeController::class)]
#[Group('media')]
#[RunTestsInSeparateProcesses]
class OEmbedIframeControllerTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_test_oembed'];

  /**
   * Data provider for testBadHashParameter().
   *
   * @return array
   *   An array of test cases.
   */
  public static function providerBadHashParameter() {
    return [
      'no hash' => [
        '',
      ],
      'invalid hash' => [
        Random::string(),
      ],
    ];
  }

  /**
   * Tests validation of the 'hash' query string parameter.
   *
   * @param string $hash
   *   The 'hash' query string parameter.
   *
   * @legacy-covers ::render
   */
  #[DataProvider('providerBadHashParameter')]
  public function testBadHashParameter($hash): void {
    /** @var callable $controller */
    $controller = $this->container
      ->get('controller_resolver')
      ->getControllerFromDefinition('\Drupal\media\Controller\OEmbedIframeController::render');

    $this->assertIsCallable($controller);

    $this->expectException('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException');
    $this->expectExceptionMessage('This resource is not available');
    $request = new Request([
      'url' => 'https://example.com/path/to/resource',
      'hash' => $hash,
    ]);
    $controller($request);
  }

  /**
   * Tests that resources can be used in media_oembed_iframe preprocess.
   *
   * @see media_test_oembed_preprocess_media_oembed_iframe()
   *
   * @legacy-covers ::render
   */
  public function testResourcePassedToPreprocess(): void {
    $hash = $this->container->get('media.oembed.iframe_url_helper')
      ->getHash('', 0, 0);

    $url_resolver = $this->prophesize('\Drupal\media\OEmbed\UrlResolverInterface');
    $resource_fetcher = $this->prophesize('\Drupal\media\OEmbed\ResourceFetcherInterface');

    $provider = new Provider('YouTube', 'https://youtube.com', [
      [
        'url' => 'https://youtube.com/foo',
      ],
    ]);
    $resource = Resource::rich('<iframe src="https://youtube.com/watch?feature=oembed"></iframe>', 320, 240, $provider);

    $resource_fetcher->fetchResource(Argument::cetera())->willReturn($resource);

    $this->container->set('media.oembed.url_resolver', $url_resolver->reveal());
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    $request = new Request([
      'url' => '',
      'hash' => $hash,
    ]);
    $response = $this->container->get('html_response.attachments_processor')
      ->processAttachments(OEmbedIframeController::create($this->container)
        ->render($request));
    assert($response instanceof HtmlResponse);
    $content = $response->getContent();

    // This query parameter is added by
    // media_test_oembed_preprocess_media_oembed_iframe() for YouTube videos.
    $this->assertStringContainsString('&pasta=rigatoni', $content);
    $this->assertStringContainsString('test.css', $content);
    $this->assertContains('yo_there', $response->getCacheableMetadata()->getCacheTags());
    $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
  }

  /**
   * Tests that the response max age is set to the same value as that of the resource.
   *
   * @return void
   *   No return value.
   */
  public function testResponseCacheMaxAge(): void {
    $hash = $this->container->get('media.oembed.iframe_url_helper')
      ->getHash('', 0, 0);

    $url_resolver = $this->prophesize(UrlResolverInterface::class);
    $resource_fetcher = $this->prophesize(ResourceFetcherInterface::class);

    $provider = new Provider('YouTube', 'https://youtube.com', [
      [
        'url' => 'https://youtube.com/foo',
      ],
    ]);
    $resource = Resource::rich(
      '<iframe src="https://youtube.com/watch?feature=oembed"></iframe>',
      320,
      240,
      $provider,
      cache_age: 1234
    );

    $resource_fetcher->fetchResource(Argument::cetera())->willReturn($resource);

    $this->container->set('media.oembed.url_resolver', $url_resolver->reveal());
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    $response = OEmbedIframeController::create($this->container)
      ->render(new Request([
        'url' => '',
        'hash' => $hash,
      ]));
    assert($response instanceof HtmlResponse);

    $this->assertEquals(1234, $response->getCacheableMetadata()->getCacheMaxAge());
  }

  /**
   * Tests that the response max age is set to 0 when a ResourceException is raised while fetching the resource.
   *
   * @return void
   *   No return value.
   */
  public function testResponseCacheMaxAgeUponResourceException(): void {
    $hash = $this->container->get('media.oembed.iframe_url_helper')
      ->getHash('', 0, 0);

    $url_resolver = $this->prophesize(UrlResolverInterface::class);
    $resource_fetcher = $this->prophesize(ResourceFetcherInterface::class);

    $resource_fetcher->fetchResource(Argument::cetera())->willThrow(new ResourceException(
      'Error while fetching resource.',
      'https://youtube.com/foo',
    ));

    $this->container->set('media.oembed.url_resolver', $url_resolver->reveal());
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    $response = OEmbedIframeController::create($this->container)
      ->render(new Request([
        'url' => '',
        'hash' => $hash,
      ]));
    assert($response instanceof HtmlResponse);

    $this->assertEquals(0, $response->getCacheableMetadata()->getCacheMaxAge());
  }

  /**
   * Tests that the response max age is 5 years when the max age returned by the provider is greater than 5 years.
   *
   * @return void
   *   No return value.
   */
  public function testResponseCacheMaxAgeGreaterThanFiveYears(): void {
    $hash = $this->container->get('media.oembed.iframe_url_helper')
      ->getHash('', 0, 0);

    $url_resolver = $this->prophesize(UrlResolverInterface::class);
    $resource_fetcher = $this->prophesize(ResourceFetcherInterface::class);

    $provider = new Provider('YouTube', 'https://youtube.com', [
      [
        'url' => 'https://youtube.com/foo',
      ],
    ]);
    $resource = Resource::rich(
      '<iframe src="https://youtube.com/watch?feature=oembed"></iframe>',
      320,
      240,
      $provider,
      // Five years and one second.
      cache_age: 157680001
    );

    $resource_fetcher->fetchResource(Argument::cetera())->willReturn($resource);

    $this->container->set('media.oembed.url_resolver', $url_resolver->reveal());
    $this->container->set('media.oembed.resource_fetcher', $resource_fetcher->reveal());

    $response = OEmbedIframeController::create($this->container)
      ->render(new Request([
        'url' => '',
        'hash' => $hash,
      ]));
    assert($response instanceof HtmlResponse);

    // The max age should be 5 years.
    $this->assertEquals(157680000, $response->getCacheableMetadata()->getCacheMaxAge());
  }

}
