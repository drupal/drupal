<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon\Plugin;

// cspell:ignore corge

use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\IconFinder;
use Drupal\Core\Theme\Plugin\IconExtractor\SvgExtractor;
use Drupal\Tests\Core\Theme\Icon\IconTestTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Plugin\IconExtractor\SvgExtractor
 *
 * @group icon
 */
class SvgExtractorTest extends UnitTestCase {

  use IconTestTrait;

  /**
   * This test plugin id (icon pack id).
   */
  private string $pluginId = 'test_svg';

  /**
   * The SvgExtractor instance.
   *
   * @var \Drupal\Core\Theme\Plugin\IconExtractor\SvgExtractor
   */
  private SvgExtractor $svgExtractorPlugin;

  /**
   * The IconFinder instance.
   *
   * @var \Drupal\Core\Theme\Icon\IconFinder|\PHPUnit\Framework\MockObject\MockObject
   */
  private IconFinder $iconFinder;

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    $this->iconFinder = $this->createMock(IconFinder::class);
    $this->svgExtractorPlugin = new SvgExtractor(
      [
        'id' => $this->pluginId,
        'config' => ['sources' => ['foo/bar/{icon_id}.svg']],
        'template' => '_foo_',
        'relative_path' => 'modules/my_module',
      ],
      $this->pluginId,
      [],
      $this->iconFinder,
    );
  }

  /**
   * Data provider for ::testDiscoverIconsSvg().
   *
   * @return \Generator
   *   The test cases, icons files returned by IconFinder::getFilesFromSources.
   */
  public static function providerDiscoverIconsSvg() {
    yield 'empty files' => [
      [],
      TRUE,
    ];

    yield 'svg file' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
          'group' => NULL,
        ],
      ],
    ];

    yield 'multiple files' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
          'group' => NULL,
        ],
        'bar' => [
          'icon_id' => 'bar',
          'source' => 'source/bar.svg',
          'absolute_path' => '/path/source/bar.svg',
          'group' => 'corge',
        ],
        'baz' => [
          'icon_id' => 'baz',
          'source' => 'source/baz.svg',
          'absolute_path' => '/path/source/baz.svg',
          'group' => NULL,
        ],
      ],
    ];
  }

  /**
   * Test the SvgExtractor::discoverIcons() method.
   *
   * @param array<array<string, string>> $files
   *   The files to test from IconFinder::getFilesFromSources.
   * @param bool $expected_empty
   *   Has icon result, default FALSE.
   *
   * @dataProvider providerDiscoverIconsSvg
   */
  public function testDiscoverIconsSvg(array $files, bool $expected_empty = FALSE): void {
    $this->iconFinder->method('getFilesFromSources')->willReturn($files);

    $result = $this->svgExtractorPlugin->discoverIcons();

    if (TRUE === $expected_empty) {
      $this->assertEmpty($result);
      return;
    }

    $expected_result = [];
    foreach ($files as $icon) {
      $expected_id = $this->pluginId . IconDefinition::ICON_SEPARATOR . $icon['icon_id'];
      $expected_result[$expected_id] = [
        'source' => $icon['source'],
        'absolute_path' => $icon['absolute_path'],
        'group' => $icon['group'],
      ];
    }

    $this->assertEquals($expected_result, $result);
  }

  /**
   * Test the SvgExtractor::discoverIcons() method with remote svg.
   */
  public function testDiscoverIconsRemoteIgnored(): void {
    $svgExtractorPlugin = new SvgExtractor(
      [
        'id' => $this->pluginId,
        'config' => [
          'sources' => [
            'http://foo/bar.svg',
            'https://foo/bar.svg',
            'https://your-bucket-name.s3.amazonaws.com/foo/bar.svg',
          ],
        ],
        'template' => '_foo_',
        'relative_path' => 'modules/my_module',
      ],
      $this->pluginId,
      [],
      $this->iconFinder,
    );
    $icons = $svgExtractorPlugin->discoverIcons();

    $this->assertEmpty($icons);
  }

  /**
   * Data provider for ::testLoadIconSvg().
   *
   * @return \Generator
   *   The test cases, icons data returned by SvgExtractor::discoverIcons.
   */
  public static function providerLoadIconSvg() {
    yield 'svg file empty' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        '',
      ],
    ];

    yield 'svg file' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        '<svg xmlns="https://www.w3.org/2000/svg"><g><path d="M8 15a.5.5 0 0 0"/></g></svg>',
      ],
      [
        '<g><path d="M8 15a.5.5 0 0 0"/></g>',
      ],
    ];

    yield 'svg file with group' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
          'group' => 'bar',
        ],
      ],
      [
        '<svg xmlns="https://www.w3.org/2000/svg"><g><path d="M8 15a.5.5 0 0 0"/></g></svg>',
      ],
      [
        '<g><path d="M8 15a.5.5 0 0 0"/></g>',
      ],
    ];

    yield 'svg file with attributes' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        '<svg xmlns="https://www.w3.org/2000/svg" data-foo="bar" data-baz="foo"><g><path d="M8 15a.5.5 0 0 0"/></g></svg>',
      ],
      [
        '<g><path d="M8 15a.5.5 0 0 0"/></g>',
      ],
      [
        [
          'data-foo' => 'bar',
          'data-baz' => 'foo',
        ],
      ],
    ];

    yield 'svg sprite is ignored' => [
      [
        [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><symbol id="foo"><g><path d="M8 15a.5.5 0 0 0"/></g></symbol>/svg>',
      ],
      [
        '',
      ],
    ];
  }

  /**
   * Test the SvgExtractor::loadIcon() method.
   *
   * @param array<array<string, string>> $icons_extracted
   *   The icons data to test.
   * @param array<string> $file_content
   *   The content returned by IconFinder:getFileContents.
   * @param array<string> $expected_content
   *   The icons expected content.
   * @param array<string, string> $expected_attributes
   *   The attributes expected.
   *
   * @dataProvider providerLoadIconSvg
   */
  public function testLoadIconSvg(array $icons_extracted = [], array $file_content = [], array $expected_content = [], ?array $expected_attributes = NULL): void {
    foreach ($icons_extracted as $index => $icon) {
      $this->iconFinder->method('getFileContents')
        ->with($icon['absolute_path'])
        ->willReturn($file_content[$index]);
      $icon_loaded = $this->svgExtractorPlugin->loadIcon($icon);

      // Empty or ignored file test.
      if (empty($expected_content[$index])) {
        $this->assertNull($icon_loaded);
        continue;
      }
      $this->assertInstanceOf(IconDefinitionInterface::class, $icon_loaded);

      $data_loaded = $icon_loaded->getAllData();
      $this->assertEquals($expected_content[$index], $data_loaded['content']);

      $expected_attributes[$index] = new Attribute($expected_attributes[$index] ?? []);
      $this->assertEquals($expected_attributes[$index], $data_loaded['attributes']);

      $expected_id = $this->pluginId . IconDefinition::ICON_SEPARATOR . $icon['icon_id'];
      $this->assertSame($expected_id, $icon_loaded->getId());
      // Basic data are not altered and can be compared directly.
      $this->assertSame($icon['icon_id'], $icon_loaded->getIconId());
      $this->assertSame($icon['source'], $icon_loaded->getSource());
      $this->assertSame($icon['group'] ?? NULL, $icon_loaded->getGroup());
    }
  }

  /**
   * Test the SvgExtractor::loadIcon() method with invalid svg.
   */
  public function testLoadIconSvgInvalid(): void {
    $icon = [
      'icon_id' => 'foo',
      'source' => '/path/source/foo.svg',
    ];
    $this->iconFinder->method('getFileContents')
      ->with($icon['source'])
      ->willReturn('Not valid svg');
    $icon_loaded = $this->svgExtractorPlugin->loadIcon($icon);

    $this->assertNull($icon_loaded);

    foreach (libxml_get_errors() as $error) {
      $this->assertSame("Start tag expected, '<' not found", trim($error->message));
    }
  }

}
