<?php

namespace Drupal\Tests\settings_tray\Unit\Ajax;

use Drupal\settings_tray\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\settings_tray\Ajax\OpenOffCanvasDialogCommand
 * @group settings_tray
 */
class OpenOffCanvasDialogCommandTest extends UnitTestCase {

  /**
   * @covers ::render
   */
  public function testRender() {
    $command = new OpenOffCanvasDialogCommand('Title', '<p>Text!</p>', ['url' => 'example']);

    $expected = [
      'command' => 'openDialog',
      'selector' => '#drupal-off-canvas',
      'settings' => NULL,
      'data' => '<p>Text!</p>',
      'dialogOptions' => [
        'url' => 'example',
        'title' => 'Title',
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
    $this->assertEquals($expected, $command->render());
  }

}
