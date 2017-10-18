<?php

namespace Drupal\Tests\settings_tray\Functional;

use Drupal\ajax_test\Controller\AjaxTestController;
use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Performs tests on opening and manipulating dialogs via AJAX commands.
 *
 * @group settings_tray
 */
class OffCanvasDialogTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['ajax_test', 'settings_tray'];

  /**
   * Test sending AJAX requests to open and manipulate off-canvas dialog.
   */
  public function testDialog() {
    $this->drupalLogin($this->drupalCreateUser(['administer contact forms']));
    // Ensure the elements render without notices or exceptions.
    $this->drupalGet('ajax-test/dialog');

    // Set up variables for this test.
    $dialog_renderable = AjaxTestController::dialogContents();
    $dialog_contents = \Drupal::service('renderer')->renderRoot($dialog_renderable);

    $off_canvas_expected_response = [
      'command' => 'openDialog',
      'selector' => '#drupal-off-canvas',
      'settings' => NULL,
      'data' => $dialog_contents,
      'dialogOptions' =>
        [
          'title' => 'AJAX Dialog & contents',
          'modal' => FALSE,
          'autoResize' => FALSE,
          'resizable' => 'w',
          'draggable' => FALSE,
          'drupalAutoButtons' => FALSE,
          'buttons' => [],
          'dialogClass' => 'ui-dialog-off-canvas',
          'width' => 300,
        ],
      'effect' => 'fade',
      'speed' => 1000,
    ];

    // Emulate going to the JS version of the page and check the JSON response.
    $ajax_result = $this->drupalGet('ajax-test/dialog-contents', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_dialog.off_canvas']]);
    $ajax_result = Json::decode($ajax_result);
    $this->assertEqual($off_canvas_expected_response, $ajax_result[3], 'off-canvas dialog JSON response matches.');
  }

}
