<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Asset;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\CssCollectionOptimizerLazy;
use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the CSS asset optimizer.
 */
#[Group('Asset')]
class CssCollectionOptimizerLazyUnitTest extends UnitTestCase {

  /**
   * A CSS asset optimizer.
   */
  protected CssOptimizer $optimizer;

  /**
   * The file URL generator mock.
   */
  protected FileUrlGeneratorInterface|MockObject $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);
    $this->fileUrlGenerator->expects($this->any())
      ->method('generateString')
      ->with($this->isString())
      ->willReturnCallback(function ($uri) {
        return 'generated-relative-url:' . $uri;
      });
    $this->optimizer = new CssOptimizer($this->fileUrlGenerator);
  }

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
    $mock_file_system = $this->createMock(FileSystemInterface::class);
    $mock_config_factory = $this->createMock(ConfigFactoryInterface::class);
    $mock_file_url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $mock_time = $this->createMock(TimeInterface::class);
    $mock_language = $this->createMock(LanguageManagerInterface::class);
    $optimizer = new CssCollectionOptimizerLazy($mock_grouper, $mock_optimizer, $mock_theme_manager, $mock_dependency_resolver, new RequestStack(), $mock_file_system, $mock_config_factory, $mock_file_url_generator, $mock_time, $mock_language);
    $gpl_license = [
      'name' => 'GPL-2.0-or-later',
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
    $mock_file_system = $this->createMock(FileSystemInterface::class);
    $mock_config_factory = $this->createMock(ConfigFactoryInterface::class);
    $mock_file_url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $mock_time = $this->createMock(TimeInterface::class);
    $mock_language = $this->createMock(LanguageManagerInterface::class);
    $optimizer = new CssCollectionOptimizerLazy($mock_grouper, $mock_optimizer, $mock_theme_manager, $mock_dependency_resolver, new RequestStack(), $mock_file_system, $mock_config_factory, $mock_file_url_generator, $mock_time, $mock_language);
    $gpl_license = [
      'name' => 'GPL-2.0-or-later',
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

  /**
   * Test that external minified CSS assets do not trigger optimization.
   *
   * This ensures that fully external asset groups do not result in a
   * CssOptimizer exception and are safely ignored.
   */
  public function testExternalMinifiedCssAssetOptimizationIsSkipped(): void {
    $mock_grouper = $this->createMock(AssetCollectionGrouperInterface::class);
    $mock_optimizer = $this->createMock(AssetOptimizerInterface::class);

    // The expectation is to never call optimize on minified external assets.
    $mock_optimizer->expects($this->never())->method('optimize');

    $optimizer = new CssCollectionOptimizerLazy(
      $mock_grouper,
      $mock_optimizer,
      $this->createMock(ThemeManagerInterface::class),
      $this->createMock(LibraryDependencyResolverInterface::class),
      new RequestStack(),
      $this->createMock(FileSystemInterface::class),
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(FileUrlGeneratorInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(LanguageManagerInterface::class)
    );

    $optimizer->optimizeGroup([
      'items' => [
        [
          'type' => 'external',
          'data' => 'core/tests/Drupal/Tests/Core/Asset/css_test_files/css_external.optimized.aggregated.css',
          'license' => FALSE,
          'preprocess' => TRUE,
          'minified' => TRUE,
        ],
      ],
    ]);
  }

  /**
   * Test that local minified CSS assets still trigger optimization.
   *
   * This ensures that local minified assets are optimized to correct relative
   * paths.
   */
  public function testLocalMinifiedCssAssetOptimizationIsNotSkipped(): void {
    $mock_grouper = $this->createMock(AssetCollectionGrouperInterface::class);
    $mock_optimizer = $this->createMock(AssetOptimizerInterface::class);
    $mock_optimizer->expects($this->once())->method('optimize');

    $optimizer = new CssCollectionOptimizerLazy(
      $mock_grouper,
      $mock_optimizer,
      $this->createMock(ThemeManagerInterface::class),
      $this->createMock(LibraryDependencyResolverInterface::class),
      new RequestStack(),
      $this->createMock(FileSystemInterface::class),
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(FileUrlGeneratorInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(LanguageManagerInterface::class)
    );

    $optimizer->optimizeGroup([
      'items' => [
        [
          'type' => 'file',
          'data' => 'core/tests/Drupal/Tests/Core/Asset/css_test_files/css_input_with_import.css',
          'license' => FALSE,
          'preprocess' => TRUE,
          'minified' => TRUE,
        ],
      ],
    ]);
  }

  /**
   * Test that relative paths in local minified CSS files are updated.
   *
   * This ensures that local minified assets have their relative paths correctly
   * rewritten during optimization.
   */
  public function testRelativePathsInLocalMinifiedCssAssets(): void {
    $mock_grouper = $this->createMock(AssetCollectionGrouperInterface::class);

    $optimizer = new CssCollectionOptimizerLazy(
      $mock_grouper,
      $this->optimizer,
      $this->createMock(ThemeManagerInterface::class),
      $this->createMock(LibraryDependencyResolverInterface::class),
      new RequestStack(),
      $this->createMock(FileSystemInterface::class),
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(FileUrlGeneratorInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(LanguageManagerInterface::class)
    );

    $result = $optimizer->optimizeGroup([
      'items' => [
        [
          'type' => 'file',
          'data' => 'core/tests/Drupal/Tests/Core/Asset/css_test_files/css_minified_with_relative_paths.css',
          'media' => 'all',
          'license' => FALSE,
          'preprocess' => TRUE,
          'minified' => TRUE,
        ],
      ],
    ]);

    $expected = file_get_contents(__DIR__ . '/css_test_files/css_minified_with_relative_paths.css.optimized.css');
    self::assertEquals($expected, $result, 'Relative paths in local minified CSS assets are correctly replaced.');
  }

}
