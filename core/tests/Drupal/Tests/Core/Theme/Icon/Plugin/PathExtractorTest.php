<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconFinder;
use Drupal\Core\Theme\Plugin\IconExtractor\PathExtractor;
use Drupal\Tests\Core\Theme\Icon\IconTestTrait;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Plugin\IconExtractor\PathExtractor
 *
 * @group icon
 */
class PathExtractorTest extends UnitTestCase {

  use IconTestTrait;

  /**
   * This test plugin id (icon pack id).
   */
  private string $pluginId = 'test_path';

  /**
   * The PathExtractor instance.
   *
   * @var \Drupal\Core\Theme\Plugin\IconExtractor\PathExtractor
   */
  private PathExtractor $pathExtractorPlugin;

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
    $this->pathExtractorPlugin = new PathExtractor(
      [
        'id' => $this->pluginId,
        'config' => ['sources' => ['foo/bar/baz.svg']],
        'template' => '_foo_',
        'relative_path' => 'modules/my_module',

      ],
      $this->pluginId,
      [],
      $this->iconFinder,
    );
  }

  /**
   * Data provider for ::testDiscoverIconsPath().
   *
   * @return \Generator
   *   The test cases, icons data with expected result.
   */
  public static function providerDiscoverIconsPath(): iterable {
    yield 'empty files' => [
      [],
      TRUE,
    ];

    yield 'single file' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
        ],
      ],
    ];

    yield 'multiple files with group' => [
      [
        'foo' => [
          'icon_id' => 'foo',
          'source' => 'source/foo.svg',
          'absolute_path' => '/path/source/foo.svg',
          'group' => 'baz',
        ],
        'bar' => [
          'icon_id' => 'bar',
          'source' => 'source/bar.svg',
          'absolute_path' => '/path/source/bar.svg',
          'group' => NULL,
        ],
        'baz' => [
          'icon_id' => 'baz',
          'source' => 'source/baz.svg',
          'absolute_path' => '/path/source/baz.svg',
        ],
      ],
    ];
  }

  /**
   * Test the PathExtractor::discoverIcons() method.
   *
   * @param array<array<string, string>> $files
   *   The files to test from IconFinder::getFilesFromSources.
   * @param bool $expected_empty
   *   Has icon result, default FALSE.
   *
   * @dataProvider providerDiscoverIconsPath
   */
  public function testDiscoverIconsPath(array $files, bool $expected_empty = FALSE): void {
    $this->iconFinder->method('getFilesFromSources')->willReturn($files);

    $result = $this->pathExtractorPlugin->discoverIcons();

    if (TRUE === $expected_empty) {
      $this->assertEmpty($result);
      return;
    }

    // Result expected is keyed by icon_id with values 'source' and 'group'.
    $expected_result = [];
    foreach ($files as $icon) {
      $expected_id = $this->pluginId . IconDefinition::ICON_SEPARATOR . $icon['icon_id'];
      if (!isset($icon['group'])) {
        $icon['group'] = NULL;
      }
      unset($icon['icon_id']);
      $expected_result[$expected_id] = $icon;
    }
    $this->assertEquals($expected_result, $result);
  }

}
