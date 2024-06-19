<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Views;

/**
 * Tests the display extender UI.
 *
 * @group views_ui
 */
class DisplayExtenderUITest extends UITestBase {

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
   * Tests the display extender UI.
   */
  public function testDisplayExtenderUI(): void {
    $this->config('views.settings')->set('display_extenders', ['display_extender_test'])->save();

    $view = Views::getView('test_view');
    $view_edit_url = "admin/structure/views/view/{$view->storage->id()}/edit";
    $display_option_url = 'admin/structure/views/nojs/display/test_view/default/test_extender_test_option';

    $this->drupalGet($view_edit_url);
    $this->assertSession()->linkByHrefExists($display_option_url, 0, 'Make sure the option defined by the test display extender appears in the UI.');

    $random_text = $this->randomMachineName();
    $this->drupalGet($display_option_url);
    $this->submitForm(['test_extender_test_option' => $random_text], 'Apply');
    $this->assertSession()->linkExists($random_text);
    $this->submitForm([], 'Save');
    $view = Views::getView($view->storage->id());
    $view->initDisplay();
    $display_extender_options = $view->display_handler->getOption('display_extenders');
    $this->assertEquals($random_text, $display_extender_options['display_extender_test']['test_extender_test_option'], 'Make sure that the display extender option got saved.');
  }

}
