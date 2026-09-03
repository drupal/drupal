<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Htmx;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Render\MainContent\HtmxRenderer;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Verifies HtmxRenderer.
 */
#[CoversClass(HtmxRenderer::class)]
#[Group('Htmx')]
#[RunTestsInSeparateProcesses]
class HtmxRendererTest extends KernelTestBase {

  use UserCreationTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'block',
    'user',
    'test_htmx',
  ];

  /**
   * Injected kernel service.
   */
  protected HttpKernelInterface $httpKernel;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->installEntitySchema('user');
    $theme = $this->config('system.theme')->get('default');
    $this->container->get('theme_installer')->install([$theme]);
    $this->placeBlock('system_powered_by_block', [
      'region' => 'header',
    ]);

    $this->setCurrentUser($this->createUser([
      'access content',
    ]));
    $this->httpKernel = $this->container->get('http_kernel');
  }

  /**
   * Test triggering the renderer with _wrapper_format.
   */
  public function testWrapperFormat(): void {
    // Verify the "Powered by" block is rendered on a standard page.
    $url = Url::fromRoute('test_htmx.attachments.replace');
    $request = Request::create($url->toString());
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Powered by', $response->getContent());
    // Verify the body contains only the main content when using the new
    // wrapper format.
    $options = [
      'query' => [
        MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_htmx',
      ],
    ];
    $url = Url::fromRoute('test_htmx.attachments.replace', [], $options);
    $this->assertHtmxResponseContent($url, TRUE);
  }

  /**
   * Test triggering the renderer with the _htmx_route option.
   */
  public function testHtmxRouteOption(): void {
    $url = Url::fromRoute('test_htmx.attachments.route_option');
    $this->assertHtmxResponseContent($url);
  }

  /**
   * Verify expected response from HtmxRenderer.
   *
   * @param \Drupal\Core\Url $url
   *   The url to use for the request.
   * @param bool $wrapper_format
   *   Whether the new wrapper format was used in the request.
   */
  protected function assertHtmxResponseContent(Url $url, bool $wrapper_format = FALSE): void {
    $request = Request::create($url->toString());
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('text/html; charset=utf-8', $response->headers->get('Content-Type'));
    $oneLine = str_replace(["\r", "\n"], "", $response->getContent());
    // Rendered classes vary to provide test features for other tests.
    $div = $wrapper_format ? '<div class="ajax-content htmx-test-flag">Initial Content</div>' : '<div class="ajax-content">Initial Content</div>';
    $this->assertStringContainsString("<body>$div</body>", $oneLine);
  }

}
