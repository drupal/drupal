<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Views;

/**
 * Tests the UI of style plugins.
 *
 * @group views_ui
 * @see \Drupal\views_test_data\Plugin\views\style\StyleTest.
 */
class StyleUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests changing the style plugin and changing some options of a style.
   */
  public function testStyleUI() {
    $view_name = 'test_view';
    $view_edit_url = "admin/structure/views/view/$view_name/edit";

    $style_plugin_url = "admin/structure/views/nojs/display/$view_name/default/style";
    $style_options_url = "admin/structure/views/nojs/display/$view_name/default/style_options";

    $this->drupalGet($style_plugin_url);
    $this->assertSession()->fieldValueEquals('style[type]', 'default');

    $edit = [
      'style[type]' => 'test_style',
    ];
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->fieldExists('style_options[test_option]');
    $random_name = $this->randomMachineName();
    $edit = [
      'style_options[test_option]' => $random_name,
    ];
    $this->submitForm($edit, 'Apply');
    $this->drupalGet($style_options_url);
    $this->assertSession()->fieldValueEquals('style_options[test_option]', $random_name);

    $this->drupalPostForm($view_edit_url, [], 'Save');
    $this->assertSession()->linkExists('Test style plugin', 0, 'Make sure the test style plugin is shown in the UI');

    $view = Views::getView($view_name);
    $view->initDisplay();
    $style = $view->display_handler->getOption('style');
    $this->assertEqual('test_style', $style['type'], 'Make sure that the test_style got saved as used style plugin.');
    $this->assertEqual($random_name, $style['options']['test_option'], 'Make sure that the custom settings field got saved as expected.');

    // Test that fields are working correctly in the UI for style plugins when
    // a field row plugin is selected.
    $this->drupalPostForm("admin/structure/views/view/$view_name/edit", [], 'Add Page');
    $this->drupalPostForm("admin/structure/views/nojs/display/$view_name/page_1/row", ['row[type]' => 'fields'], 'Apply');
    // If fields are being used this text will not be shown.
    $this->assertNoText('The selected style or row format does not use fields.');
  }

}
