<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

// cspell:ignore subtheming

/**
 * Tests warnings when ckeditor_stylesheets do not have CKEditor 5 equivalents.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditorStylesheetsWarningTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Installs and enables themes for testing.
   *
   * @param string $theme
   *   The theme to enable.
   */
  public function installThemeThatTriggersWarning($theme) {
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install([$theme]);
    $this->config('system.theme')->set('default', $theme)->save();
    $theme_installer->install(['stark']);
    $this->config('system.theme')->set('admin', 'stark')->save();
    \Drupal::service('theme_handler')->refreshInfo();
  }

  /**
   * Test the ckeditor_stylesheets warning in the filter UI.
   *
   * @dataProvider providerTestWarningFilterUI
   */
  public function testWarningFilterUi($theme, $expected_warning): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->addNewTextFormat($page, $assert_session);
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');

    $assert_session->pageTextNotContains($expected_warning);
    $this->installThemeThatTriggersWarning($theme);
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $this->assertTrue($assert_session->waitForText($expected_warning));
  }

  /**
   * Data provider for testWarningFilterUI().
   *
   * @return string[][]
   *   An array with the theme to enable and the warning message to check.
   */
  public function providerTestWarningFilterUi() {
    return [
      'single theme' => [
        'theme' => 'test_ckeditor_stylesheets_without_5',
        'expected_warning' => 'The No setting for CKEditor 5 stylesheets theme has ckeditor_stylesheets configured without a corresponding ckeditor5-stylesheets configuration. See the change record for details.',
      ],
      'with base theme' => [
        'theme' => 'test_subtheming_ckeditor_stylesheets_without_5',
        'expected_warning' => 'The No setting for CKEditor 5 stylesheets here or subtheme and No setting for CKEditor 5 stylesheets themes have ckeditor_stylesheets configured, but without corresponding ckeditor5-stylesheets configurations. See the change record for details.',
      ],
    ];
  }

}
