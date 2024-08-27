<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Ajax;

use Drupal\ajax_test\Controller\AjaxTestController;
use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Performs tests on opening and manipulating dialogs via AJAX commands.
 *
 * @group Ajax
 */
class OffCanvasDialogTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test'];

  /**
   * Tests sending AJAX requests to open and manipulate off-canvas dialog.
   *
   * @dataProvider dialogPosition
   */
  public function testDialog($position): void {
    // Ensure the elements render without notices or exceptions.
    $this->drupalGet('ajax-test/dialog');

    // Set up variables for this test.
    $dialog_renderable = AjaxTestController::dialogContents();
    $dialog_contents = \Drupal::service('renderer')->renderRoot($dialog_renderable);
    $dialog_class = 'ui-dialog-off-canvas ui-dialog-position-' . ($position ?: 'side');
    $off_canvas_expected_response = [
      'command' => 'openDialog',
      'selector' => '#drupal-off-canvas',
      'settings' => NULL,
      'data' => (string) $dialog_contents,
      'dialogOptions' =>
        [
          'classes' => ['ui-dialog' => $dialog_class, 'ui-dialog-content' => 'drupal-off-canvas-reset'],
          'title' => 'AJAX Dialog & contents',
          'modal' => FALSE,
          'autoResize' => FALSE,
          'resizable' => 'w',
          'draggable' => FALSE,
          'drupalAutoButtons' => FALSE,
          'drupalOffCanvasPosition' => $position ?: 'side',
          'width' => 300,
        ],
      'effect' => 'fade',
      'speed' => 1000,
    ];

    // Emulate going to the JS version of the page and check the JSON response.
    $wrapper_format = $position && ($position !== 'side') ? 'drupal_dialog.off_canvas_' . $position : 'drupal_dialog.off_canvas';
    $ajax_result = $this->drupalGet('ajax-test/dialog-contents', ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => $wrapper_format]]);
    $ajax_result = Json::decode($ajax_result);

    $this->assertSame($off_canvas_expected_response, $ajax_result[4], 'off-canvas dialog JSON response matches.');
  }

  /**
   * The data provider for potential dialog positions.
   *
   * @return array
   */
  public static function dialogPosition() {
    return [
      [NULL],
      ['side'],
      ['top'],
    ];
  }

}
