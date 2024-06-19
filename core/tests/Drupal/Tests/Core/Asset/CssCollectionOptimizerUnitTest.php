<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CSS asset optimizer.
 *
 * @group Asset
 */
class CssCollectionOptimizerUnitTest extends UnitTestCase {

  /**
   * The data from the dumper.
   *
   * @var string
   */
  protected $dumperData;

  /**
   * A CSS Collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $optimizer;

  /**
   * Tests that CSS imports with strange letters do not destroy the CSS output.
   *
   * @group legacy
   */
  public function testCssImport(): void {
    $mock_grouper = $this->createMock(AssetCollectionGrouperInterface::class);
    $mock_grouper->method('group')
      ->willReturnCallback(function ($assets) {
        return [
          [
            'items' => $assets,
            'type' => 'file',
            'preprocess' => TRUE,
          ],
        ];
      });
    $mock_optimizer = $this->createMock(AssetOptimizerInterface::class);
    $mock_optimizer->method('optimize')
      ->willReturn(
        file_get_contents(__DIR__ . '/css_test_files/css_input_with_import.css.optimized.css'),
        file_get_contents(__DIR__ . '/css_test_files/css_subfolder/css_input_with_import.css.optimized.css')
      );
    $mock_dumper = $this->createMock(AssetDumperInterface::class);
    $mock_dumper->method('dump')
      ->willReturnCallback(function ($css) {
        $this->dumperData = $css;
      });
    $mock_state = $this->createMock(StateInterface::class);
    $mock_file_system = $this->createMock(FileSystemInterface::class);
    $this->optimizer = new CssCollectionOptimizer($mock_grouper, $mock_optimizer, $mock_dumper, $mock_state, $mock_file_system);
    $gpl_license = [
      'name' => 'GPL-2.0-or-later',
      'url' => 'https://www.drupal.org/licensing/faq',
      'gpl-compatible' => TRUE,
    ];
    $this->optimizer->optimize([
      'core/modules/system/tests/modules/common_test/common_test_css_import.css' => [
        'type' => 'file',
        'data' => 'core/modules/system/tests/modules/common_test/common_test_css_import.css',
        'preprocess' => TRUE,
        'license' => $gpl_license,
      ],
      'core/modules/system/tests/modules/common_test/common_test_css_import_not_preprocessed.css' => [
        'type' => 'file',
        'data' => 'core/modules/system/tests/modules/common_test/common_test_css_import.css',
        'preprocess' => TRUE,
        'license' => $gpl_license,
      ],
    ],
    []);
    self::assertEquals(file_get_contents(__DIR__ . '/css_test_files/css_input_with_import.css.optimized.aggregated.css'), $this->dumperData);
  }

  /**
   * Tests that CSS imports with strange letters do not destroy the CSS output.
   *
   * Checks that license information is added only once when several files
   * have the same license. Checks that multiple licenses are added properly.
   *
   * @group legacy
   */
  public function testCssLicenseAggregation(): void {
    $mock_grouper = $this->createMock(AssetCollectionGrouperInterface::class);
    $mock_grouper->method('group')
      ->willReturnCallback(function ($assets) {
        return [
          [
            'items' => $assets,
            'type' => 'file',
            'preprocess' => TRUE,
          ],
        ];
      });
    $mock_optimizer = $this->createMock(AssetOptimizerInterface::class);
    $mock_optimizer->method('optimize')
      ->willReturn(
        file_get_contents(__DIR__ . '/css_test_files/css_input_with_import.css.optimized.css'),
        file_get_contents(__DIR__ . '/css_test_files/css_subfolder/css_input_with_import.css.optimized.css'),
        file_get_contents(__DIR__ . '/css_test_files/css_input_without_import.css.optimized.css')
      );
    $mock_dumper = $this->createMock(AssetDumperInterface::class);
    $mock_dumper->method('dump')
      ->willReturnCallback(function ($css) {
        $this->dumperData = $css;
      });
    $mock_state = $this->createMock(StateInterface::class);
    $mock_file_system = $this->createMock(FileSystemInterface::class);
    $this->optimizer = new CssCollectionOptimizer($mock_grouper, $mock_optimizer, $mock_dumper, $mock_state, $mock_file_system);
    $gpl_license = [
      'name' => 'GPL-2.0-or-later',
      'url' => 'https://www.drupal.org/licensing/faq',
      'gpl-compatible' => TRUE,
    ];
    $this->optimizer->optimize([
      'core/modules/system/tests/modules/common_test/common_test_css_import.css' => [
        'type' => 'file',
        'data' => 'core/modules/system/tests/modules/common_test/common_test_css_import.css',
        'preprocess' => TRUE,
        'license' => $gpl_license,
      ],
      'core/modules/system/tests/modules/common_test/common_test_css_import_not_preprocessed.css' => [
        'type' => 'file',
        'data' => 'core/modules/system/tests/modules/common_test/common_test_css_import.css',
        'preprocess' => TRUE,
        'license' => $gpl_license,
      ],
      'core/modules/system/tests/modules/common_test/css_input_without_import.css' => [
        'type' => 'file',
        'data' => 'core/modules/system/tests/modules/common_test/css_input_without_import.css',
        'preprocess' => TRUE,
        'license' => [
          'name' => 'MIT',
          'url' => 'https://opensource.org/licenses/MIT',
          'gpl-compatible' => TRUE,
        ],
      ],
    ],
    []);
    self::assertEquals(file_get_contents(__DIR__ . '/css_test_files/css_license.css.optimized.aggregated.css'), $this->dumperData);
  }

}
