<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\Core\Render\HtmlResponse;
use Drupal\media\Controller\OEmbedIframeController;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\Resource;
use Drupal\TestTools\Random;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\media\Controller\OEmbedIframeController
 *
 * @group media
 */
class OEmbedIframeControllerTest extends MediaKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_test_oembed'];

  /**
   * Data provider for testBadHashParameter().
   *
   * @return array
   *   An array of test cases.OffCanvasDialogTest.php
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
   * @dataProvider providerBadHashParameter
   *
   * @covers ::render
   */
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
   * @covers ::render
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

}
