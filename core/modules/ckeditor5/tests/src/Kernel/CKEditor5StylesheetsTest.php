<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the ckeditor5-stylesheets theme config property.
 *
 * @group ckeditor5
 */
class CKEditor5StylesheetsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'ckeditor5',
    'editor',
    'filter',
  ];

  /**
   * Tests loading of theme's CKEditor 5 stylesheets defined in the .info file.
   *
   * @param string $theme
   *   The machine name of the theme.
   * @param array $expected
   *   The expected CKEditor 5 CSS paths from the theme.
   *
   * @dataProvider externalStylesheetsProvider
   */
  public function testExternalStylesheets($theme, $expected): void {
    \Drupal::service('theme_installer')->install([$theme]);
    $this->config('system.theme')->set('default', $theme)->save();
    $this->assertSame($expected, _ckeditor5_theme_css($theme));
  }

  /**
   * Provides test cases for external stylesheets.
   *
   * @return array
   *   An array of test cases.
   */
  public static function externalStylesheetsProvider() {
    return [
      'Install theme which has an absolute external CSS URL' => [
        'test_ckeditor_stylesheets_external',
        ['https://fonts.googleapis.com/css?family=Open+Sans'],
      ],
      'Install theme which has an external protocol-relative CSS URL' => [
        'test_ckeditor_stylesheets_protocol_relative',
        ['//fonts.googleapis.com/css?family=Open+Sans'],
      ],
      'Install theme which has a relative CSS URL' => [
        'test_ckeditor_stylesheets_relative',
        ['/core/modules/system/tests/themes/test_ckeditor_stylesheets_relative/css/yokotsoko.css'],
      ],
      'Install theme which has a Drupal root CSS URL' => [
        'test_ckeditor_stylesheets_drupal_root',
        ['/core/modules/system/tests/themes/test_ckeditor_stylesheets_drupal_root/css/yokotsoko.css'],
      ],
    ];
  }

}
