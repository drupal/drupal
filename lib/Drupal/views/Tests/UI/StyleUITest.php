<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\StyleUITest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the UI of style plugins.
 *
 * @see Drupal\views_test_data\Plugin\views\style\StyleTest.
 */
class StyleUITest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Style: UI',
      'description' => 'Tests the UI of style plugins.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests changing the style plugin and changing some options of a style.
   */
  public function testStyleUI() {
    $view = $this->getView();
    $view_edit_url = "admin/structure/views/view/{$view->storage->name}/edit";

    $style_plugin_url = "admin/structure/views/nojs/display/{$view->storage->name}/default/style";
    $style_options_url = "admin/structure/views/nojs/display/{$view->storage->name}/default/style_options";

    $this->drupalGet($style_plugin_url);
    $this->assertFieldByName('style', 'default', 'The default style plugin selected in the UI should be unformatted list.');

    $edit = array(
      'style' => 'test_style'
    );
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->assertFieldByName('style_options[test_option]', NULL, 'Make sure the custom settings form from the test plugin appears.');
    $random_name = $this->randomName();
    $edit = array(
      'style_options[test_option]' => $random_name
    );
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->drupalGet($style_options_url);
    $this->assertFieldByName('style_options[test_option]', $random_name, 'Make sure the custom settings form field has the expected value stored.');

    $this->drupalPost($view_edit_url, array(), t('Save'));
    $this->assertLink(t('Test style plugin'), 0, 'Make sure the test style plugin is shown in the UI');

    $view = views_get_view($view->storage->name);
    $view->initDisplay();
    $style = $view->display_handler->getOption('style');
    $this->assertEqual($style['type'], 'test_style', 'Make sure that the test_style got saved as used style plugin.');
    $this->assertEqual($style['options']['test_option'], $random_name, 'Make sure that the custom settings field got saved as expected.');
  }

}
