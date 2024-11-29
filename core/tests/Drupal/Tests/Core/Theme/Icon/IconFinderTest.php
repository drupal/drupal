<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

// cspell:ignore corge grault garply quux plugh
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Theme\Icon\IconFinder;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Icon\IconFinder
 *
 * @group icon
 */
class IconFinderTest extends UnitTestCase {

  private const TEST_ICONS_PATH = 'core/modules/system/tests/modules/icon_test';
  private const TEST_RELATIVE_URL = 'foo/bar';

  /**
   * The file url generator instance.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The logger service instance.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private LoggerInterface $logger;

  /**
   * The IconFinder instance.
   *
   * @var \Drupal\Core\Theme\Icon\IconFinder
   */
  private IconFinder $iconFinder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileUrlGenerator = $this->getMockBuilder('Drupal\Core\File\FileUrlGeneratorInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->iconFinder = new IconFinder(
      $this->fileUrlGenerator,
      $this->logger,
      DRUPAL_ROOT,
    );
  }

  /**
   * Data provider for ::testGetFilesFromSourcesUrl().
   *
   * @return \Generator
   *   The test cases, with expected result as icon_id.
   */
  public static function providerGetFilesFromSourcesUrl(): iterable {
    yield 'empty sources' => [
      [],
    ];

    yield 'url valid' => [
      [
        'http://example.com/foo.png',
        'https://example.com/bar.png',
        'https://example.com/fOO%20folder%20%C3%B9/FoO%21%20BaR%3D%281%29%20iCo-n.png%3Ftest%3DfOO%23bAz',
      ],
      [
        'foo' => 'http://example.com/foo.png',
        'bar' => 'https://example.com/bar.png',
        'FoO! BaR=(1) iCo-n' => 'https://example.com/fOO%20folder%20%C3%B9/FoO%21%20BaR%3D%281%29%20iCo-n.png%3Ftest%3DfOO%23bAz',
      ],
    ];

    yield 'url encoded' => [
      [
        'https://example.com/fOO%20folder%20%C3%B9/FoO%21%20BaR%3D%281%29%20iCo-n.png%3Ftest%3DfOO%23bAz',
      ],
      [
        'FoO! BaR=(1) iCo-n' => 'https://example.com/fOO%20folder%20%C3%B9/FoO%21%20BaR%3D%281%29%20iCo-n.png%3Ftest%3DfOO%23bAz',
      ],
    ];

    yield 'url not encoded' => [
      [
        'https://example.com/fOO folder ù/FoO_Ba,R=(1) iCo-n.png?test=fOO#bAz',
      ],
      [
        'FoO_Ba,R=(1) iCo-n' => 'https://example.com/fOO folder ù/FoO_Ba,R=(1) iCo-n.png?test=fOO#bAz',
      ],
    ];

    yield 'url valid with duplicate' => [
      [
        'http://example.com/foo.png',
        'https://example.com/bar.png',
        'http://example.com/foo.svg',
        'https://example.com/bar.svg',
      ],
      [
        'foo' => 'http://example.com/foo.svg',
        'bar' => 'https://example.com/bar.svg',
      ],
    ];

    yield 'url no extension' => [
      [
        'http://example.com/foo',
        'https://example.com/fOO folder ù/FoO BaR=(1) iCo-n?test=fOO#bAz',
      ],
      [
        'foo' => 'http://example.com/foo',
        'FoO BaR=(1) iCo-n' => 'https://example.com/fOO folder ù/FoO BaR=(1) iCo-n?test=fOO#bAz',
      ],
    ];

    yield 'url valid with special filename' => [
      [
        'http://example.com/foo (è$ù5 6*$^ _ ù48 \'Bar\']=-n!.png',
      ],
      [
        'foo (è$ù5 6*$^ _ ù48 \'Bar\']=-n!' => 'http://example.com/foo (è$ù5 6*$^ _ ù48 \'Bar\']=-n!.png',
      ],
    ];

    // Test invalid scheme and empty path.
    yield 'url not supported scheme or path is invalid' => [
      [
        'ftp://foo/bar.png',
        'ssh://foo/bar.png',
        'sftp://foo/bar.png',
        'htp://foo/bar.png',
        'a://foo/bar.png',
        'http://',
        'https://',
      ],
    ];
  }

  /**
   * Test the IconFinder::getFilesFromSources method with urls.
   *
   * @param array<string> $sources
   *   The list of remote.
   * @param array<string, string> $expected
   *   The expected result.
   *
   * @dataProvider providerGetFilesFromSourcesUrl
   */
  public function testGetFilesFromSourcesUrl(array $sources, array $expected = []): void {
    $result = $this->iconFinder->getFilesFromSources(
      $sources,
      // Relative path is not used for this test url.
      '',
    );

    // Prepare result array matching getFileFromHttpUrl() to minimize test data.
    $expected_result = [];
    foreach ($expected as $expected_icon_id => $expected_source) {
      $expected_result[$expected_icon_id] = [
        'icon_id' => $expected_icon_id,
        'source' => $expected_source,
        'absolute_path' => $expected_source,
      ];
    }

    $this->assertEquals($expected_result, $result);
  }

  /**
   * Data provider for ::testGetFilesFromSourcesPath().
   *
   * @return \Generator
   *   The test cases, to minimize test data, result expected is an array with:
   *   - icon_id: the expected id
   *   - path: expected path found relative to TEST_ICONS_PATH
   *   - group: The group name if any
   */
  public static function providerGetFilesFromSourcesPath(): iterable {
    yield 'path empty' => [
      [
        '',
      ],
    ];

    yield 'direct file without path' => [
      [
        'foo.svg',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file without extension' => [
      [
        'foo',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file without extension wildcard' => [
      [
        'foo.*',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with wildcard and extension' => [
      [
        '*.svg',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with wildcard only' => [
      [
        '*',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with wildcards' => [
      [
        '*.*',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'path valid' => [
      [
        'icons/flat/foo.png',
        'icons/flat/bar.svg',
      ],
      [
        ['foo', 'icons/flat/foo.png'],
        ['bar', 'icons/flat/bar.svg'],
      ],
    ];

    yield 'absolute path valid' => [
      [
        '/' . self::TEST_ICONS_PATH . '/icons/flat/foo.png',
      ],
      [
        ['foo', 'icons/flat/foo.png'],
      ],
    ];

    yield 'path not allowed extension' => [
      [
        // File exist but not valid extension.
        'icons/flat/foo.webp',
        'icons/flat/*.webp',
        // Non existent file.
        'icons/flat/foo-1.jpg',
      ],
    ];

    yield 'path without extension' => [
      [
        'icons/flat/foo',
      ],
      [
        ['foo', 'icons/flat/foo.svg'],
      ],
    ];

    yield 'path wildcard extension' => [
      [
        'icons/flat/foo.*',
      ],
      [
        ['foo', 'icons/flat/foo.svg'],
      ],
    ];

    yield 'path wildcard filename' => [
      [
        'icons/flat/*.svg',
      ],
      [
        ['bar-2', 'icons/flat/bar-2.svg'],
        ['bar', 'icons/flat/bar.svg'],
        ['baz-2', 'icons/flat/baz-2.svg'],
        ['foo', 'icons/flat/foo.svg'],
        ['64x64', 'icons/flat/64x64.svg'],
      ],
    ];

    yield 'path wildcard increment filename' => [
      [
        'icons/flat_same_name/*',
      ],
      [
        ['foo', 'icons/flat_same_name/foo.svg'],
      ],
    ];

    yield 'path wildcard filename with extension' => [
      ['icons/flat/*.svg'],
      [
        ['bar-2', 'icons/flat/bar-2.svg'],
        ['baz-2', 'icons/flat/baz-2.svg'],
        ['foo', 'icons/flat/foo.svg'],
        ['bar', 'icons/flat/bar.svg'],
        ['64x64', 'icons/flat/64x64.svg'],
      ],
    ];

    yield 'path wildcard' => [
      ['*/flat/*'],
      [
        ['bar-2', 'icons/flat/bar-2.svg'],
        ['bar', 'icons/flat/bar.svg'],
        ['baz-1', 'icons/flat/baz-1.png'],
        ['baz-2', 'icons/flat/baz-2.svg'],
        ['foo', 'icons/flat/foo.svg'],
        ['64x64', 'icons/flat/64x64.svg'],
      ],
    ];

    yield 'mix wildcard and extensions' => [
      [
        'icons/flat/foo.*',
        'icons/flat/foo.svg',
      ],
      [
        ['foo', 'icons/flat/foo.svg'],
      ],
    ];

    yield 'path group no result' => [
      ['icons/group/*'],
      [],
    ];

    yield 'path with folder wildcard' => [
      ['icons/group/*/*'],
      [
        ['bar_group_1', 'icons/group/group_1/bar_group_1.png'],
        ['bar_group_2', 'icons/group/group_2/bar_group_2.png'],
        ['baz_group_1', 'icons/group/group_1/baz_group_1.png'],
        ['baz_group_2', 'icons/group/group_2/baz_group_2.png'],
        ['corge_group_1', 'icons/group/group_1/corge_group_1.svg'],
        ['corge_group_2', 'icons/group/group_2/corge_group_2.svg'],
        ['foo_group_1', 'icons/group/group_1/foo_group_1.svg'],
        ['foo_group_2', 'icons/group/group_2/foo_group_2.svg'],
      ],
    ];

    yield 'path sub group wildcard' => [
      ['icons/group/*/sub_sub_group_1/*'],
      [
        ['foo_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/foo_sub_group_1.png'],
        ['bar_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/bar_sub_group_1.svg'],
      ],
    ];

    yield 'path sub group wildcard partial and filename' => [
      ['icons/group/*/sub_sub_group_*/*'],
      [
        ['foo_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/foo_sub_group_1.png'],
        ['corge_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/corge_sub_group_2.png'],
        ['bar_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/bar_sub_group_1.svg'],
        ['baz_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/baz_sub_group_2.svg'],

      ],
    ];

    yield 'path sub group multiple wildcard folder and filename' => [
      ['icons/group/*/*/*'],
      [
        ['foo_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/foo_sub_group_1.png'],
        ['corge_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/corge_sub_group_2.png'],
        ['bar_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/bar_sub_group_1.svg'],
        ['baz_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/baz_sub_group_2.svg'],
      ],
    ];

    // Start tests for the {group} placeholder.
    yield 'path with {group} extracted' => [
      ['icons/group/{group}/*'],
      [
        ['bar_group_1', 'icons/group/group_1/bar_group_1.png', 'group_1'],
        ['baz_group_1', 'icons/group/group_1/baz_group_1.png', 'group_1'],
        ['bar_group_2', 'icons/group/group_2/bar_group_2.png', 'group_2'],
        ['baz_group_2', 'icons/group/group_2/baz_group_2.png', 'group_2'],
        ['corge_group_1', 'icons/group/group_1/corge_group_1.svg', 'group_1'],
        ['foo_group_1', 'icons/group/group_1/foo_group_1.svg', 'group_1'],
        ['corge_group_2', 'icons/group/group_2/corge_group_2.svg', 'group_2'],
        ['foo_group_2', 'icons/group/group_2/foo_group_2.svg', 'group_2'],
      ],
    ];

    yield 'test group extracted wildcard after' => [
      ['icons/group/{group}/*/*'],
      [
        ['bar_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/bar_sub_group_1.svg', 'sub_group_1'],
        ['baz_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/baz_sub_group_2.svg', 'sub_group_2'],
        ['corge_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/corge_sub_group_2.png', 'sub_group_2'],
        ['foo_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/foo_sub_group_1.png', 'sub_group_1'],
      ],
    ];

    yield 'test group extracted wildcard before' => [
      ['icons/group/*/{group}/*'],
      [
        ['bar_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/bar_sub_group_1.svg', 'sub_sub_group_1'],
        ['baz_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/baz_sub_group_2.svg', 'sub_sub_group_2'],
        ['corge_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/corge_sub_group_2.png', 'sub_sub_group_2'],
        ['foo_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/foo_sub_group_1.png', 'sub_sub_group_1'],
      ],
    ];

    yield 'test group extracted wildcard both' => [
      ['icons/*/{group}/*/*'],
      [
        ['bar_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/bar_sub_group_1.svg', 'sub_group_1'],
        ['baz_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/baz_sub_group_2.svg', 'sub_group_2'],
        ['corge_sub_group_2', 'icons/group/sub_group_2/sub_sub_group_2/corge_sub_group_2.png', 'sub_group_2'],
        ['foo_sub_group_1', 'icons/group/sub_group_1/sub_sub_group_1/foo_sub_group_1.png', 'sub_group_1'],
      ],
    ];

    yield 'test group same name' => [
      ['icons/group_same_name/{group}/*'],
      [
        ['foo', 'icons/group_same_name/group_3/foo.svg', 'group_3'],
      ],
    ];

    // Start tests for the {icon_id} placeholder.
    yield 'direct file with icon_id' => [
      [
        '{icon_id}',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and extension' => [
      [
        '{icon_id}.svg',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and extension wildcard' => [
      [
        '{icon_id}.*',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and wildcard after' => [
      [
        '{icon_id}*',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and wildcard before' => [
      [
        '*{icon_id}',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and wildcard around' => [
      [
        '*{icon_id}*',
      ],
      [
        ['foo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and wildcard around and prefix' => [
      [
        'f*{icon_id}*',
      ],
      [
        ['oo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and wildcard around and suffix' => [
      [
        '*{icon_id}*o',
      ],
      [
        ['fo', 'foo.svg'],
      ],
    ];

    yield 'direct file with icon_id and wildcard around and prefix and suffix' => [
      [
        'f*{icon_id}*o',
      ],
      [
        ['o', 'foo.svg'],
      ],
    ];

    yield 'test icon_id extracted' => [
      ['icons/prefix_suffix/{icon_id}.svg'],
      [
        ['grault', 'icons/prefix_suffix/grault.svg'],
        ['garply_suffix', 'icons/prefix_suffix/garply_suffix.svg'],
        ['prefix_quux', 'icons/prefix_suffix/prefix_quux.svg'],
        ['prefix_corge_suffix', 'icons/prefix_suffix/prefix_corge_suffix.svg'],
      ],
    ];

    yield 'test icon_id extracted prefix' => [
        ['icons/prefix_suffix/prefix_{icon_id}.svg'],
        [
          ['quux', 'icons/prefix_suffix/prefix_quux.svg'],
          ['corge_suffix', 'icons/prefix_suffix/prefix_corge_suffix.svg'],
        ],
    ];

    yield 'test icon_id extracted suffix' => [
        ['icons/prefix_suffix/{icon_id}_suffix.svg'],
        [
          ['garply', 'icons/prefix_suffix/garply_suffix.svg'],
          ['prefix_corge', 'icons/prefix_suffix/prefix_corge_suffix.svg'],
        ],
    ];

    yield 'test icon_id extracted both' => [
        ['icons/prefix_suffix/prefix_{icon_id}_suffix.svg'],
        [
          ['corge', 'icons/prefix_suffix/prefix_corge_suffix.svg'],
        ],
    ];

    yield 'test icon_id extracted with group' => [
        ['icons/prefix_suffix/{group}/{icon_id}.svg'],
        [
          ['fred_group', 'icons/prefix_suffix/group/fred_group.svg', 'group'],
          ['plugh_group_suffix', 'icons/prefix_suffix/group/plugh_group_suffix.svg', 'group'],
          ['prefix_qux_group', 'icons/prefix_suffix/group/prefix_qux_group.svg', 'group'],
          ['prefix_waldo_group_suffix', 'icons/prefix_suffix/group/prefix_waldo_group_suffix.svg', 'group'],
        ],
    ];

    yield 'test icon_id extracted with group and wildcard' => [
        ['icons/*/{group}/{icon_id}.svg'],
        [
          ['corge_group_1', 'icons/group/group_1/corge_group_1.svg', 'group_1'],
          ['foo_group_1', 'icons/group/group_1/foo_group_1.svg', 'group_1'],
          ['corge_group_2', 'icons/group/group_2/corge_group_2.svg', 'group_2'],
          ['foo_group_2', 'icons/group/group_2/foo_group_2.svg', 'group_2'],
          ['foo', 'icons/group_same_name/group_3/foo.svg', 'group_3'],
          ['fred_group', 'icons/prefix_suffix/group/fred_group.svg', 'group'],
          ['plugh_group_suffix', 'icons/prefix_suffix/group/plugh_group_suffix.svg', 'group'],
          ['prefix_qux_group', 'icons/prefix_suffix/group/prefix_qux_group.svg', 'group'],
          ['prefix_waldo_group_suffix', 'icons/prefix_suffix/group/prefix_waldo_group_suffix.svg', 'group'],
        ],
    ];

    yield 'path {icon_id} partial extracted wildcard extension' => [
      ['icons/flat_same_name/f{icon_id}o.*'],
      [
        ['o', 'icons/flat_same_name/foo.svg'],
      ],
    ];
  }

  /**
   * Test the IconFinder::getFilesFromSources method with paths.
   *
   * @param array<string> $sources
   *   The list of remote.
   * @param array<string, string> $expected
   *   The expected result.
   *
   * @dataProvider providerGetFilesFromSourcesPath
   */
  public function testGetFilesFromSourcesPath(array $sources, array $expected = []): void {
    $this->fileUrlGenerator
      ->expects($this->any())
      ->method('generateString')
      ->willReturnCallback(function ($uri) {
        return self::TEST_RELATIVE_URL . $uri;
      });

    $result = $this->iconFinder->getFilesFromSources(
      $sources,
      self::TEST_ICONS_PATH,
    );

    // Prepare result array matching processFoundFiles() to minimize test data.
    $expected_result = [];
    foreach ($expected as $key => $expected_value) {
      $icon_id = $expected[$key][0];
      $filename = $expected[$key][1];
      $group = $expected[$key][2] ?? NULL;
      $expected_result[$icon_id] = [
        'icon_id' => $icon_id,
        'source' => self::TEST_RELATIVE_URL . '/' . self::TEST_ICONS_PATH . '/' . $filename,
        'absolute_path' => DRUPAL_ROOT . '/' . self::TEST_ICONS_PATH . '/' . $filename,
        'group' => $group,
      ];
    }

    $this->assertEqualsCanonicalizing($expected_result, $result);
  }

  /**
   * Test the IconFinder::getFilesFromPath method with warning.
   */
  public function testGetFilesFromPathEmptyWarning(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'getFilesFromPath');
    $method->setAccessible(TRUE);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Invalid icon path extension @filename.@extension in source: @source');

    $method->invoke($this->iconFinder, self::TEST_ICONS_PATH . '/icons/flat/foo.webp', '');
  }

  /**
   * Test the IconFinder::getFilesFromPath method with warning.
   */
  public function testGetFilesFromPathInvalidExtensionWarning(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'getFilesFromPath');
    $method->setAccessible(TRUE);

    $this->logger->expects($this->once())
      ->method('warning');

    $method->invoke($this->iconFinder, self::TEST_ICONS_PATH . '/icons/empty/*.svg', '');
  }

  /**
   * Test the IconFinder::getFileFromUrl method with warning.
   */
  public function testGetFileFromUrlWarning(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'getFileFromUrl');
    $method->setAccessible(TRUE);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Invalid icon source: @source');

    $method->invoke($this->iconFinder, 'invalid_scheme', '', '');
  }

  /**
   * Test the IconFinder::findFiles method with warning with invalid path.
   */
  public function testFindFilesWarning(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'findFiles');
    $method->setAccessible(TRUE);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with('Invalid icon path in source: @source');

    $method->invoke($this->iconFinder, 'invalid_path', '*');
  }

  /**
   * Test the IconFinder::findFiles method with warning when no icons found.
   */
  public function testFindFilesEmptyWarning(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'findFiles');
    $method->setAccessible(TRUE);

    $this->logger->expects($this->once())
      ->method('warning');

    $method->invoke($this->iconFinder, self::TEST_ICONS_PATH . '/icons/empty', '*.svg');
  }

  /**
   * Data provider for ::testExtractIconIdFromFilename().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerExtractIconIdFromFilename(): iterable {
    yield 'no pattern' => [
      'foo bar baz',
      '',
      'foo bar baz',
    ];

    yield 'no pattern and wildcard' => [
      'foo*bar*baz',
      '',
      'foo*bar*baz',
    ];

    yield 'simple pattern' => [
      'foo bar baz',
      'foo {icon_id} baz',
      'bar',
    ];

    yield 'simple pattern and wildcard included' => [
      'foo*bar*baz',
      'foo{icon_id}baz',
      '*bar*',
    ];

    yield 'wrong pattern' => [
      'foo bar baz',
      'foo {ico_id} baz',
      'foo bar baz',
    ];

    yield 'pattern with special chars' => [
      'foO1o,-O 5 (B,ar)_b-az',
      'fo{icon_id}az',
      'O1o,-O 5 (B,ar)_b-',
    ];
  }

  /**
   * Test the IconFinder::extractIconIdFromFilename method.
   *
   * @param string $filename
   *   The filename found to match against.
   * @param string $filename_pattern
   *   The path with {icon_id}.
   * @param string $expected
   *   The expected result.
   *
   * @dataProvider providerExtractIconIdFromFilename
   */
  public function testExtractIconIdFromFilename(string $filename, string $filename_pattern, string $expected): void {
    $method = new \ReflectionMethod(IconFinder::class, 'extractIconIdFromFilename');
    $method->setAccessible(TRUE);

    $this->assertEquals($expected, $method->invoke($this->iconFinder, $filename, $filename_pattern));
  }

  /**
   * Test the IconFinder::extractIconIdFromFilename method with failing pattern.
   */
  public function testExtractIconIdFromFilenameWarning(): void {
    $method = new \ReflectionMethod(IconFinder::class, 'extractIconIdFromFilename');
    $method->setAccessible(TRUE);

    // PHPUnit 10 cannot expect warnings, so we have to catch them ourselves.
    // Thanks to: Drupal\Tests\Component\PhpStorage\FileStorageTest.
    $messages = [];
    set_error_handler(function (int $errno, string $errstr) use (&$messages): void {
      $messages[] = [$errno, $errstr];
    });

    $method->invoke($this->iconFinder, 'foo*bar*baz', 'foo*{icon_id}*baz');

    restore_error_handler();

    $this->assertCount(1, $messages);
    $this->assertSame(E_WARNING, $messages[0][0]);
    $this->assertSame('preg_match(): Compilation failed: quantifier does not follow a repeatable item at offset 19', $messages[0][1]);
  }

  /**
   * Data provider for ::testGetFileContents().
   *
   * @return array
   *   The test cases as uri and expected valid.
   */
  public static function providerGetFileContents(): array {
    return [
      'valid local file' => [
        DRUPAL_ROOT . '/' . self::TEST_ICONS_PATH . '/icons/flat/foo.svg',
        TRUE,
      ],
      'do not exist' => [
        DRUPAL_ROOT . '/' . self::TEST_ICONS_PATH . '/icons/do/not/exist.svg',
        FALSE,
      ],
      [
        'http://foo.com/bar.png',
        FALSE,
      ],
      [
        'https://foo.com/bar.png',
        FALSE,
      ],
      [
        'ftp://foo.com/bar.png',
        FALSE,
      ],
      [
        'ssh://foo.com/bar.png',
        FALSE,
      ],
      [
        '//foo.com/bar.png',
        FALSE,
      ],
    ];
  }

  /**
   * Test the IconFinder::getFileContents method.
   *
   * @param string $uri
   *   The uri to test result.
   * @param bool $expected
   *   The result of the file content is expected or not.
   *
   * @dataProvider providerGetFileContents
   */
  public function testGetFileContents(string $uri, bool $expected): void {
    if ($expected) {
      $result = $this->iconFinder->getFileContents($uri);
      $this->assertEquals(file_get_contents($uri), $result);
      return;
    }
    $this->assertFalse($this->iconFinder->getFileContents($uri));
  }

}
