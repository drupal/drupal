<?php

declare(strict_types = 1);

namespace Drupal\Tests\filter\Unit;

use Drupal\filter\Plugin\Filter\FilterImageLazyLoad;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\filter\Plugin\Filter\FilterImageLazyLoad
 * @group editor
 */
final class FilterImageLazyLoadTest extends UnitTestCase {

  /**
   * @var \Drupal\filter\Plugin\Filter\FilterImageLazyLoad
   */
  protected FilterImageLazyLoad $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->filter = new FilterImageLazyLoad([], 'filter_image_lazy_load', ['provider' => 'test']);
    parent::setUp();
  }

  /**
   * @covers ::process
   *
   * @dataProvider providerHtml
   *
   * @param string $html
   *   Input HTML.
   * @param string $expected
   *   The expected output string.
   */
  public function testProcess(string $html, string $expected): void {
    $this->assertSame($expected, $this->filter->process($html, 'en')->getProcessedText());
  }

  /**
   * Provides data for testProcess.
   *
   * @return array
   *   An array of test data.
   */
  public function providerHtml(): array {
    return [
      'lazy loading attribute already added' => [
        'input' => '<p><img src="foo.png" loading="lazy"></p>',
        'output' => '<p><img src="foo.png" loading="lazy" /></p>',
      ],
      'eager loading attribute already added' => [
        'input' => '<p><img src="foo.png" loading="eager"/></p>',
        'output' => '<p><img src="foo.png" loading="eager" /></p>',
      ],
      'image dimensions provided' => [
        'input' => '<p><img src="foo.png" width="200" height="200"/></p>',
        '<p><img src="foo.png" width="200" height="200" loading="lazy" /></p>',
      ],
      'width image dimensions provided' => [
        'input' => '<p><img src="foo.png" width="200"/></p>',
        '<p><img src="foo.png" width="200" /></p>',
      ],
      'height image dimensions provided' => [
        'input' => '<p><img src="foo.png" height="200"/></p>',
        '<p><img src="foo.png" height="200" /></p>',
      ],
      'invalid loading attribute' => [
        'input' => '<p><img src="foo.png" width="200" height="200" loading="foo"></p>',
        'output' => '<p><img src="foo.png" width="200" height="200" loading="lazy" /></p>',
      ],
      'no image tag' => [
        'input' => '<p>Lorem ipsum...</p>',
        'output' => '<p>Lorem ipsum...</p>',
      ],
      'no image dimensions provided' => [
        'input' => '<p><img src="foo.png"></p>',
        'output' => '<p><img src="foo.png" /></p>',
      ],
    ];
  }

}
