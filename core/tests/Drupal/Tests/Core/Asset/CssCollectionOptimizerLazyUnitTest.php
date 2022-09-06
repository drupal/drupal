<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Asset\CssCollectionOptimizerLazy;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the CSS asset optimizer.
 *
 * @group Asset
 */
class CssCollectionOptimizerLazyUnitTest extends UnitTestCase {

  /**
   * Tests that CSS imports with strange letters do not destroy the CSS output.
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
    $mock_theme_manager = $this->createMock(ThemeManagerInterface::class);
    $mock_dependency_resolver = $this->createMock(LibraryDependencyResolverInterface::class);
    $mock_state = $this->createMock(StateInterface::class);
    $mock_file_system = $this->createMock(FileSystemInterface::class);
    $mock_config_factory = $this->createMock(ConfigFactoryInterface::class);
    $mock_file_url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $mock_time = $this->createMock(TimeInterface::class);
    $mock_language = $this->createMock(LanguageManagerInterface::class);
    $optimizer = new CssCollectionOptimizerLazy($mock_grouper, $mock_optimizer, $mock_theme_manager, $mock_dependency_resolver, new RequestStack(), $mock_file_system, $mock_config_factory, $mock_file_url_generator, $mock_time, $mock_language, $mock_state);
    $gpl_license = [
      'name' => 'GNU-GPL-2.0-or-later',
      'url' => 'https://www.drupal.org/licensing/faq',
      'gpl-compatible' => TRUE,
    ];
    $aggregate = $optimizer->optimizeGroup(
      [
        'items' => [
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
      ],
    );
    self::assertStringEqualsFile(__DIR__ . '/css_test_files/css_input_with_import.css.optimized.aggregated.css', $aggregate);
  }

  /**
   * Test that license information is added correctly to aggregated CSS.
   *
   * Checks that license information is added only once when several files
   * have the same license. Checks that multiple licenses are added properly.
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
    $mock_theme_manager = $this->createMock(ThemeManagerInterface::class);
    $mock_dependency_resolver = $this->createMock(LibraryDependencyResolverInterface::class);
    $mock_state = $this->createMock(StateInterface::class);
    $mock_file_system = $this->createMock(FileSystemInterface::class);
    $mock_config_factory = $this->createMock(ConfigFactoryInterface::class);
    $mock_file_url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $mock_time = $this->createMock(TimeInterface::class);
    $mock_language = $this->createMock(LanguageManagerInterface::class);
    $optimizer = new CssCollectionOptimizerLazy($mock_grouper, $mock_optimizer, $mock_theme_manager, $mock_dependency_resolver, new RequestStack(), $mock_file_system, $mock_config_factory, $mock_file_url_generator, $mock_time, $mock_language, $mock_state);
    $gpl_license = [
      'name' => 'GNU-GPL-2.0-or-later',
      'url' => 'https://www.drupal.org/licensing/faq',
      'gpl-compatible' => TRUE,
    ];
    $aggregate = $optimizer->optimizeGroup(
      [
        'items' => [
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
      ],
    );
    self::assertStringEqualsFile(__DIR__ . '/css_test_files/css_license.css.optimized.aggregated.css', $aggregate);
  }

}
