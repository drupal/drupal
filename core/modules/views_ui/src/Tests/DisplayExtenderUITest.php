<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\DisplayExtenderUITest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Views;

/**
 * Tests the display extender UI.
 *
 * @group views_ui
 */
class DisplayExtenderUITest extends UITestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @todo https://www.drupal.org/node/2387149
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Tests the display extender UI.
   */
  public function testDisplayExtenderUI() {
    \Drupal::config('views.settings')->set('display_extenders', array('display_extender_test'))->save();

    $view = Views::getView('test_view');
    $view_edit_url = "admin/structure/views/view/{$view->storage->id()}/edit";
    $display_option_url = 'admin/structure/views/nojs/display/test_view/default/test_extender_test_option';

    $this->drupalGet($view_edit_url);
    $this->assertLinkByHref($display_option_url, 0, 'Make sure the option defined by the test display extender appears in the UI.');

    $random_text = $this->randomMachineName();
    $this->drupalPostForm($display_option_url, array('test_extender_test_option' => $random_text), t('Apply'));
    $this->assertLink($random_text);
    $this->drupalPostForm(NULL, array(), t('Save'));
    $view = Views::getView($view->storage->id());
    $view->initDisplay();
    $this->assertEqual($view->display_handler->getOption('test_extender_test_option'), $random_text, 'Make sure that the display extender option got saved.');
  }

}
