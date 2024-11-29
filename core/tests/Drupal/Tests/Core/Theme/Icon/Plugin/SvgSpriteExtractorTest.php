<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon\Plugin;

// cspell:ignore corge
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconFinder;
use Drupal\Core\Theme\Plugin\IconExtractor\SvgSpriteExtractor;
use Drupal\Tests\Core\Theme\Icon\IconTestTrait;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Plugin\IconExtractor\SvgSpriteExtractor
 *
 * @group icon
 */
class SvgSpriteExtractorTest extends UnitTestCase {

  use IconTestTrait;

  /**
   * This test plugin id (icon pack id).
   */
  private string $pluginId = 'test_svg_sprite';

  /**
   * The SvgSpriteExtractor instance.
   *
   * @var \Drupal\Core\Theme\Plugin\IconExtractor\SvgSpriteExtractor
   */
  private SvgSpriteExtractor $svgSpriteExtractorPlugin;

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
    $this->svgSpriteExtractorPlugin = new SvgSpriteExtractor(
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
   * Data provider for ::testDiscoverIconsSvgSprite().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerDiscoverIconsSvgSprite(): iterable {
    yield 'empty' => [];

    yield 'svg not sprite is ignored' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        ['/path/source/foo.svg', '<svg xmlns="https://www.w3.org/2000/svg"><path d="M8 15a.5.5 0 0 0"/></svg>'],
      ],
      [],
    ];

    yield 'svg sprite with one symbol' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
          'group' => NULL,
        ],
      ],
      [
        ['/path/source/foo.svg', '<svg><symbol id="bar"></symbol></svg>'],
      ],
      [
        'bar',
      ],
    ];

    yield 'single file with multiple symbol' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        ['/path/source/foo.svg', '<svg><symbol id="foo"></symbol><symbol id="bar"></symbol></svg>'],
      ],
      ['foo', 'bar'],
    ];

    yield 'single file with multiple symbol in defs' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        ['/path/source/foo.svg', '<svg><defs><symbol id="foo"></symbol><symbol id="bar"></symbol></defs></svg>'],
      ],
      ['foo', 'bar'],
    ];

    yield 'suspicious symbol id ignored' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
      [
        ['/path/source/foo.svg', '<svg><symbol id="!script"></symbol><symbol id="not valid"></symbol><symbol id="_foo-bar_"></symbol></svg>'],
      ],
      ['_foo-bar_'],
    ];
  }

  /**
   * Test the SvgSpriteExtractor::discoverIcons() method.
   *
   * @param array<array<string, string>> $files
   *   The files to test from IconFinder::getFilesFromSources.
   * @param array<int, array<int, mixed>> $contents_map
   *   The content returned by fileGetContents() based on absolute_path.
   * @param array<string> $expected
   *   The icon ids expected.
   *
   * @dataProvider providerDiscoverIconsSvgSprite
   */
  public function testDiscoverIconsSvgSprite(array $files = [], array $contents_map = [], array $expected = []): void {
    $this->iconFinder->method('getFilesFromSources')->willReturn($files);
    $this->iconFinder->method('getFileContents')
      ->willReturnMap($contents_map);

    $result = $this->svgSpriteExtractorPlugin->discoverIcons();

    if (empty($expected)) {
      $this->assertEmpty($result);
      return;
    }

    // Basic data are not altered and can be compared directly.
    $index = 0;
    foreach ($result as $icon_id => $icon_data) {
      $expected_id = $this->pluginId . IconDefinition::ICON_SEPARATOR . $expected[$index];
      $this->assertSame($expected_id, $icon_id);
      $index++;
    }
  }

  /**
   * Test the SvgSpriteExtractor::discoverIcons() method with invalid svg.
   */
  public function testDiscoverIconsSvgSpriteInvalid(): void {
    $icon = [
      'icon_id' => 'foo',
      'source' => '/path/source/foo.svg',
    ];
    $this->iconFinder->method('getFilesFromSources')->willReturn($icon);
    $this->iconFinder->method('getFileContents')->willReturn('Not valid svg');

    $icons = $this->svgSpriteExtractorPlugin->discoverIcons();
    $this->assertEmpty($icons);
    foreach (libxml_get_errors() as $error) {
      $this->assertSame("Start tag expected, '<' not found", trim($error->message));
    }
  }

  /**
   * Test the SvgSpriteExtractor::discoverIcons() method with invalid content.
   */
  public function testDiscoverIconsSvgSpriteInvalidContent(): void {
    $icon = [
      'icon_id' => 'foo',
      'source' => '/path/source/foo.svg',
    ];
    $this->iconFinder->method('getFilesFromSources')->willReturn($icon);
    $this->iconFinder->method('getFileContents')->willReturn(FALSE);
    $icons = $this->svgSpriteExtractorPlugin->discoverIcons();
    $this->assertEmpty($icons);
  }

}
