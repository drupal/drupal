<?php

declare(strict_types=1);

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
  public static function providerHtml(): array {
    return [
      'lazy loading attribute already added' => [
        'html' => '<p><img src="foo.png" loading="lazy"></p>',
        'expected' => '<p><img src="foo.png" loading="lazy"></p>',
      ],
      'eager loading attribute already added' => [
        'html' => '<p><img src="foo.png" loading="eager"/></p>',
        'expected' => '<p><img src="foo.png" loading="eager"></p>',
      ],
      'image dimensions provided' => [
        'html' => '<p><img src="foo.png" width="200" height="200"/></p>',
        'expected' => '<p><img src="foo.png" width="200" height="200" loading="lazy"></p>',
      ],
      'width image dimensions provided' => [
        'html' => '<p><img src="foo.png" width="200"/></p>',
        'expected' => '<p><img src="foo.png" width="200"></p>',
      ],
      'height image dimensions provided' => [
        'html' => '<p><img src="foo.png" height="200"/></p>',
        'expected' => '<p><img src="foo.png" height="200"></p>',
      ],
      'invalid loading attribute' => [
        'html' => '<p><img src="foo.png" width="200" height="200" loading="foo"></p>',
        'expected' => '<p><img src="foo.png" width="200" height="200" loading="lazy"></p>',
      ],
      'no image tag' => [
        'html' => '<p>Lorem ipsum...</p>',
        'expected' => '<p>Lorem ipsum...</p>',
      ],
      'no image dimensions provided' => [
        'html' => '<p><img src="foo.png"></p>',
        'expected' => '<p><img src="foo.png"></p>',
      ],
    ];
  }

}
