<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Htmx;

use Drupal\Core\Htmx\Htmx;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test the request attributes for HTMX.
 */
#[CoversClass(Htmx::class)]
#[Group('Htmx')]
#[RunTestsInSeparateProcesses]
class HtmxRequestTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'test_htmx',
  ];

  /**
   * Class under test.
   */
  protected Htmx $htmx;

  /**
   * Injected kernel service.
   */
  protected HttpKernelInterface $httpKernel;

  /**
   * Prepared Url object.
   */
  protected Url $url;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->httpKernel = $this->container->get('http_kernel');
    $this->url = Url::fromRoute('test_htmx.attachments.page');
  }

  /**
   * Test all 5 request verb methods.
   */
  #[DataProvider('htmxRequestProvider')]
  public function testHxRequest(string $method): void {
    $attribute = "data-hx-$method";
    $render = [];
    (new Htmx())->$method($this->url)->applyTo($render);
    $this->assertArrayHasKey($attribute, $render['#attributes']);
    $expected = '/htmx-test-attachments/page';
    // The paths in GitLabCI include a subfolder.
    $this->assertStringEndsWith($expected, $render['#attributes'][$attribute]);
    $this->assertArrayNotHasKey('data-hx-drupal-only-main-content', $render['#attributes']);

    // Verify wrapper format.
    $render = [];
    (new Htmx())->$method($this->url)->onlyMainContent()->applyTo($render);
    $this->assertArrayHasKey($attribute, $render['#attributes']);
    $this->assertStringEndsWith('/htmx-test-attachments/page', $render['#attributes'][$attribute]);
    $this->assertTrue($render['#attributes']['data-hx-drupal-only-main-content']);

    // Verify no parameters with no wrapper format.
    $url = Url::fromRoute('test_htmx.attachments.replace');
    $request = Request::create($url->toString());
    $this->httpKernel->handle($request);
    $render = [];
    (new Htmx())->$method()->applyTo($render);
    $this->assertArrayHasKey($attribute, $render['#attributes']);
    $expected = '';
    $this->assertSame($expected, $render['#attributes'][$attribute]);
    $this->assertArrayNotHasKey('data-hx-drupal-only-main-content', $render['#attributes']);

    // Verify no parameters.
    $render = [];
    (new Htmx())->$method()->onlyMainContent()->applyTo($render);
    $this->assertArrayHasKey($attribute, $render['#attributes']);
    $this->assertSame('', $render['#attributes'][$attribute]);
    $this->assertTrue($render['#attributes']['data-hx-drupal-only-main-content']);
  }

  /**
   * Data provider for testHxRequest.
   */
  public static function htmxRequestProvider(): array {
    return [
      ['get'],
      ['post'],
      ['put'],
      ['patch'],
      ['delete'],
    ];
  }

}
